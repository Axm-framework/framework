<?php

use Views\View;

if (!function_exists('raxm')) {

    /**
     * Initialize and use a Raxm component.
     *
     * This function is used to initialize and use a Raxm component within the application.
     * @param string $component The name of the Raxm component to initialize and use.
     * @return mixed The result of initializing and using the specified Raxm component.
     */
    function raxm(string $component)
    {
        // Get the Raxm instance from the application.
        $raxm = app('raxm');
        $names = $raxm::parserComponent($component);

        // Initialize and use the specified Raxm component.
        return $raxm::mountComponent(new $names, true);
    }
}

if (!function_exists('error')) {
    /**
     * Function error
     *
     * This function is used to retrieve an error message associated with a specific field.
     * @param string $field             The name of the field for which you want to retrieve an error message.
     * @param array|string $messages    An associative array of error messages where keys are field names and values are corresponding error messages. Alternatively, it can be a string representing a single error message.
     * @param string $defaultMessage    (Optional) A default error message to use if no message is found for the specified field in $messages.
     * @return string                   Returns the error message associated with the specified field. If no message is found for the field, it returns the default message or an empty string if no default message is provided.
     */
    if (!function_exists('error')) {

        function error(string $field, array|string $messages = [], string $defaultMessage = ''): string
        {
            if (is_string($messages)) {
                return $messages;
            }

            if (is_array($messages) && empty($messages[$field])) {
                return $defaultMessage;
            }

            return $messages[$field] ?? $defaultMessage;
        }
    }
}

if (!function_exists('raxmScripts')) {

	/**
	 * Enable the use of Raxm scripts and assets in the View.
	 *
	 * This function is used to enable the inclusion of Raxm scripts and assets in a View template.
	 * It sets a flag in the View class to indicate that Raxm assets should be included.
	 * @return bool True to enable Raxm scripts and assets in the View; false otherwise.
	 */
	function raxmScripts()
	{
		// Set a flag in the View class to enable Raxm scripts and assets.
		// return View::$raxmAssets = true;
	}
}
