<?php

namespace App;

class CSVHelper
{
    CONST FILE_NAME = 'employees-';

    public function outputCsv($data) {

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

    public function csvCleaner($array) {
        $names = '';
        $gNames = '';
        $CSVFormat = [];

        // Filtering the array
        $resultArr = array_filter($array, fn($value) => !is_null($value) && $value !== '');

        if (!empty($resultArr)) {
            foreach($array as $idx => $employee) {
                if (isset($employee['same_day']) && !isset($employee['small_cake'])) {
                    foreach($employee as $innerEmp) {
                        if (isset($innerEmp['name'])) {
                            $names .= $innerEmp['name'] . ',';
                            $cakeDay = $innerEmp['cake_day'];
                        }
                    }

                    if (isset($employee['shared_day'])) {
                        $names = rtrim($names, ',');
                        $CSVFormat[$cakeDay] = [
                            'Cake Day'    => $cakeDay,
                            'Small Cakes' => 0,
                            'Large Cakes' => 1,
                            'Employees'   => $names,
                        ];
                    }
                    continue;
                }
                if (isset($employee['health'])) {

                    $CSVFormat[$employee['cake_day']] = [
                        'Cake Day'    => $employee['health'],
                        'Small Cakes' => 1,
                        'Large Cakes' => 0,
                        'Employees'   => $employee['name'],
                    ];

                    continue;
                }

                if (isset($employee['group']) && isset($employee['shared_day'])) {

                    foreach ($employee as $innEmp) {
                        if(is_array($innEmp)) {
                            $gNames .= $innEmp['name'] . ',';
                            $gNames = rtrim($gNames,',');
                        }
                    }

                    $CSVFormat[$employee['shared_day']] = [
                        'Cake Day'    => $employee['shared_day'],
                        'Small Cakes' => 0,
                        'Large Cakes' => 1,
                        'Employees'   => $gNames,
                    ];
                    continue;
                }
                if (!isset($employee['group']) && isset($employee['small_cake'])) {

                    $CSVFormat[$employee[0]['cake_day']] = [
                        'Cake Day'    => $employee[0]['cake_day'],
                        'Small Cakes' => 1,
                        'Large Cakes' => 0,
                        'Employees'   => $employee[0]['name'],
                    ];
                    continue;
                }
            }
        }

        return $CSVFormat;
    }



}