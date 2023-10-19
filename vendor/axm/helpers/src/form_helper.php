<?php

if (!function_exists('formOpen')) {
    /**
     * Crea un formulario HTML con token CSRF y devuelve un objeto HTML.
     *
     * @param string $method Método del formulario (get o post).
     * @param string $action URL de destino del formulario.
     * @param array $attributes Atributos adicionales del formulario.
     * @return self Objeto HTML con el formulario.
     * @throws AxmException Si el método del formulario no es válido.
     */
    function formOpen(string $method, string $action, array $attributes = [])
    {
        // Validar valores de entrada
        $validMethods = ['get', 'post'];
        if (!in_array(strtolower($method), $validMethods)) {
            throw new AxmException('Invalid form method');
        }

        // Crear etiqueta de formulario
        $formAttrs = array_merge(['action' => $action, 'method' => strtolower($method)], $attributes);
        $formTag   = createHtmlTag('form', $formAttrs, true);

        // Agregar token CSRF
        $csrfToken = Axm::app()->getCsrfToken();
        $csrfTag   = createHtmlTag('input', ['type' => 'hidden', 'name' => '_csrf_token_', 'value' => $csrfToken], false);
        $_SESSION['_csrf_token_'] = $csrfToken;

        $html = new self();
        $html->addElement($formTag . $csrfTag);

        return $html;
    }
}

if (!function_exists('end')) {
    function end(): string
    {
        return '</form>' . PHP_EOL;
    }
}


if (!function_exists('createHtmlTag')) {
    function createHtmlTag($tagName, $attributes = [], $closingTag = true): string
    {
        $tag = '<' . $tagName;
        $tag .= attrs($attributes);

        if ($closingTag && $tagName !== 'form') {
            $tag .= '></' . $tagName . '>';
        } else {
            $tag .= ' />';
        }

        return $tag;
    }
}

if (!function_exists('formHidden')) {
    /**
     * Hidden Input Field
     *
     * Generates hidden fields. You can pass a simple key/value string or
     * an associative array with multiple values.
     *
     * @param array|string $name  Field name or associative array to create multiple fields
     * @param array|string $value Field value
     */
    function formHidden($name, $value = '', bool $recursing = false): string
    {
        static $form;

        if ($recursing === false) {
            $form = "\n";
        }

        if (is_array($name)) {
            foreach ($name as $key => $val) {
                formHidden($key, $val, true);
            }

            return $form;
        }

        if (!is_array($value)) {
            $form .= formInput($name, $value, '', 'hidden');
        } else {
            foreach ($value as $k => $v) {
                $k = is_int($k) ? '' : $k;
                formHidden($name . '[' . $k . ']', $v, true);
            }
        }

        return $form;
    }
}

if (!function_exists('formInput')) {
    /**
     * Text Input Field. If 'type' is passed in the $type field, it will be
     * used as the input type, for making 'email', 'phone', etc input fields.
     *
     * @param mixed $data
     * @param mixed $extra
     */
    function formInput($data = '', string $value = '', $extra = '', string $type = 'text'): string
    {
        $defaults = [
            'type'  => $type,
            'name'  => is_array($data) ? '' : $data,
            'value' => $value,
        ];

        return '<input ' . parseFormAttributes($data, $defaults) . stringifyAttributes($extra) . " />\n";
    }
}

if (!function_exists('formPassword')) {
    /**
     * Password Field
     *
     * Identical to the input function but adds the "password" type
     *
     * @param mixed $data
     * @param mixed $extra
     */
    function formPassword($data = '', string $value = '', $extra = ''): string
    {
        if (!is_array($data)) {
            $data = ['name' => $data];
        }
        $data['type'] = 'password';

        return formInput($data, $value, $extra);
    }
}

if (!function_exists('formUpload')) {
    /**
     * Upload Field
     *
     * Identical to the input function but adds the "file" type
     *
     * @param mixed $data
     * @param mixed $extra
     */
    function formUpload($data = '', string $value = '', $extra = ''): string
    {
        $defaults = [
            'type' => 'file',
            'name' => '',
        ];

        if (!is_array($data)) {
            $data = ['name' => $data];
        }

        $data['type'] = 'file';

        return '<input ' . parseFormAttributes($data, $defaults) . stringifyAttributes($extra) . " />\n";
    }
}

