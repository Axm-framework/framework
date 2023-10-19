<?php


if (!function_exists('wordLimiter')) {
    /**
     * Word Limiter
     *
     * Limits a string to X number of words.
     * @param string $endChar the end character. Usually an ellipsis
     */
    function wordLimiter(string $str, int $limit = 100, string $endChar = '&#8230;'): string
    {
        // Convertir caracteres HTML especiales en su representación de texto
        $str = htmlspecialchars_decode($str);

        // Eliminar etiquetas HTML
        $str = strip_tags($str);

        // Eliminar espacios en blanco adicionales
        $str = trim($str);

        // Dividir la cadena en palabras
        $words = preg_split('/\s+/', $str, $limit + 1);

        // Unir las palabras limitadas
        $limitedStr = implode(' ', array_slice($words, 0, $limit));

        // Agregar el carácter final si es necesario
        if (count($words) > $limit) {
            $limitedStr .= $endChar;
        }

        return $limitedStr;
    }
}

if (!function_exists('characterLimiter')) {
    /**
     * Character Limiter
     *
     * Limits the string based on the character count.  Preserves complete words
     * so the character count may not be exactly as specified.
     * @param string $endChar the end character. Usually an ellipsis
     */
    function characterLimiter(string $str, int $n = 500, string $endChar = '&#8230;'): string
    {
        // Eliminar espacios en blanco adicionales
        $str = trim(preg_replace('/\s+/', ' ', $str));

        if (mb_strlen($str) <= $n) {
            return $str;
        }

        $limitedStr = mb_substr($str, 0, $n);

        // Verificar si el último carácter es un espacio
        $lastChar = mb_substr($limitedStr, -1);
        if (ctype_space($lastChar)) {
            $limitedStr = trim($limitedStr);
        } else {
            // Retroceder hasta encontrar un espacio para evitar cortar una palabra
            $lastSpacePos = mb_strrpos($limitedStr, ' ');
            if ($lastSpacePos !== false) {
                $limitedStr = mb_substr($limitedStr, 0, $lastSpacePos);
            }
            $limitedStr = trim($limitedStr);
        }

        return $limitedStr . $endChar;
    }
}

if (!function_exists('asciiToEntities')) {
    /**
     * High ASCII to Entities
     *
     * Converts high ASCII text and MS Word special characters to character entities
     */
    function asciiToEntities(string $str): string
    {
        $out = '';

        $strLength = mb_strlen($str, 'UTF-8');

        for ($i = 0; $i < $strLength; $i++) {
            $char = mb_substr($str, $i, 1, 'UTF-8');
            $ordinal = ord($char);

            if ($ordinal < 128) {
                $out .= $char;
            } else {
                $out .= '&#' . $ordinal . ';';
            }
        }

        return $out;
    }
}

if (!function_exists('entitiesToAscii')) {
    /**
     * Entities to ASCII
     *
     * Converts character entities back to ASCII
     */
    function entitiesToAscii(string $str, bool $all = true): string
    {
        $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($all) {
            $str = htmlspecialchars_decode($str, ENT_QUOTES | ENT_HTML5);
        }

        return $str;
    }
}

if (!function_exists('wordCensor')) {
    /**
     * Word Censoring Function
     *
     * Supply a string and an array of disallowed words and any
     * matched words will be converted to #### or to the replacement
     * word you've submitted.
     * @param string $str         the text string
     * @param array  $censored    the array of censored words
     * @param string $replacement the optional replacement value
     */
    function wordCensor(string $str, array $censored, string $replacement = ''): string
    {
        if (empty($censored)) {
            return $str;
        }

        $pattern = '/\b(' . implode('|', array_map('preg_quote', $censored)) . ')\b/i';

        if ($replacement !== '') {
            return preg_replace($pattern, $replacement, $str);
        }

        return preg_replace_callback($pattern, function ($match) {
            return str_repeat('#', mb_strlen($match[0]));
        }, $str);
    }
}

