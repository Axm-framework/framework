<?php

if (!function_exists('now')) {
    /**
     * Obtiene la fecha y hora actual en un timestamp UNIX.
     *
     * @param string|null $timezone La zona horaria a utilizar (opcional)
     * @return int El timestamp UNIX actual
     */
    function now(?string $timezone = null): int
    {
        $timezone = $timezone ?: appTimeZone();

        if ($timezone === 'local' || $timezone === date_default_timezone_get()) {
            return time();
        }

        $datetime = new DateTime('now', new DateTimeZone($timezone));
        return $datetime->getTimestamp();
    }
}

if (!function_exists('timeZoneSelect')) {
    /**
     * Genera un elemento select HTML para seleccionar la zona horaria.
     *
     * @param string $class     La clase CSS para el elemento select (opcional)
     * @param string $default   La zona horaria predeterminada seleccionada (opcional)
     * @param int    $what      Tipo de zonas horarias a incluir en la lista (opcional)
     * @param string $country   El código de país para filtrar las zonas horarias (opcional)
     * @return string El elemento select HTML
     */
    function timeZoneSelect(string $class = '', string $default = '', int $what = DateTimeZone::ALL, ?string $country = null): string
    {
        $timezones = DateTimeZone::listIdentifiers($what, $country);

        $buffer = "<select name='timezone' class='{$class}'>" . PHP_EOL;

        foreach ($timezones as $timezone) {
            $selected = ($timezone === $default) ? 'selected' : '';
            $buffer .= "<option value='{$timezone}' {$selected}>{$timezone}</option>" . PHP_EOL;
        }

        return $buffer . '</select>' . PHP_EOL;
    }
}