if (!function_exists('formTextarea')) {
    /**
     * Textarea field
     *
     * @param mixed $data
     * @param mixed $extra
     */
    function formTextarea($data = '', string $value = '', $extra = ''): string
    {
        $defaults = [
            'name' => is_array($data) ? '' : $data,
            'cols' => '40',
            'rows' => '10',
        ];
        if (!is_array($data) || !isset($data['value'])) {
            $val = $value;
        } else {
            $val = $data['value'];
            unset($data['value']); // textareas don't use the value attribute
        }

        // Unsets default rows and cols if defined in extra field as array or string.
        if ((is_array($extra) && array_key_exists('rows', $extra)) || (is_string($extra) && stripos(preg_replace('/\s+/', '', $extra), 'rows=') !== false)) {
            unset($defaults['rows']);
        }

        if ((is_array($extra) && array_key_exists('cols', $extra)) || (is_string($extra) && stripos(preg_replace('/\s+/', '', $extra), 'cols=') !== false)) {
            unset($defaults['cols']);
        }

        return '<textarea ' . rtrim(parseFormAttributes($data, $defaults)) . stringifyAttributes($extra) . '>'
            . htmlspecialchars($val)
            . "</textarea>\n";
    }
}

if (!function_exists('formMultiselect')) {
    /**
     * Multi-select menu
     *
     * @param mixed $name
     * @param mixed $extra
     */
    function formMultiselect($name = '', array $options = [], array $selected = [], $extra = ''): string
    {
        $extra = stringifyAttributes($extra);

        if (stripos($extra, 'multiple') === false) {
            $extra .= ' multiple="multiple"';
        }

        return formDropdown($name, $options, $selected, $extra);
    }
}

if (!function_exists('formDropdown')) {
    /**
     * Drop-down Menu
     *
     * @param mixed $data
     * @param mixed $options
     * @param mixed $selected
     * @param mixed $extra
     */
    function formDropdown($data = '', $options = [], $selected = [], $extra = ''): string
    {
        $defaults = [];
        if (is_array($data)) {
            if (isset($data['selected'])) {
                $selected = $data['selected'];
                unset($data['selected']); // select tags don't have a selected attribute
            }
            if (isset($data['options'])) {
                $options = $data['options'];
                unset($data['options']); // select tags don't use an options attribute
            }
        } else {
            $defaults = ['name' => $data];
        }

        if (!is_array($selected)) {
            $selected = [$selected];
        }
        if (!is_array($options)) {
            $options = [$options];
        }

        // If no selected state was submitted we will attempt to set it automatically
        if (empty($selected)) {
            if (is_array($data)) {
                if (isset($data['name'], $_POST[$data['name']])) {
                    $selected = [$_POST[$data['name']]];
                }
            } elseif (isset($_POST[$data])) {
                $selected = [$_POST[$data]];
            }
        }

        // Standardize selected as strings, like the option keys will be
        foreach ($selected as $key => $item) {
            $selected[$key] = (string) $item;
        }

        $extra    = stringifyAttributes($extra);
        $multiple = (count($selected) > 1 && stripos($extra, 'multiple') === false) ? ' multiple="multiple"' : '';
        $form     = '<select ' . rtrim(parseFormAttributes($data, $defaults)) . $extra . $multiple . ">\n";

        foreach ($options as $key => $val) {
            // Keys should always be strings for strict comparison
            $key = (string) $key;

            if (is_array($val)) {
                if (empty($val)) {
                    continue;
                }

                $form .= '<optgroup label="' . $key . "\">\n";

                foreach ($val as $optgroupKey => $optgroupVal) {
                    // Keys should always be strings for strict comparison
                    $optgroupKey = (string) $optgroupKey;

                    $sel = in_array($optgroupKey, $selected, true) ? ' selected="selected"' : '';
                    $form .= '<option value="' . htmlspecialchars($optgroupKey) . '"' . $sel . '>' . $optgroupVal . "</option>\n";
                }

                $form .= "</optgroup>\n";
            } else {
                $form .= '<option value="' . htmlspecialchars($key) . '"'
                    . (in_array($key, $selected, true) ? ' selected="selected"' : '') . '>'
                    . $val . "</option>\n";
            }
        }

        return $form . "</select>\n";
    }
}

if (!function_exists('formCheckbox')) {
    /**
     * Checkbox Field
     *
     * @param mixed $data
     * @param mixed $extra
     */
    function formCheckbox($data = '', string $value = '', bool $checked = false, $extra = ''): string
    {
        $defaults = [
            'type'  => 'checkbox',
            'name'  => (!is_array($data) ? $data : ''),
            'value' => $value,
        ];

        if (is_array($data) && array_key_exists('checked', $data)) {
            $checked = $data['checked'];
            if ($checked === false) {
                unset($data['checked']);
            } else {
                $data['checked'] = 'checked';
            }
        }

        if ($checked === true) {
            $defaults['checked'] = 'checked';
        } elseif (isset($defaults['checked'])) {
            unset($defaults['checked']);
        }

        return '<input ' . parseFormAttributes($data, $defaults) . stringifyAttributes($extra) . " />\n";
    }
}