if (!function_exists('highlightCode')) {
    /**
     * Code Highlighter
     *
     * Colorizes code strings
     * @param string $str the text string
     */
    function highlightCode(string $str): string
    {
        $str = str_replace(
            ['&lt;', '&gt;', '<?', '?>', '<%', '%>', '\\', '</script>'],
            ['<', '>', 'phptagopen', 'phptagclose', 'asptagopen', 'asptagclose', 'backslashtmp', 'scriptclose'],
            $str
        );

        // Use a custom code highlighter instead of highlight_string
        $str = highlight_custom_code($str);

        $str = preg_replace_callback(
            '/<span style="color: #([A-Z0-9]+)">&lt;\?php(&nbsp;| )/i',
            function ($matches) {
                return '<span style="color: #' . $matches[1] . '">&lt;?php' . $matches[2];
            },
            $str
        );

        $str = preg_replace(
            [
                '/(<span style="color: #[A-Z0-9]+">.*?)\?&gt;<\/span>\n<\/span>\n<\/code>/is',
                '/<span style="color: #[A-Z0-9]+"\><\/span>/i',
            ],
            [
                "$1</span>\n</span>\n</code>",
                '',
            ],
            $str
        );

        $str = str_replace(
            [
                'phptagopen',
                'phptagclose',
                'asptagopen',
                'asptagclose',
                'backslashtmp',
                'scriptclose',
            ],
            [
                '&lt;?',
                '?&gt;',
                '&lt;%',
                '%&gt;',
                '\\',
                '&lt;/script&gt;',
            ],
            $str
        );

        return $str;
    }
}

if (!function_exists('highlightPhrase')) {
    /**
     * Phrase Highlighter
     *
     * Highlights a phrase within a text string
     * @param string $str      the text string
     * @param string $phrase   the phrase you'd like to highlight
     * @param string $tagOpen  the opening tag to precede the phrase with
     * @param string $tagClose the closing tag to end the phrase with
     */
    function highlightPhrase(string $str, string $phrase, string $tagOpen = '<mark>', string $tagClose = '</mark>'): string
    {
        if ($str === '' || $phrase === '') {
            return $str;
        }

        $phrase = preg_quote($phrase, '/');

        return preg_replace_callback(
            '/(' . $phrase . ')/i',
            function ($matches) use ($tagOpen, $tagClose) {
                return $tagOpen . $matches[1] . $tagClose;
            },
            $str
        );
    }
}

if (!function_exists('convertAccentedCharacters')) {
    /**
     * Convert Accented Foreign Characters to ASCII
     *
     * @param string $str Input string
     */
    function convertAccentedCharacters(string $str): string
    {
        $map = [
            // Letras minúsculas
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'å' => 'a',
            'æ' => 'ae',
            'ç' => 'c',
            'ñ' => 'n',
            'ø' => 'o',
            'ß' => 'ss',

            // Letras mayúsculas
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U',
            'Ä' => 'A', 'Ë' => 'E', 'Ï' => 'I', 'Ö' => 'O', 'Ü' => 'U',
            'Â' => 'A', 'Ê' => 'E', 'Î' => 'I', 'Ô' => 'O', 'Û' => 'U',
            'Å' => 'A',
            'Æ' => 'AE',
            'Ç' => 'C',
            'Ñ' => 'N',
            'Ø' => 'O',
        ];

        return strtr($str, $map);
    }
}

