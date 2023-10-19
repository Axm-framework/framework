<?php

if (!function_exists('ul')) {
    /**
     * Unordered List
     *
     * Generates an HTML unordered list from an single or
     * multi-dimensional array.
     * @param mixed $attributes HTML attributes string, array, object
     */
    function ul(array $list, $attributes = ''): string
    {
        return listHtml('ul', $list, $attributes);
    }
}

if (!function_exists('ol')) {
    /**
     * Ordered List
     *
     * Generates an HTML ordered list from an single or multi-dimensional array.
     * @param mixed $attributes HTML attributes string, array, object
     */
    function ol(array $list, $attributes = ''): string
    {
        return listHtml('ol', $list, $attributes);
    }
}

if (!function_exists('listHtml')) {
    /**
     * Generates the list Html
     *
     * Generates an HTML ordered list from an single or multi-dimensional array.
     * @param mixed $list
     * @param mixed $attributes string, array, object
     */
    function listHtml(string $type = 'ul', array $list = [], array $attributes = [], int $depth = 0): string
    {
        $indentation = str_repeat(' ', $depth);
        $output = $indentation . '<' . $type . createAttributes($attributes) . ">\n";

        foreach ($list as $key => $value) {
            $output .= $indentation . '  <li>';

            if (!is_array($value)) {
                $output .= $value;
            } else {
                $output .= $key . "\n" . listHtml($type, $value, [], $depth + 4);
            }

            $output .= "</li>\n";
        }

        $output .= $indentation . '</' . $type . ">\n";

        return $output;
    }
}


if (!function_exists('createAttributes')) {
    /**
     * 
     */
    function createAttributes(array $attributes): string
    {
        $output = '';

        foreach ($attributes as $name => $value) {
            $output .= ' ' . $name . '="' . htmlspecialchars($value) . '"';
        }

        return $output;
    }
}

if (!function_exists('img')) {
    /**
     * Image
     *
     * Generates an image element
     * @param array|string        $src        Image source URI, or array of attributes and values
     * @param bool                $indexPage  Whether to treat $src as a routed URI string
     * @param array|object|string $attributes Additional HTML attributes
     */
    function img(string $src = '', bool $indexPage = false, array $attributes = []): string
    {
        if (!isset($attributes['src'])) {
            $attributes['src'] = $src;
        }
        if (!isset($attributes['alt'])) {
            $attributes['alt'] = '';
        }

        $img = '<img';

        // Check for a relative URI
        if (!preg_match('#^([a-z]+:)?//#i', $attributes['src']) && strpos($attributes['src'], 'data:') !== 0) {
            $img .= ' src="' . ($indexPage ? baseUrl($attributes['src']) : normalizeUrlPath('baseURL') . $attributes['src']) . '"';
            unset($attributes['src']);
        }

        // Append any other values
        $img .= createAttributes($attributes) . ">\n";


        return $img . ' />';
    }
}

if (!function_exists('imgData')) {
    /**
     * Image (data)
     *
     * Generates a src-ready string from an image using the "data:" protocol
     * @param string      $path Image source path
     * @param string|null $mime MIME type to use, or null to guess
     */
    function imgData(string $path, ?string $mime = null): string
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException("File not found or cannot be read: $path");
        }

        // Read file contents
        $data = file_get_contents($path);

        // Encode as base64
        $data = base64_encode($data);

        // Determine the MIME type (Fallback to JPEG)
        $mime = $mime ?? mime_content_type($path) ?? 'image/jpeg';

        return 'data:' . $mime . ';base64,' . $data;
    }
}

if (!function_exists('doctype')) {
    /**
     * Doctype
     *
     * Generates a page document type declaration
     * Examples of valid options: html5, xhtml-11, xhtml-strict, xhtml-trans,
     * xhtml-frame, html4-strict, html4-trans, and html4-frame.
     * All values are saved in the doctypes config file.
     * @param string $type The doctype to be generated
     */
    function doctype(string $type = 'html5'): string
    {
        $doctypes = [
            'html5'               => '<!DOCTYPE html>',
            'html4-strict'        => '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
            'html4-transitional'  => '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
            'xhtml1-strict'       => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
            'xhtml1-transitional' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
        ];

        if (isset($doctypes[$type])) {
            return $doctypes[$type];
        } else {
            throw new InvalidArgumentException("Invalid doctype type: $type");
        }
    }
}

if (!function_exists('scriptTag')) {
    /**
     * Script
     *
     * Generates link to a JS file
     * @param mixed $src       Script source or an array
     * @param bool  $indexPage Should indexPage be added to the JS path
     */
    function scriptTag($src = '', bool $indexPage = false): string
    {
        $attributes = [];

        if (!is_array($src)) {
            $src = ['src' => $src];
        }

        foreach ($src as $key => $value) {
            if ($key === 'src' && !preg_match('#^([a-z]+:)?//#i', $value)) {
                if ($indexPage === true) {
                    $value = baseUrl($value);
                } else {
                    $value = normalizeUrlPath('baseURL') . $value;
                }
            }

            $attributes[] = "$key=\"$value\"";
        }

        return '<script ' . implode(' ', $attributes) . ' type="text/javascript"></script>';
    }
}