if (!function_exists('formRadio')) {
    /**
     * Radio Button
     *
     * @param mixed $data
     * @param mixed $extra
     */
    function formRadio($data = '', string $value = '', bool $checked = false, $extra = ''): string
    {
        if (!is_array($data)) {
            $data = ['name' => $data];
        }
        $data['type'] = 'radio';

        return formCheckbox($data, $value, $checked, $extra);
    }
}

if (!function_exists('formSubmit')) {
    /**
     * Submit Button
     *
     * @param mixed $data
     * @param mixed $extra
     */
    function formSubmit($data = '', string $value = '', $extra = ''): string
    {
        return formInput($data, $value, $extra, 'submit');
    }
}

if (!function_exists('formReset')) {
    /**
     * Reset Button
     *
     * @param mixed $data
     * @param mixed $extra
     */
    function formReset($data = '', string $value = '', $extra = ''): string
    {
        return formInput($data, $value, $extra, 'reset');
    }
}

if (!function_exists('formButton')) {
    /**
     * Form Button
     *
     * @param mixed $data
     * @param mixed $extra
     */
    function formButton($data = '', string $content = '', $extra = ''): string
    {
        $defaults = [
            'name' => is_array($data) ? '' : $data,
            'type' => 'button',
        ];

        if (is_array($data) && isset($data['content'])) {
            $content = $data['content'];
            unset($data['content']); // content is not an attribute
        }

        return '<button ' . parseFormAttributes($data, $defaults) . stringifyAttributes($extra) . '>'
            . $content
            . "</button>\n";
    }
}

if (!function_exists('formLabel')) {
    /**
     * Form Label Tag
     *
     * @param string $labelText  The text to appear onscreen
     * @param string $id         The id the label applies to
     * @param array  $attributes Additional attributes
     */
    function formLabel(string $labelText = '', string $id = '', array $attributes = []): string
    {
        $label = '<label';

        if ($id !== '') {
            $label .= ' for="' . $id . '"';
        }

        if (is_array($attributes) && $attributes) {
            foreach ($attributes as $key => $val) {
                $label .= ' ' . $key . '="' . $val . '"';
            }
        }

        return $label . '>' . $labelText . '</label>';
    }
}

if (!function_exists('formDatalist')) {
    /**
     * Datalist
     *
     * The <datalist> element specifies a list of pre-defined options for an <input> element.
     * Users will see a drop-down list of pre-defined options as they input data.
     * The list attribute of the <input> element, must refer to the id attribute of the <datalist> element.
     */
    function formDatalist(string $name, string $value, array $options): string
    {
        $data = [
            'type'  => 'text',
            'name'  => $name,
            'list'  => $name . '_list',
            'value' => $value,
        ];

        $out = formInput($data) . "\n";

        $out .= "<datalist id='" . $name . '_list' . "'>";

        foreach ($options as $option) {
            $out .= "<option value='{$option}'>" . "\n";
        }

        return $out . ('</datalist>' . "\n");
    }
}

if (!function_exists('formFieldset')) {
    /**
     * Fieldset Tag
     *
     * Used to produce <fieldset><legend>text</legend>.  To close fieldset
     * use formFieldsetClose()
     *
     * @param string $legendText The legend text
     * @param array  $attributes Additional attributes
     */
    function formFieldset(string $legendText = '', array $attributes = []): string
    {
        $fieldset = '<fieldset' . stringifyAttributes($attributes) . ">\n";

        if ($legendText !== '') {
            return $fieldset . '<legend>' . $legendText . "</legend>\n";
        }

        return $fieldset;
    }
}

if (!function_exists('formFieldsetClose')) {
    /**
     * Fieldset Close Tag
     */
    function formFieldsetClose(string $extra = ''): string
    {
        return '</fieldset>' . $extra;
    }
}

if (!function_exists('formClose')) {
    /**
     * Form Close Tag
     */
    function formClose(string $extra = ''): string
    {
        return '</form>' . $extra;
    }
}