if (!function_exists('wordWrap')) {
    /**
     * Word Wrap
     *
     * Wraps text at the specified character. Maintains the integrity of words.
     * Anything placed between {unwrap}{/unwrap} will not be word wrapped, nor
     * will URLs.
     * @param string $str     the text string
     * @param int    $charlim = 76    the number of characters to wrap at
     */
    function wordWrap(string $str, int $charlim = 76): string
    {
        // Reduce multiple spaces
        $str = preg_replace('/ +/', ' ', $str);

        // Standardize newlines
        $str = str_replace(["\r\n", "\r"], "\n", $str);

        // If the current word is surrounded by {unwrap} tags, we'll
        // extract and store them to restore later.
        $unwrap = [];
        $str = preg_replace_callback('/\{unwrap\}(.+?)\{\/unwrap\}/s', function ($matches) use (&$unwrap) {
            $unwrap[] = $matches[1];
            return '{{unwrapped' . (count($unwrap) - 1) . '}}';
        }, $str);

        // Use PHP's native function to do the initial wordwrap.
        // We set the cut flag to FALSE so that any individual words that are
        // too long get left alone. In the next step, we'll deal with them.
        $lines = explode("\n", wordwrap($str, $charlim, "\n", false));

        // Split long words and URLs that exceed the character limit
        $output = '';
        foreach ($lines as $line) {
            while (mb_strlen($line) > $charlim) {
                // If the over-length word is a URL, we won't wrap it
                if (preg_match('!\[url.+\]|://|www\.!', $line)) {
                    break;
                }

                $temp = mb_substr($line, 0, $charlim - 1);
                $line = mb_substr($line, $charlim - 1);

                $output .= $temp . "\n";
            }

            $output .= $line . "\n";
        }

        // Restore the extracted {unwrap} tags
        foreach ($unwrap as $key => $val) {
            $output = str_replace('{{unwrapped' . $key . '}}', $val, $output);
        }

        // Remove any trailing newline
        return rtrim($output);
    }
}

if (!function_exists('ellipsize')) {
    /**
     * Ellipsize String
     *
     * This function will strip tags from a string, split it at its max_length and ellipsize
     * @param string $str       String to ellipsize
     * @param int    $maxLength Max length of string
     * @param mixed  $position  int (1|0) or float, .5, .2, etc for position to split
     * @param string $ellipsis  ellipsis ; Default '...'
     * @return string Ellipsized string
     */
    function ellipsize(string $str, int $maxLength, $position = 1, string $ellipsis = '&hellip;'): string
    {
        // Strip tags
        $str = trim(strip_tags($str));

        // Is the string long enough to ellipsize?
        if (mb_strlen($str) <= $maxLength) {
            return $str;
        }

        $position = max(0, min(1, $position));
        $positionIndex = (int) floor(mb_strlen($str) * $position);

        $beg = mb_substr($str, 0, $positionIndex);
        $end = mb_substr($str, $positionIndex - ($maxLength - mb_strlen($ellipsis)));

        return $beg . $ellipsis . $end;
    }
}

if (!function_exists('stripSlashes')) {
    /**
     * Strip Slashes
     *
     * Removes slashes contained in a string or in an array
     * @param mixed $str string or array
     * @return mixed string or array
     */
    function stripSlashes($str)
    {
        if (!is_array($str)) {
            return stripslashes($str);
        }

        array_walk_recursive($str, function (&$value) {
            $value = stripslashes($value);
        });

        return $str;
    }
}

if (!function_exists('stripQuotes')) {
    /**
     * Strip Quotes
     *
     * Removes single and double quotes from a string
     */
    function stripQuotes(string $str): string
    {
        $str = str_replace(['"', "'"], '', $str);

        // Eliminar caracteres de escape de comillas
        $str = preg_replace('/\\\\([\'"])/', '$1', $str);

        return $str;
    }
}

if (!function_exists('quotesToEntities')) {
    /**
     * Quotes to Entities
     * Converts single and double quotes to entities
     */
    function quotesToEntities(string $str): string
    {
        // Reemplazar comillas simples por entidad HTML '&#39;'
        $str = str_replace("'", '&#39;', $str);

        // Reemplazar comillas dobles por entidad HTML '&quot;'
        $str = str_replace('"', '&quot;', $str);

        return $str;
    }
}

if (!function_exists('reduceDoubleSlashes')) {
    /**
     * Reduce Double Slashes
     *
     * Converts double slashes in a string to a single slash,
     * except those found in http://
     */
    function reduceDoubleSlashes(string $str): string
    {
        // Reemplazar múltiples barras diagonales consecutivas por una sola barra diagonal
        return preg_replace('#/{2,}#', '/', $str);
    }
}

