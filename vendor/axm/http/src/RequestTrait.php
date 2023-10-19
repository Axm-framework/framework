<?php

namespace Axm\Http;

/**
 * Request Trait
 *
 * Additional methods to make a PSR-7 Request class
 * compliant with the framework's own RequestInterface.
 *
 */
trait RequestTrait
{
       
    /**
     * Parse CSV.
     *
     * Convierte CSV en arrays numéricos,
     * cada item es una linea
     *
     * @param string $input
     *
     * @return array
     */
    public function parseCSV($input)
    {
        $temp = fopen('php://memory', 'rw');
        fwrite($temp, $input);
        fseek($temp, 0);
        $res = [];
        while (($data = fgetcsv($temp)) !== false) {
            $res[] = $data;
        }
        fclose($temp);

        return $res;
    }
}
