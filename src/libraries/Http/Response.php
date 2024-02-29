<?php

namespace Http;

use Http\ResponseTrait;
use RuntimeException;

/**
 * Class Response
 * 
 * Handles HTTP responses.
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Http
 */
class Response
{
	use ResponseTrait;

	/**
	 * Counter to prevent cyclic redirections.
	 */
	private int $cyclic = 0;

	/**
	 * HTTP response message.
	 * @var string|null
	 */
	private $message;

	protected $headers = [];
	protected $content;


	/**
	 * Abort the execution with a specific HTTP status code, message, and headers.
	 *
	 * @param int $code HTTP status code.
	 * @param string $message Custom message.
	 * @param array $headers Associative array of headers (name => value).
	 * @throws \Exception If an invalid HTTP status code is provided.
	 * @return void
	 */
	public function abort(int $code, string $message = '', array $headers = []): void
	{
		$this->setStatusCode($code, $message)
			->setHeaders($headers)
			->setContent('')
			->send();
	}

	/**
	 * Reload the page using JavaScript.
	 * @return mixed
	 */
	public function reload()
	{
		$script = <<<JS
			<script>location.reload();</script>
		JS;
		return $this->setContent($script);
	}

	/**
	 * Perform redirection based on the provided page URL or reload the current page.
	 *
	 * @param string|null $page URL to redirect to, or null to reload the current page.
	 * @param int $maxRedirects Maximum number of allowed redirects to prevent cyclic routing.
	 * @throws RuntimeException If the URL provided is invalid or cyclic routing is detected.
	 * @return void
	 */
	private function redirector(?string $page = null, int $maxRedirects = 10)
	{
		return match (true) {
			is_null($page)                          => $this->reload(),
			!filter_var($page, FILTER_VALIDATE_URL) => throw new RuntimeException('The URL provided is invalid.'),
			++$this->cyclic > $maxRedirects         => throw new RuntimeException('Cyclic routing has been detected. This may cause stability problems.'),
			default                                 => $this->setHeader('Location', $page),
		};
	}

	/**
	 * Redirect to a specified page.
	 *
	 * @param string|null $page URL to redirect to, or null to reload the current page.
	 * @throws RuntimeException If the URL provided is invalid or cyclic routing is detected.
	 * @return void
	 */
	public function redirect(?string $page = null): void
	{
		$this->redirector(
			$page !== null && (!str_contains($page, 'http://')
				&& !str_contains($page, 'https://')) ?
				go($page) : $page
		);

		exit;
	}

	/**
	 * The file method sets the content type and content of the response
	 * object based on the contents of a file.
	 */
	public function file($filePath, $additionalHeaders = [])
	{
		if (!file_exists($filePath)) throw new \Exception('File does not exist.');

		// Sets the content type according to the file extension
		$mimeType = mime_content_type($filePath);
		$contents = file_get_contents($filePath);
		$this->withHeaders(
			[
				'Content-Type' => $mimeType,
				$additionalHeaders
			]
		)->setContent($contents);

		return $this;
	}

	/**
	 * Prepares a Response object with the given content, headers, status, MIME type, andset.
	 *
	 * @param string $content response content.
	 * @param array $headers The response headers.
	 * @param int $status The HTTP status code.
	 * @param string $mimeType The MIME type of the response content.
	 * @param string $charset The character set of the response content.
	 * @return Response The prepared Response object.
	 */
	public function make(string $content = '', array $headers = [], int $status = 200, string $mimeType = 'text/html', string $charset = 'utf-8'): Response
	{
		$this->status($status)
			->withHeaders($headers);

		$this->setContentType($mimeType, $charset)
			->setContent($content);

		return $this;
	}

	/**
	 * Sends the prepared Response object.
	 * @return void
	 */
	public function send(): void
	{

		if (func_num_args() > 0) {
			$this->make(...func_get_args());
		}

		$this->setHeaders();
		echo $this->getContent();

		exit;
	}

	/**
	 * Set the HTTP status code
	 *
	 * @param int $code The HTTP status code
	 * @return $this
	 */
	public function status(int $code): self
	{
		if ($code < 100 || $code > 599) {
			throw new \Exception('Invalid HTTP status code');
		}

		http_response_code($code);

		return $this;
	}

	/**
	 * Set the HTTP status code and optionally a message.
	 *
	 * @param int $code HTTP status code.
	 * @param string|null $message Response message.
	 * @return $this
	 */
	public function setStatusCode(int $code, ?string $message = null): self
	{
		$this->status($code);
		$this->message = $message ?: $this->getMessageFromCode($code);

		return $this;
	}