if (!function_exists('reduceMultiples')) {
    /**
     * Reduce Multiples
     *
     * Reduces multiple instances of a particular character.  Example:
     * Fred, Bill,, Joe, Jimmy
     * becomes:
     * Fred, Bill, Joe, Jimmy
     * @param string $character the character you wish to reduce
     * @param bool   $trim      TRUE/FALSE - whether to trim the character from the beginning/end
     */
    function reduceMultiples(string $str, string $character = ',', bool $trim = false): string
    {
        // Reemplazar múltiples repeticiones del carácter especificado por una sola repetición
        $str = preg_replace('/' . preg_quote($character, '/') . '+/', $character, $str);

        return ($trim) ? trim($str, $character) : $str;
    }
}

if (!function_exists('randomString')) {
    /**
     * Create a Random String
     *
     * Useful for generating passwords or hashes.
     * @param string $type Type of random string.  basic, alpha, alnum, numeric, nozero, md5, sha1, and crypto
     * @param int    $len  Number of characters
     */
    function randomString(string $type = 'alnum', int $len = 8): string
    {
        $characters = '';

        switch ($type) {
            case 'alpha':
                $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;

            case 'alnum':
                $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;

            case 'numeric':
                $characters = '0123456789';
                break;

            case 'nozero':
                $characters = '123456789';
                break;

            case 'md5':
                return md5(uniqid((string) mt_rand(), true));

            case 'sha1':
                return sha1(uniqid((string) mt_rand(), true));

            case 'crypto':
                return bin2hex(random_bytes($len / 2));

            default:
                return (string) mt_rand();
        }

        $maxIndex = strlen($characters) - 1;
        $randomString = '';

        for ($i = 0; $i < $len; $i++) {
            $randomString .= $characters[random_int(0, $maxIndex)];
        }

        return $randomString;
    }
}

if (!function_exists('incrementString')) {
    /**
     * Add's _1 to a string or increment the ending number to allow _2, _3, etc
     *
     * @param string $str       Required
     * @param string $separator What should the duplicate number be appended with
     * @param int    $first     Which number should be used for the first dupe increment
     */
    function incrementString(string $str, string $separator = '_', int $first = 1): string
    {
        $parts = explode($separator, $str);
        $lastPart = end($parts);

        if (is_numeric($lastPart)) {
            $newLastPart = (int) $lastPart + 1;
            array_pop($parts);
            $parts[] = $newLastPart;
        } else {
            $parts[] = $first;
        }

        return implode($separator, $parts);
    }
}

if (!function_exists('alternator')) {
    /**
     * Alternator
     *
     * Allows strings to be alternated. See docs...
     * @param string ...$args (as many parameters as needed)
     */
    function alternator(...$args): callable
    {
        $count = count($args);
        $index = 0;

        return function () use (&$args, &$index, $count) {
            $value = $args[$index];
            $index = ($index + 1) % $count;
            return $value;
        };
    }
}

if (!function_exists('excerpt')) {
    /**
     * Excerpt.
     *
     * Allows to extract a piece of text surrounding a word or phrase.
     * @param string $text     String to search the phrase
     * @param string $phrase   Phrase that will be searched for.
     * @param int    $radius   The amount of characters returned around the phrase.
     * @param string $ellipsis Ending that will be appended
     * @return string
     * 
     * If no $phrase is passed, will generate an excerpt of $radius characters
     * from the beginning of $text.
     */
    function excerpt(string $text, ?string $phrase = null, int $radius = 100, string $ellipsis = '...'): string
    {
        if (isset($phrase)) {
            $phrasePos = stripos($text, $phrase);
            $phraseLen = strlen($phrase);
        } else {
            $phrasePos = $radius / 2;
            $phraseLen = 1;
        }

        $textLength = strlen($text);
        $startPos = max(0, $phrasePos - $radius);
        $endPos = min($textLength, $phrasePos + $phraseLen + $radius);

        $excerpt = substr($text, $startPos, $endPos - $startPos);

        // Remove partial words at the beginning and end of the excerpt
        $excerpt = preg_replace('/\b\w+\b/', '', $excerpt);

        // Add ellipsis if necessary
        if ($startPos > 0) {
            $excerpt = $ellipsis . ltrim($excerpt);
        }
        if ($endPos < $textLength) {
            $excerpt = rtrim($excerpt) . $ellipsis;
        }

        return $excerpt;
    }
}
