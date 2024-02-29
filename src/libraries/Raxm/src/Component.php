<?php

namespace Raxm;

use Axm;
use Exception;
use Views\View;
use RuntimeException;
use Http\Request;
use Http\Response;
use Raxm\ComponentCheckSum;
use Raxm\ComponentProperties;
use Raxm\LifecycleManager;
use App\Controllers\BaseController;
use Raxm\ReceivesEvents;
use Raxm\Support\InteractsWithProperties;
use Raxm\Support\HandlesActions;
use Raxm\Support\ValidatesInput;

/**
 * Abstract base class for Raxm components.
 *
 * This class serves as the base class for all Raxm components,
 * providing common functionality and methods.
 * Raxm components are used to build dynamic web interfaces with 
 * real-time updates and interactions.
 */
abstract class Component extends BaseController
{
    use ReceivesEvents;
    use InteractsWithProperties;
    use HandlesActions;
    use ValidatesInput;

    protected ?Request  $request;
    protected ?Response $response;
    protected bool $shouldSkipRendering = false;
    protected ?array  $serverMemo  = null;
    protected ?array  $fingerprint = null;
    protected ?array  $updates = null;
    protected ?array  $publicProperties = [];
    protected ?string $preRenderedView;
    protected bool $ifActionIsRedirect = false;
    protected bool $ifActionIsNavigate = false;
    public ?string $id;
    public ?array $effects = [];
    protected $id_p;
    protected $component;
    protected $type;
    protected $method;
    protected $params;
    protected $payload;
    protected $return = [];
    protected $eventQueue    = [];
    protected $dispatchQueue = [];
    protected $listeners     = [];
    protected $queryString   = [];
    protected $rules = [];
    protected $messages;
    public $tmpfile;

    /**
     * Constructor for the Raxm Component.
     */
    public function __construct()
    {
        $app = Axm::app();
        $this->request  = $app->request  ?? null;
        $this->response = $app->response ?? null;
    }

    /**
     * Run the Raxm component.
     *
     * This method is responsible for executing the Raxm component's logic.
     * It performs tasks such as checking if the request is a Raxm request,
     * extracting data from the request, hydrating the component's state,
     * dispatching events, and preparing and sending a JSON response.
     */
    private function run()
    {
        $this->isRaxmRequest();
        $this->extractDataRequest($this->request);
        $this->hydrateFromServerMemo();
        $this->hydratePayload();
        $this->dispatchEvents();
        $this->compileResponse();
        $this->sendJsonResponse();
    }

    /**
     * Check if the request is a Raxm request.
     *
     * This method checks if the current HTTP request is a valid Raxm request.
     * It verifies the 'x-axm' header to ensure that the request is a Raxm request.
     * If the check fails, it throws an exception.
     */
    private function isRaxmRequest()
    {
        if ($this->request->getHeader('X-AXM') != true) {
            throw new Exception('This request is not a Raxm request');
        }
    }

    /**
     * Extract data from the request.
     *
     * This method extracts data from the HTTP request, including server memo,
     * updates, and fingerprint data. It sets the component's ID and name based on the fingerprint.
     * @param Request $request The HTTP request object.
     */
    private function extractDataRequest(Request $request): void
    {
        $this->serverMemo  = $request->serverMemo  ?? [];
        $this->updates     = $request->updates     ?? [];
        $this->fingerprint = $request->fingerprint ?? [];

        [$this->id, $this->component] = [
            $this->fingerprint['id'], $this->fingerprint['name']
        ];
    }

    /**
     * Hydrate the component's state from the server memo.
     * This method hydrates the component's state using data
     * from the server memo.
     */
    private function hydrateFromServerMemo(): void
    {
        $this->mount($this->serverMemo['data']);
    }

    /**
     * Hydrate the component's payload data.
     * This method hydrates the component's payload data based on 
     * updates received from the client.
     */
    private function hydratePayload()
    {
        $payloads = $this->updates ?? [];
        foreach ($payloads as $payload) {
            $this->payload = $payload['payload'];

            $this->type   = $payload['type'];
            $this->id_p   = $payload['payload']['id'];
            $this->method = $payload['payload']['method'] ?? null;
            $this->params = $payload['payload']['params'] ?? null;
        }
    }

