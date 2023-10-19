<?php


if (!function_exists('sanitizeFilename')) {
    /**
     * Sanitize a filename to use in a URI.
     */
    function sanitizeFilename(string $filename): string
    {
        // Remover caracteres no deseados del nombre de archivo
        $filename = preg_replace("/[^a-zA-Z0-9\.\-_]/", "", $filename);

        // Eliminar puntos y guiones repetidos
        $filename = preg_replace("/[\.\-_]+/", ".", $filename);

        // Eliminar puntos y guiones al principio y al final del nombre de archivo
        $filename = trim($filename, ".-_");

        return $filename;
    }
}

if (!function_exists('stripImageTags')) {
    /**
     * Strip Image Tags
     */
    function stripImageTags(string $str): string
    {
        // Eliminar etiquetas de imagen con expresiones regulares
        $str = preg_replace('/<img[^>]+>/', '', $str);

        return $str;
    }
}

if (!function_exists('encodePhpTags')) {
    /**
     * Convert PHP tags to entities
     */
    function encodePhpTags(string $str): string
    {
        // Reemplazar solo las etiquetas de apertura y cierre de PHP
        $str = str_replace(['<?php', '<?', '?>'], ['&lt;?php', '&lt;?', '?&gt;'], $str);

        return $str;
    }
}