if (!function_exists('linkTag')) {
    /**
     * Link
     *
     * Generates link to a CSS file
     * @param mixed $href      Stylesheet href or an array
     * @param bool  $indexPage should indexPage be added to the CSS path.
     */
    function linkTag($href = '', string $rel = 'stylesheet', string $type = 'text/css', string $title = '', string $media = '', bool $indexPage = false, string $hreflang = ''): string
    {
        $attributes = [];

        // Extract fields if needed
        if (is_array($href)) {
            $rel       = $href['rel'] ?? $rel;
            $type      = $href['type'] ?? $type;
            $title     = $href['title'] ?? $title;
            $media     = $href['media'] ?? $media;
            $hreflang  = $href['hreflang'] ?? '';
            $indexPage = $href['indexPage'] ?? $indexPage;
            $href      = $href['href'] ?? '';
        }

        if (!preg_match('#^([a-z]+:)?//#i', $href)) {
            if ($indexPage === true) {
                $href = baseUrl($href);
            } else {
                $href = normalizeUrlPath('baseURL') . $href;
            }
        }

        $attributes[] = "href=\"$href\"";

        if ($hreflang !== '') {
            $attributes[] = "hreflang=\"$hreflang\"";
        }

        $attributes[] = "rel=\"$rel\"";

        if (!in_array($rel, ['alternate', 'canonical'], true)) {
            $attributes[] = "type=\"$type\"";
        }

        if ($media !== '') {
            $attributes[] = "media=\"$media\"";
        }

        if ($title !== '') {
            $attributes[] = "title=\"$title\"";
        }

        return '<link ' . implode(' ', $attributes) . '>';
    }
}

if (!function_exists('video')) {
    /**
     * Video
     *
     * Generates a video element to embed videos. The video element can
     * contain one or more video sources
     * @param mixed  $src                Either a source string or an array of sources
     * @param string $unsupportedMessage The message to display if the media tag is not supported by the browser
     * @param string $attributes         HTML attributes
     */
    function video($src, string $unsupportedMessage = '', string $attributes = '', array $tracks = [], bool $indexPage = false): string
    {
        if (is_array($src)) {
            return _media('video', $src, $unsupportedMessage, $attributes, $tracks);
        }

        $video = '<video';

        if (_hasProtocol($src)) {
            $video .= ' src="' . $src . '"';
        } elseif ($indexPage === true) {
            $video .= ' src="' . baseUrl($src) . '"';
        } else {
            $video .= ' src="' . normalizeUrlPath('baseURL') . $src . '"';
        }

        if ($attributes !== '') {
            $video .= ' ' . $attributes;
        }

        $video .= ">\n";

        foreach ($tracks as $track) {
            $video .= _indent() . $track . "\n";
        }

        if (!empty($unsupportedMessage)) {
            $video .= _indent()
                . $unsupportedMessage
                . "\n";
        }

        return $video . "</video>\n";
    }
}

if (!function_exists('audio')) {
    /**
     * Audio
     *
     * Generates an audio element to embed sounds
     * @param mixed  $src                Either a source string or an array of sources
     * @param string $unsupportedMessage The message to display if the media tag is not supported by the browser.
     * @param string $attributes         HTML attributes
     */
    function audio($src, string $unsupportedMessage = '', string $attributes = '', array $tracks = [], bool $indexPage = false): string
    {
        if (is_array($src)) {
            return _media('audio', $src, $unsupportedMessage, $attributes, $tracks);
        }

        $audio = '<audio';

        if (_hasProtocol($src)) {
            $audio .= ' src="' . $src . '"';
        } elseif ($indexPage === true) {
            $audio .= ' src="' . baseUrl($src) . '"';
        } else {
            $audio .= ' src="' . normalizeUrlPath('baseURL') . $src . '"';
        }

        if ($attributes !== '') {
            $audio .= ' ' . $attributes;
        }

        $audio .= '>';

        foreach ($tracks as $track) {
            $audio .= "\n" . _indent() . $track;
        }

        if (!empty($unsupportedMessage)) {
            $audio .= "\n" . _indent() . $unsupportedMessage . "\n";
        }

        return $audio . "</audio>\n";
    }
}