	/**
	 * Add headers to the response
	 *
	 * @param array $headers An array of header key-value pairs
	 * @return $this
	 */
	public function withHeaders(array $headers): self
	{
		$this->headers = array_merge($this->headers, $headers);

		return $this;
	}

	/**
	 * Store errors in the session
	 *
	 * @param array $errors An array of error messages
	 * @return $this
	 */
	public function withErrors($errors): self
	{
		// Stores errors in the session
		$_SESSION['_errors'] = $errors;

		return $this;
	}

	/**
	 * Sets a cookie with the given parameters
	 *
	 * @param string $name The name of the cookie
	 * @param string $value The value of the cookie
	 * @param int $minutes The number of minutes until the cookie expires
	 * @param string $path The path for the cookie
	 * @param string|null $domain The domain for the cookie
	 * @param bool $secure Whether the cookie should only be sent over HTTPS
	 * @param bool $httpOnly Whether the cookie
	 */
	protected function withCookie(string $name, string $value, int $minutes = 0, string $path = '/', string|null $domain = null, bool $secure = false, bool $httpOnly = true): self
	{
		// Build the cookie string manually
		$cookie = urlencode($name) . '=' . urlencode($value);

		if ($minutes > 0) {
			$expire = time() + ($minutes * 60);
			$cookie .= '; expires=' . gmdate('D, d M Y H:i:s T', $expire);
		}

		$cookie .= '; path=' . $path;
		if ($domain) {
			$cookie .= '; domain=' . $domain;
		}

		if ($secure) {
			$cookie .= '; secure';
		}

		if ($httpOnly) {
			$cookie .= '; HttpOnly';
		}

		// Adds the cookie string to the headers
		$this->headers['Set-Cookie'] = $cookie;

		return $this;
	}

	/**
	 * Turn on gzip compression for the response
	 */
	public function gzip(): self
	{
		ob_start('ob_gzhandler');

		return $this;
	}

	/**
	 * Sets the content encoding of the response
	 *
	 * @param string $encoding The desired encoding
	 * @return $this The current response object
	 */
	public function encode($encoding): self
	{
		$this->withHeaders(['Content-Encoding' => $encoding]);
		return $this;
	}

	/**
	 * Set the cache control headers.
	 */
	public function withCache(int $maxAge): self
	{
		$this->withHeaders(['Cache-Control', "max-age=$maxAge"]);
		return $this;
	}

	/**
	 * The expireCache method sets the Cache-Control header of the response object
	 * to a max age of 0, effectively expiring the cache.
	 */
	public function expireCache(): self
	{
		$this->withCache(0);

		return $this;
	}

	/**
	 * Download a file and send it as a response
	 *
	 * @param string $filePath The path to the file to download
	 * @param string $nafileNameme The name of the file to display to the user
	 * @param array $additionalHeaders Additional headers to send with the response
	 * @param string $disposition The disposition type (either 'attachment' or 'inline')
	 * @throws \Exception If the file does not exist
	 */
	public function download(string $filePath, string $fileName = null, array $additionalHeaders = [], string $disposition = 'attachment'): self
	{
		if (!file_exists($filePath . $fileName)) throw new \Exception('File does not exist.');

		$fileName = $fileName ?? basename($filePath);
		$mimeType = mime_content_type($filePath);
		$content  = file_get_contents($filePath);

		$headers = [
			'Content-Type' => $mimeType,
			'Content-Disposition' => "$disposition; filename=\"$fileName\""
		];

		// Merge additional headers
		$headers = array_merge($headers, $additionalHeaders);

		// Set headers and content
		$this->withHeaders($headers)
			->setContent($content);

		return $this;
	}

	/**
	 * Output content encoded as a JSON string.
	 *
	 * @param mixed $content Content to be encoded.
	 * @param int $statusCode HTTP status code.
	 * @param string $charset Character set.
	 * @throws \RuntimeException If content cannot be converted to JSON.
	 * @return void
	 */
	public function toJson($content, int $statusCode = 200, string $charset = 'utf-8')
	{
		if ($jsonContent = json_encode($content)) {
			return $this->send($jsonContent, [], $statusCode, 'application/json', $charset);
		}

		throw new \RuntimeException('Failed to convert content to JSON.');
	}