    /**
     * Dispatch events based on the payload type.
     * This method dispatches events based on the type of payload 
     * received from the client.
     */
    private function dispatchEvents()
    {
        match ($this->type) {
            'syncInput'  => $this->syncInputData(),
            'callMethod' => $this->callMethod($this->method, $this->params),
            'fireEvent'  => $this->fireEvent($this->method, $this->params, $this->id_p),

            default => throw new Exception('Unknown event type: ' . $this->type)
        };
    }

    /**
     * Sync input data based on updates.
     * This method syncs input data based on updates received from the client.
     */
    private function syncInputData(): void
    {
        foreach ($this->updates as $update) {
            $name  = $update['payload']['name']  ?? null;
            $value = $update['payload']['value'] ?? null;

            $this->syncInput($name, $value);
        }
    }

    /**
     * Initialize the Raxm component.
     *
     * This method initializes the Raxm component, setting its ID and preparing 
     * the response for the client.
     * @param string|null $id The component ID (optional).
     * @return mixed The response to send to the client.
     */
    private function initialInstance($id = null): string
    {
        // Generate a random ID if one is not already set.
        $this->id = $id ?? bin2hex(random_bytes(10));

        // Prepare the response that will be sent to the client.
        $this->prepareResponse();

        // Return the response to the client.
        return $this->html();
    }

    /**
     * Get the HTML representation of the component.
     *
     * This method returns the HTML representation of the component, 
     * which is stored in the 'effects' array.
     * @return string|null The HTML representation of the component.
     */
    private function html()
    {
        return $this->effects['html'] ?? null;
    }

    /**
     * Embed the component's data in the HTML representation.
     *
     * This method embeds the component's data in the HTML representation 
     * by adding attributes to the HTML root tag.
     */
    private function embedThyselfInHtml()
    {
        if (!$html = $this->renderToView()) return;
        $this->effects['html'] = (new HtmlRootTagAttributeAdder)($html, [
            'initial-data' => $this->toArrayWithoutHtml()
        ]);
    }

    /**
     * Embed the component's ID in the HTML representation.
     *
     * This method embeds the component's ID in the HTML representation by adding an 'id' 
     * attribute to the HTML root tag.
     */
    private function embedIdInHtml()
    {
        if (!$html = $this->effects['html'] ?? null) return;
        $this->effects['html'] = (new HtmlRootTagAttributeAdder)($html, [
            'id' => $this->id,
        ]);
    }

    /**
     * It is used to wrap the HTML content of the 'effects' array inside a div element.
     */
    private function wrapInDiv()
    {
        if (!$html = $this->effects['html'] ?? null) return;
        $this->effects['html'] = sprintf("<div>\n%s\n</div>\n", $html);
    }

    /**
     * Get an array of component data without HTML.
     *
     * This method returns an array of component data without the HTML representation.
     * @return array The component data without HTML.
     */
    private function toArrayWithoutHtml()
    {
        $fingerprint = $this->fingerprint ?? LifecycleManager::initialFingerprint();
        $effects     = array_diff_key($this->effects, ['html' => null]) ?: LifecycleManager::initialEffects();
        $serverMemo  = $this->serveMemo() ?? LifecycleManager::createDataServerMemo();

        return compact('fingerprint', 'effects', 'serverMemo');
    }

    /**
     * Render the component to a view.
     *
     * This method renders the component to a view, which is either returned by 
     * the 'render' method or a default view.
     * @return string|null The rendered view or null if not found.
     * @throws \Exception If the "render" method does not return an instance of View.
     */
    private function renderToView()
    {
        $view = $this->getView();
        return $this->preRenderedView = $view;
    }

    /**
     * Call the component's 'render' method.
     *
     * This method calls the 'render' method of the component or falls back to a default 
     * view if the 'render' method is not defined.
     * @return string|null The rendered view or null if not found.
     */
    private function callRender()
    {
        $mergePublicProperties  = View::$tempData = $this->getPublicProperties($this);
        $this->publicProperties = $mergePublicProperties;

        return $this->render();
    }

