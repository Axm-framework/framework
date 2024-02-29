<?php

declare(strict_types=1);

namespace Validation\Rules;

use DateTime;

/*
* Class Time

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Time
{
    private array $formats = [
        'Y-m-d H:i:s',         // Formato ISO 8601 (2022-09-01 14:30:00)
        'Y-m-d\TH:i:s.uP',     // Formato con microsegundos y zona horaria (2022-09-01T14:30:00.123456+02:00)
        'Y-m-d\TH:i:sP',       // Formato con zona horaria (2022-09-01T14:30:00+02:00)
        'Y-m-d\TH:i:s',        // Formato sin zona horaria (2022-09-01T14:30:00)
        'Y-m-d',               // Formato solo fecha (2022-09-01)
        'D, d M Y H:i:s O',    // Formato de fecha RFC 2822 (Thu, 01 Sep 2022 14:30:00 +0200)
        'D, d M Y H:i:s e',    // Formato de fecha con zona horaria (Thu, 01 Sep 2022 14:30:00 Europe/Berlin)
        'D, d M Y H:i:s T',    // Formato de fecha con zona horaria (Thu, 01 Sep 2022 14:30:00 CEST)
        'D, d M Y H:i:s',      // Formato de fecha sin zona horaria (Thu, 01 Sep 2022 14:30:00)
        'D, d M Y',            // Formato solo fecha (Thu, 01 Sep 2022)
        'd M Y H:i:s O',       // Formato personalizado (01 Sep 2022 14:30:00 +0200)
        'd M Y H:i:s e',       // Formato personalizado (01 Sep 2022 14:30:00 Europe/Berlin)
        'd M Y H:i:s T',       // Formato personalizado (01 Sep 2022 14:30:00 CEST)
        'd M Y H:i:s',         // Formato personalizado (01 Sep 2022 14:30:00)
        'd M Y',               // Formato personalizado (01 Sep 2022)
        'Y/m/d H:i:s',         // Formato alternativo (2022/09/01 14:30:00)
        'Y/m/d',               // Formato alternativo (2022/09/01)
        'm/d/Y H:i:s',         // Formato alternativo (09/01/2022 14:30:00)
        'm/d/Y',               // Formato alternativo (09/01/2022)

        // Agrega otros formatos si es necesario
    ];

    /**
     * Validate if the input is a valid date and time in any of the specified formats.
     *
     * @param string $input The string to be validated.
     * @return bool True if the input is a valid date and time, false otherwise.
     */
    public function validate($input): bool
    {
        $input = $input['valueData'];

        if (!is_string($input)) {
            return false;
        }

        foreach ($this->formats as $format) {
            $date = DateTime::createFromFormat($format, $input);
            if ($date && $date->format($format) === $input) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function getErrorMessage()
    {
        return 'The value is not a valid date and time.';
    }
}
