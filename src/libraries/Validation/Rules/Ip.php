<?php

declare(strict_types=1);

namespace Validation\Rules;

/*
* Class Ip

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Ip
{
    /**
     * Validates an IP address.
     *
     * @param string $input The IP address to validate.
     * @param string $type  The type of IP address to validate ('both', 'ipv4', 'ipv6').
     * @return bool True if the input is a valid IP address, false otherwise.
     */
    public function validate(string $input, string $type = 'both'): bool
    {
        $input = $input['valueData'];

        if (!is_string($input)) {
            // Indicate that the input is not a string.
            return false;
        }

        $flags = 0;
        if ($type === 'ipv4') {
            $flags = FILTER_FLAG_IPV4;
        } elseif ($type === 'ipv6') {
            $flags = FILTER_FLAG_IPV6;
        }

        // Use FILTER_VALIDATE_IP and check the result.
        $validIp = filter_var($input, FILTER_VALIDATE_IP, ['flags' => $flags]);

        if ($validIp === false) {
            // Indicate that the input is not a valid IP address.
            return false;
        }

        return true;
    }

    public function message()
    {
        return 'The ip is incorrect.';
    }
}