    /**
     * Get the view for the component.
     *
     * This method retrieves the view for the component, either by calling 
     * the 'render' method or using a default view name.
     * @return string|null The view for the component.
     */
    private function getView(): ?string
    {
        $view = method_exists($this, 'render')
            ? $this->callRender() : view('raxm.' . $this->getComponentName());

        return $view;
    }

    /**
     * Get the name of the component.
     *
     * This method returns the name of the component using the 'componentName' 
     * method from Raxm.
     * @return string The name of the component.
     */
    private function getComponentName()
    {
        return Raxm::componentName();
    }

    /**
     * Compile and mount the component.
     *
     * @param  mixed $class
     * @return void
     */
    public function index(Object $component)
    {
        Raxm::mountComponent($component);
    }

    /**
     * Sets component properties based on provided parameters.
     *
     * Populates public properties with valid values from the given array.
     * @param array $params Associative array of property-value pairs.
     * @return $this Current instance of the component.
     */
    protected function mount($params = [])
    {
        $this->publicProperties = ComponentProperties::getPublicProperties($this);
        foreach ($params as $property => $value) {
            if (isset($this->publicProperties[$property]) && (is_array($value)
                || is_scalar($value) || is_null($value))) {
                // Assign the value to the property.
                $this->{$property} = $value;
            }
        }

        return $this;
    }

    /**
     * Get the public properties of the component.
     *
     * This method retrieves the public properties of the component using 
     * the 'getPublicProperties' method from ComponentProperties.
     * @return array The public properties of the component.
     */
    private function getPublicProperties()
    {
        return ComponentProperties::getPublicProperties($this);
    }

    /**
     * Prepare the response data for the client.
     *
     * This method prepares the response data that will be sent to the client, 
     * including effects, server memo, and checksum.
     * @return array The prepared response data.
     */
    private function prepareResponse(): array
    {
        return [
            'effects' => $this->effects(),
            'serverMemo' => $this->serveMemo(),
        ];
    }

    /**
     * Generates and serves a memo containing specific information.
     * @return array The generated memo including a random HTML hash,
     * a data response, and a checksum.
     */
    private function serveMemo(): array
    {
        // Create an associative array representing the memo to be served.
        $serverMemo = [
            'htmlHash' => randomId(8),   // Generate a random HTML hash with 8 characters.
            'data'     => $this->dataResponse(),  // Get the data response using the dataResponse() method.
            'checksum' => $this->checkSumAndGenerate(
                $this->serverMemo['checksum'] ?? '',   // Get the current checksum of the memo or a default value.
                $this->fingerprint ?? [],   // Get the fingerprint or an empty array if not defined.
                $this->serverMemo  ?? []    // Get the current memo or an empty array if not defined.
            )
        ];

        // Return the generated memo.
        return $serverMemo;
    }

    /**
     * Get the effects data for the client.
     *
     * This method retrieves the effects data, including HTML, dirty data, events,
     * and listeners, to be sent to the client.
     * @return array The effects data.
     */
    private function effects(): array
    {
        $this->embedThyselfInHtml();
        $this->embedIdInHtml();
        $this->wrapInDiv();

        $effects = [
            'html'  => $this->html(),
            'dirty' => $this->getChangedData(),
            'emits' => $this->getEventQueue(),
            'listeners'  => $this->getEventsBeingListenedFor(),
            'dispatches' => $this->getDispatchQueue(),
        ];

        // Check if $this->ifActionIsRedirect is true before adding 'redirect' to the array.
        if ($this->ifActionIsRedirect == true) {
            $effects['redirect'] = $this->getRedirectTo();
        }

        if ($this->ifActionIsNavigate == true) {
            $effects['navigate'] = $this->getRedirectTo();
        }

        return $effects;
    }

    /**
     * Add effects data to the component.
     *
     * This method adds additional effects data to the component's effects array.
     * @param string $key The key for the effect data.
     * @param mixed $value The value of the effect data.
     * @return mixed The updated effects array.
     */
    private function addEffects(string $key, $value)
    {
        return $this->effects[$key] = $value;
    }