if (!function_exists('_media')) {
    /**
     * Generate media based tag
     *
     * @param string $unsupportedMessage The message to display if the media tag is not supported by the browser.
     */
    function _media(string $name, array $types = [], string $unsupportedMessage = '', string $attributes = '', array $tracks = []): string
    {
        $media = '<' . $name;

        if (empty($attributes)) {
            $media .= '>';
        } else {
            $media .= ' ' . $attributes . '>';
        }

        $media .= "\n";

        foreach ($types as $option) {
            $media .= _indent() . $option . "\n";
        }

        foreach ($tracks as $track) {
            $media .= _indent() . $track . "\n";
        }

        if (!empty($unsupportedMessage)) {
            $media .= _indent() . $unsupportedMessage . "\n";
        }

        return $media . ('</' . $name . ">\n");
    }
}

if (!function_exists('source')) {
    /**
     * Source
     *
     * Generates a source element that specifies multiple media resources
     * for either audio or video element
     * @param string $src        The path of the media resource
     * @param string $type       The MIME-type of the resource with optional codecs parameters
     * @param string $attributes HTML attributes
     */
    function source(string $src, string $type = 'unknown', string $attributes = '', bool $indexPage = false): string
    {
        if (!filter_var($src, FILTER_VALIDATE_URL) && !parse_url($src, PHP_URL_SCHEME)) {
            $src = $indexPage === true ? baseUrl($src) : normalizeUrlPath('baseURL', $src);
        }

        $sourceAttributes = [];
        $sourceAttributes[] = 'src="' . htmlspecialchars($src, ENT_QUOTES) . '"';
        $sourceAttributes[] = 'type="' . htmlspecialchars($type, ENT_QUOTES) . '"';

        if (!empty($attributes)) {
            $sourceAttributes[] = $attributes;
        }

        return '<source ' . implode(' ', $sourceAttributes) . ' />';
    }
}

if (!function_exists('track')) {
    /**
     * Track
     *
     * Generates a track element to specify timed tracks. The tracks are
     * formatted in WebVTT format.
     * @param string $src The path of the .VTT file
     */
    function track(string $src, string $kind, string $srcLanguage, string $label): string
    {
        $attributes = [];
        $attributes[] = 'src="' . $src . '"';
        $attributes[] = 'kind="' . $kind . '"';
        $attributes[] = 'srclang="' . $srcLanguage . '"';
        $attributes[] = 'label="' . $label . '"';

        return '<track ' . implode(' ', $attributes) . ' />';
    }
}


if (!function_exists('param')) {
    /**
     * Param
     *
     * Generates a param element that defines parameters
     * for the object element.
     * @param string $name       The name of the parameter
     * @param string $value      The value of the parameter
     * @param string $type       The MIME-type
     * @param string $attributes HTML attributes
     */
    function param(string $name, string $value, string $type = 'ref', string $attributes = ''): string
    {
        $paramAttributes = [];
        $paramAttributes[] = 'name="' . $name . '"';
        $paramAttributes[] = 'type="' . $type . '"';
        $paramAttributes[] = 'value="' . $value . '"';

        if (!empty($attributes)) {
            $paramAttributes[] = $attributes;
        }

        return '<param ' . implode(' ', $paramAttributes) . ' />';
    }
}

if (!function_exists('embed')) {
    /**
     * Embed
     *
     * Generates an embed element
     * @param string $src        The path of the resource to embed
     * @param string $type       MIME-type
     * @param string $attributes HTML attributes
     */
    function embed(string $src, string $type = 'unknown', string $attributes = '', bool $indexPage = false): string
    {
        if (!filter_var($src, FILTER_VALIDATE_URL) && !parse_url($src, PHP_URL_SCHEME)) {
            $src = $indexPage === true ? baseUrl($src) : normalizeUrlPath('baseURL', $src);
        }

        $embedAttributes = [];
        $embedAttributes[] = 'src="' . htmlspecialchars($src, ENT_QUOTES) . '"';
        $embedAttributes[] = 'type="' . htmlspecialchars($type, ENT_QUOTES) . '"';

        if (!empty($attributes)) {
            $embedAttributes[] = $attributes;
        }

        return '<embed ' . implode(' ', $embedAttributes) . " />\n";
    }
}


if (!function_exists('_indent')) {
    /**
     * Provide space indenting.
     */
    function _indent(int $depth = 2, string $character = ' '): string
    {
        return str_repeat($character, $depth);
    }
}

if (!function_exists('normalizeUrlPath')) {
    /**
     * 
     */
    function normalizeUrlPath(string $item, string $baseURL = ROOT_PATH): string
    {
        $baseURL = rtrim($baseURL, '/\\');
        $item    = ltrim($item, '/\\');

        return $baseURL . '/' . $item;
    }
}


if (!function_exists('_hasProtocol')) {
    /**
     * 
     */
    function _hasProtocol(string $src, bool $indexPage = false): bool
    {
        if (!filter_var($src, FILTER_VALIDATE_URL) && !parse_url($src, PHP_URL_SCHEME)) {
            if ($indexPage) {
                $src = baseUrl($src);
            } else {
                $src = normalizeUrlPath('baseURL', $src);
            }
        }

        return true;
    }
}


