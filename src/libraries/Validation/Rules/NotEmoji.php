<?php

declare(strict_types=1);

namespace Respect\Validation\Rules;

use function implode;
use function is_string;
use function preg_match;

/*
* Class NotEmoji

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation
 */

final class NotEmoji
{
    private const EMOJI_RANGES = [
        '\x{1F600}-\x{1F64F}', // Emoticons
        '\x{1F300}-\x{1F5FF}', // Miscellaneous Symbols and Pictographs
        '\x{1F680}-\x{1F6FF}', // Transport and Map Symbols
        '\x{1F700}-\x{1F77F}', // Alchemical Symbols
        '\x{1F780}-\x{1F7FF}', // Geometric Shapes Extended
        '\x{1F800}-\x{1F8FF}', // Supplemental Symbols and Pictographs
        '\x{1F900}-\x{1F9FF}', // Supplemental Symbols and Pictographs
        '\x{1FA00}-\x{1FA6F}', // Chess Symbols
        '\x{1FA70}-\x{1FAFF}', // Symbols and Pictographs Extended-A
        '\x{1F004}',           // Mahjong Tile Red Dragon
        '\x{1F0CF}',           // Playing Card Black Joker
        '\x{1F004}-\x{1F0CF}', // Range covering various game-related symbols
        '\x{1F600}-\x{1F64F}', // Emoticons
        '\x{1F300}-\x{1F5FF}', // Miscellaneous Symbols and Pictographs
        '\x{1F680}-\x{1F6FF}', // Transport and Map Symbols
        '\x{1F700}-\x{1F77F}', // Alchemical Symbols
        '\x{1F780}-\x{1F7FF}', // Geometric Shapes Extended
        '\x{1F800}-\x{1F8FF}', // Supplemental Symbols and Pictographs
        '\x{1F900}-\x{1F9FF}', // Supplemental Symbols and Pictographs
        '\x{1FA00}-\x{1FA6F}', // Chess Symbols
        '\x{1FA70}-\x{1FAFF}', // Symbols and Pictographs Extended-A
        '\x{1F004}',           // Mahjong Tile Red Dragon
        '\x{1F0CF}',           // Playing Card Black Joker
        '\x{1F004}-\x{1F0CF}', // Range covering various game-related symbols
        '\x{2600}-\x{26FF}',   // Miscellaneous Symbols
        '\x{2700}-\x{27BF}',   // Dingbats
        // Add more emoji ranges as needed.
    ];

    /**
     * Validate the input text to ensure it does not contain emoji characters.
     *
     * @param mixed $input The input text to validate.
     * @return bool True if the input does not contain emoji characters, false otherwise.
     */
    public function validate($input): bool
    {
        $input = $input['valueData'];

        if (!is_string($input)) {
            return false;
        }

        // Use regular expressions to check for emoji characters within the specified Unicode EMOJI_RANGES .
        return preg_match('/' . implode('|', self::EMOJI_RANGES) . '/mu', $input) === 0;
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The input should not contain emoji characters.';
    }
}