    /**
     * Get the URL to redirect to.
     *
     * This method determines the URL to which the client should be redirected
     * based on the 'redirect' method or a default URL.
     * @return string The URL to redirect to.
     */
    private function getRedirectTo()
    {
        if (method_exists($this, 'redirect')) {
            $pathTo = $this->redirect();
            return generateUrl($pathTo);
        }
    }

    /**
     * Check and generate the checksum for data integrity.
     *
     * This method checks and generates a checksum to ensure the integrity of the component's data.
     * @param string $checksum The existing checksum.
     * @param array $fingerprint The component's fingerprint data.
     * @param array $memo The server memo data.
     * @return mixed The generated checksum.
     */
    private function checkSumAndGenerate($checksum, $fingerprint, $memo)
    {
        if (ComponentCheckSum::check($checksum, $fingerprint, $memo))
            throw new RuntimeException("Raxm encountered corrupt data when 
                trying to hydrate the $this->component component. \n" .
                "Ensure that the [name, id, data] 
                of the Raxm component wasn't tampered with between requests.");

        return ComponentCheckSum::generate($fingerprint, $memo);
    }

    /**
     * Get the changed data properties.
     *
     * This method retrieves the properties of the component's data that have 
     * changed compared to the server memo.
     * @return array The changed data properties.
     */
    private function getChangedData()
    {
        $changedData = [];
        foreach ($this->serverMemo['data'] ?? [] as $key => $value) {
            if (isset($this->{$key}) && $this->{$key} != $value) {
                $changedData[] = $key;
            }

            return $changedData;
        }
    }

    /**
     * Get the data to include in the server response.
     *
     * This method retrieves the data to include in the server response, 
     * excluding any properties that are not part of the component's public properties.
     * @return array The data to include in the server response.
     */
    private function dataResponse()
    {
        return array_filter($this->publicProperties, function ($key) {
            return property_exists($this, $key);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Prepare and send a JSON response to the client.
     * This method prepares the response data and sends it as a JSON 
     * response to the client.
     */
    private function compileResponse()
    {
        return $this->return = $this->prepareResponse();
    }

    /**
     * Sends a JSON response using the response object and the specified data.
     * @return string The JSON representation of the specified data.
     */
    private function sendJsonResponse()
    {
        // Uses the response object to convert the specified data to JSON.
        return $this->response->toJson($this->return);
    }

    /**
     * Handle dynamic method calls.
     *
     * This method handles dynamic method calls on the component,
     * allowing it to call its methods.
     * @param string $method The method to call.
     * @param array $params The method parameters.
     * @return mixed The result of the method call.
     */
    public function __call($method, $params)
    {
        $reservedMethods = ['hydrate', 'dehydrate'];
        if (in_array($method, $reservedMethods)) {
            throw new Exception(
                sprintf('This method is reserved for Raxm [ %s ] ', implode(', ', $reservedMethods))
            );
        }

        $className = static::class;
        if (!method_exists($this, $method)) {
            throw new Exception(sprintf('Method [ %s ] does not exist', "$className::$method()"));
        }

        return $this->$method(...$params);
    }

    /**
     * Magic method to access properties of the class.
     *
     * @param string $property The name of the property to access.
     * @return mixed The value of the property if it exists and is public.
     * @throws Exception If the property does not exist or is not public.
     */
    public function __get($property)
    {
        $publicProperties = $this->getPublicProperties($this);
        if (isset($publicProperties[$property])) {
            return $property;
        }

        throw new Exception(sprintf('Property [ $%s ] not found on component [ %s ] ', $property, $this->component));
    }

    /**
     * Magic method to check if a property is set.
     *
     * @param string $property The name of the property to check.
     * @return bool True if the property is set and is public, false otherwise.
     * @throws Exception If the property does not exist or is not public.
     */
    public function __isset($property)
    {
        if (null !== $this->__get($property)) {
            throw new Exception(sprintf('Property [ $%s ] not found on component [ %s ] ', $property, $this->component));
        }

        return true;
    }
}
