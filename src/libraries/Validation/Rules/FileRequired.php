<?php

declare(strict_types=1);

namespace Validation\Rules;

/**
 * Class FileRequired

 *
 * Validates if a file input field is required and whether a file has been uploaded.
 * @package Axm\Validation\Rules
 */
class FileRequired
{
    /**
     * Validate if a file input field is required and a file has been uploaded.
     *
     * @param string $rule      The validation rule being applied (e.g., 'required').
     * @param string|null $attribute The name of the file input field.
     *
     * @return bool True if the file input is required and a file has been uploaded; otherwise, false.
     */
    public function validate($input): bool
    {
        $rule = $input['rule'];
        $attribute = $input['valueData'];

        // Check if the rule is 'required', the attribute is not empty, and the file exists in the request.
        if ($rule === 'required' && !empty($attribute) && isset($_FILES[$attribute])) {
            return !empty($_FILES[$attribute]['tmp_name']);
        }

        return false;
    }

    /**
     * Get the error message for file validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The file is required but was not uploaded.';
    }
}