if (!function_exists('setValue')) {
    /**
     * Form Value
     *
     * Grabs a value from the POST array for the specified field so you can
     * re-populate an input field or textarea
     * @param string          $field      Field name
     * @param string|string[] $default    Default value
     * @param bool            $htmlEscape Whether to escape HTML special characters or not
     * @return string|string[]
     */
    function setValue(string $field, $default = '', bool $htmlEscape = true)
    {
        $request = app()->request;

        // Try any old input data we may have first
        $value = $request->getOldInput($field);

        if ($value === null) {
            $value = $request->getPost($field) ?? $default;
        }

        return ($htmlEscape) ? esc($value) : $value;
    }
}

if (!function_exists('setSelect')) {
    /**
     * Set Select
     *
     * Let's you set the selected value of a <select> menu via data in the POST array.
     * If Form Validation is active it retrieves the info from the validation class
     */
    function setSelect(string $field, string $value = '', bool $default = false): string
    {
        $request = app()->request;

        // Try any old input data we may have first
        $input = $request->getOldInput($field);

        if ($input === null) {
            $input = $request->getPost($field);
        }

        if ($input === null) {
            return ($default === true) ? ' selected="selected"' : '';
        }

        if (is_array($input)) {
            // Note: in_array('', array(0)) returns TRUE, do not use it
            foreach ($input as &$v) {
                if ($value === $v) {
                    return ' selected="selected"';
                }
            }

            return '';
        }

        return ($input === $value) ? ' selected="selected"' : '';
    }
}

if (!function_exists('setCheckbox')) {
    /**
     * Set Checkbox
     *
     * Let's you set the selected value of a checkbox via the value in the POST array.
     * If Form Validation is active it retrieves the info from the validation class
     */
    function setCheckbox(string $field, string $value = '', bool $default = false): string
    {
        $request = app()->request;

        // Try any old input data we may have first
        $input = $request->getOldInput($field);

        if ($input === null) {
            $input = $request->getPost($field);
        }

        if (is_array($input)) {
            // Note: in_array('', array(0)) returns TRUE, do not use it
            foreach ($input as &$v) {
                if ($value === $v) {
                    return ' checked="checked"';
                }
            }

            return '';
        }

        // Unchecked checkbox and radio inputs are not even submitted by browsers ...
        if ((string) $input === '0' || !empty($request->getPost()) || !empty(old($field))) {
            return ($input === $value) ? ' checked="checked"' : '';
        }

        return ($default === true) ? ' checked="checked"' : '';
    }
}

if (!function_exists('set_radio')) {
    /**
     * Set Radio
     *
     * Let's you set the selected value of a radio field via info in the POST array.
     * If Form Validation is active it retrieves the info from the validation class
     */
    function set_radio(string $field, string $value = '', bool $default = false): string
    {
        $request = app()->request;

        // Try any old input data we may have first
        $input = $request->getOldInput($field);
        if ($input === null) {
            $input = $request->getPost($field) ?? $default;
        }

        if (is_array($input)) {
            // Note: in_array('', array(0)) returns TRUE, do not use it
            foreach ($input as &$v) {
                if ($value === $v) {
                    return ' checked="checked"';
                }
            }

            return '';
        }

        // Unchecked checkbox and radio inputs are not even submitted by browsers ...
        $result = '';
        if ((string) $input === '0' || !empty($input = $request->getPost($field)) || !empty($input = old($field))) {
            $result = ($input === $value) ? ' checked="checked"' : '';
        }

        if (empty($result)) {
            $result = ($default === true) ? ' checked="checked"' : '';
        }

        return $result;
    }
}

if (!function_exists('parseFormAttributes')) {
    /**
     * Parse the form attributes
     *
     * Helper function used by some of the form helpers
     *
     * @param array|string $attributes List of attributes
     * @param array        $default    Default values
     */
    function parseFormAttributes($attributes, array $default): string
    {
        if (is_array($attributes)) {
            foreach (array_keys($default) as $key) {
                if (isset($attributes[$key])) {
                    $default[$key] = $attributes[$key];
                    unset($attributes[$key]);
                }
            }
            if (!empty($attributes)) {
                $default = array_merge($default, $attributes);
            }
        }

        $att = '';

        foreach ($default as $key => $val) {
            if (!is_bool($val)) {
                if ($key === 'value') {
                    $val = esc($val);
                } elseif ($key === 'name' && !strlen($default['name'])) {
                    continue;
                }
                $att .= $key . '="' . $val . '"' . ($key === array_key_last($default) ? '' : ' ');
            } else {
                $att .= $key . ' ';
            }
        }

        return $att;
    }
}
