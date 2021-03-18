<?php

namespace App;

class CSVHelper
{
    CONST FILE_NAME = 'employees-';

    public static function outputCsv($data) {
        $has_header = false;
        $fileName = self::FILE_NAME.time().'.csv';
        foreach ($data as $c) {
            $fp = fopen($fileName, 'a');
            if (!$has_header) {
                fputcsv($fp, array_keys($c));
                $has_header = true;
            }
            fputcsv($fp, $c);
        }
        fclose($fp);
        echo $fileName . ' has been generated at: ' . date('Y-m-d H:i:s') . PHP_EOL;
    }

    public static function csvCleaner($array) {

        $CSVFormat = [];
        if (empty($array)) {
            throw new \Exception('Array cannot be empty!');
        }
        try {
            foreach ($array as $emp) {
                if ($emp instanceof Employee) {
                    /** @var Employee $emp */
                    $cakeday = $emp->getCakeday();
                    if ($emp->getIgnore() !== 1) {
                        $CSVFormat[$cakeday] = [
                            'Cake Day'    => $cakeday,
                            'Small Cakes' => ($emp->getLargeCake()) ? '' : $emp->getSmallCake(),
                            'Large Cakes' => $emp->getLargeCake(),
                            'Employees'   => ($emp->getLargeCake()) ? $emp->getGroupNames() : $emp->getName()
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return $CSVFormat;
    }



}