	/**
	 * Convert JSON content to an array.
	 *
	 * @param string $content JSON content.
	 * @return array
	 */
	public function toArray(string $content)
	{
		return (array) json_decode($content);
	}

	/** 
	 * Returns response in XML format 
	 * 
	 * @param mixed $data Data to be converted to XML 
	 * @param int $statusCode HTTP status code 
	 * @param string $charset Character set 
	 * @throws \RuntimeException If unable to convert content to XML 
	 **/
	public function toXml($data, int $statusCode = 200, string $charset = 'utf-8'): void
	{
		$xmlContent = $this->convertToXml($data);
		if ($xmlContent !== null) {
			$this->send($xmlContent, [], $statusCode, 'application/xml', $charset);
		}

		throw new \RuntimeException('The content could not be converted to XML.');
	}

	/** 
	 * Converts data to XML format 
	 * 
	 * @param mixed $data Data to convert 
	 * @return string|null XML content or null if conversion fails 
	 **/
	private function convertToXml($data): ?string
	{
		$xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root></root>');
		array_walk_recursive($data, [$xml, 'addChild']);
		return $xml->asXML();
	}

	/** 
	 * Returns the response in CSV format 
	 * 
	 * @param array $data Data to convert to CSV 
	 * @param string $fileName Name of the download CSV file 
	 * @param int $statusCode HTTP status code 
	 * @throws \RuntimeException If CSV content cannot be generated 
	 **/
	public function toCsv(array $data, string $fileName = 'data.csv', int $statusCode = 200): void
	{
		$csvContent = $this->convertToCsv($data);
		if ($csvContent !== null) {
			$this->downloadCsv($csvContent, $fileName, $statusCode);
		}

		throw new \RuntimeException('CSV content could not be generated.');
	}

	/**
	 * Converts data to CSV format.
	 *
	 * @param array $data Data to be converted.
	 * @return string|null CSV content or null if conversion fails.
	 */
	private function convertToCsv(array $data): ?string
	{
		$output = fopen('php://temp', 'w');
		foreach ($data as $row) {
			fputcsv($output, $row);
		}
		rewind($output);
		$csvContent = stream_get_contents($output);
		fclose($output);

		return $csvContent;
	}

	/**
	 * Download the CSV content as a response file.
	 *
	 * @param string $content CSV content.
	 * @param string $fileName Name of the download CSV file.
	 * @param int $statusCode HTTP status code
	 */
	private function downloadCsv(string $content, string $fileName, int $statusCode): void
	{
		$this->download($content, $fileName, [], 'attachment', $statusCode);
	}

	/**
	 * Returns the HTTP 1.1 message corresponding to the provided code.
	 *
	 * @param int $int HTTP status code.
	 * @return string|null
	 */
	public function getMessageFromCode($int)
	{
		if (isset(self::HTTP_MESSAGES[$int]))
			return self::HTTP_MESSAGES[$int];
		else
			return null;
	}

	/**
	 * Set custom headers for the response.
	 *
	 * @param array $headers Associative array of headers (name => value).
	 * @return $this
	 */
	public function setHeaders(): self
	{
		if (func_num_args() > 0) {
			$this->withHeaders(...func_get_args());
		}

		array_walk($this->headers, function ($value, $name) {
			$value = filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$this->setHeader($name, $value);
		});

		return $this;
	}

	/**
	 * Set custom headers for the response.
	 *
	 * @param array $headers Associative array of headers (name => value).
	 * @return $this
	 */
	public function setHeader(string $name, string $value): self
	{
		header(sprintf('%s: %s', $name, $value));
		return $this;
	}

	/**
	 * Get the headers array
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * Set the content of the response.
	 *
	 * @param string $content The content to set.
	 * @return $this
	 */
	public function setContent(string $content = null): self
	{
		$this->content = $content;

		return $this;
	}

	/**
	 * Set the content of the response.
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * Set the content type of the response
	 */
	public function setContentType(string $mimeType, string $charset = 'utf8')
	{
		$this->withHeaders(['Content-Type' => $mimeType . ';charset=' . $charset]);
		return $this;
	}

	/**
	 * This method returns an array of all the MIME types supported by the class.
	 * @return array An array of all the MIME types supported by the class.
	 */
	public function getMimes(string $mimeTypes): array
	{
		$mimeTypes = [];
		foreach (self::MIMES_TYPES as $ext => $atributes) {
			foreach ($atributes as $atribute) {
				$mimeTypes[$atribute] = $atribute;
			}
		}

		return $mimeTypes;
	}
}
