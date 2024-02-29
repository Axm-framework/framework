<?php

/**
 * This file is part of Axm framework.
 *
 * (c) Axm Foundation <admin@Axm.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Console\Commands\Utilities;

use Console\BaseCommand;
use Console\CLI;

/**
 * [Description Colors]
 */
class Colors extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     *
     * @var string
     */
    protected $group = 'Utilities';

    /**
     * The Command's name
     * @var string
     */
    protected $name = 'colors';

    /**
     * the Command's short description
     * @var string
     */
    protected $description = 'Displays the colors supported by the console.';

    /**
     * the Command's usage
     * @var string
     */
    protected $usage = 'colors [options]';

    /**
     * The Command's arguments
     * @var array<string, string>
     */
    protected $options = [
        '--256' => 'Displays 256 colors if supported, otherwise it will display the console default colors.',
    ];

    /**
     * @var string
     */
    protected $driver = null;

    /**
     * Creates a new database.
     *   
     * @param array $params
     */
    public function run(array $params)
    {
        $this->printFormattedBlock('reset');
        CLI::newLine();
        CLI::newLine();
        CLI::newLine();

        $c_256 = array_key_exists('256', $params) || CLI::getOption('256');
        if ($c_256) {
            $this->colors_256();
        }
    }

    /**
     * Function for printing a block of formatted text in the console    
     *
     * @param  mixed $format
     * @return void
     */
    protected function printFormattedBlock($format)
    {
        // Códigos ANSI para formato
        $formatCodes = [
            'reset' => "\033[0m",
            'bold' => "\033[1m",
            'underline' => "\033[4m",
            'blink' => "\033[5m",
            'reverse' => "\033[7m",
        ];

        // ANSI codes for background and foreground colors
        $backgroundColors = range(40, 47);
        $foregroundColors = range(30, 37);

        foreach ($backgroundColors as $clbg) {
            foreach ($foregroundColors as $clfg) {
                foreach ($formatCodes as $attr => $code) {
                    $txt = '' . $attr;
                    $output = sprintf("\033[%d;%d;%dm %s %s \033[0m", $attr, $clbg, $clfg, $code, $txt);
                    echo $output;
                }
                echo PHP_EOL;
            }
        }
    }

    /**
     * colors_256
     *
     * @return void
     */
    public function colors_256()
    {
        CLI::write('256 colors: ');
        CLI::newLine();

        // Loop for background and foreground colors
        foreach ([38, 48] as $fgbg) {
            foreach (range(0, 255) as $color) {

                $this->printColorBlocks($fgbg, $color);
            }

            echo PHP_EOL;
        }
    }

    /**
     * Function for printing text blocks with foreground/background colors
     * @param mixed $fgbg
     * @param mixed $color
     */
    function printColorBlocks($fgbg, $color)
    {
        // Imprimir el color
        printf("\033[%d;5;%sm  %3s  \033[0m", $fgbg, $color, $color);

        // Imprimir 6 colores por línea
        if (($color + 1) % 6 == 4) {
            echo PHP_EOL;
        }
    }
}
