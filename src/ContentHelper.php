<?php

namespace App;

use App\Cache\CacheClient;
use RapidWeb\UkBankHolidays\Factories\UkBankHolidayFactory;

class ContentHelper
{

    CONST CAKE_SMALL = 'small_cake';
    CONST LARGE_CAKE = 'large_cake';
    CONST CURRENT_YEAR = 2021;
    CONST REGION = 'england-and-wales';

    /**
     * @var array
     */
    protected $contentsNormalized;
    /**
     * @var array
     */
    protected $filteredContents;

    /** @var CacheClient  */
    protected $cacheClient;

    public function __construct() {
        $this->cacheClient = new CacheClient(15);

    }

    /**
     * @param $input
     * @return $this
     * @throws \Exception
     */
    public function validateContents($input)
    {
        if (empty($input)) {
            throw new \Exception('Cannot parse empty input');
        }
        $split = explode("\n", $input);
        $array = [];

        foreach ($split as $string)
        {
            if (empty($string)) {
                continue;
            }
            $record = explode(",", $string);
            $array[] = $record;
        }

        $this->contentsNormalized = $array;
        return $this;
    }

    public function getInitContents() : array {
        return $this->contentsNormalized;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function filterContents() {

        if (empty($this->contentsNormalized)) {
            throw new \Exception('Contents are empty to filter!');
        }
        $this->filteredContents = [];
        foreach ($this->contentsNormalized as $employee) {
            if (is_array($employee)) {
                $empF = [];
                foreach ($employee as $value) {
                    if (is_string($value) && !strtotime($value)) {
                        $empF['name'] = $value;
                        continue;
                    }
                    if (strtotime($value)) {
                        $date = \DateTime::createFromFormat('Y-m-d', $value);
                        if ($date) {
                            $empF['date'] = $date;
                        }
                    }
                }
                $this->filteredContents[] = $empF;
            }
        }
        return $this;
    }

    public function getFilteredContents() : array {
        return $this->filteredContents;
    }

    /** Organise array by key */
    public function arraySorting($key) {
        usort($this->filteredContents, function ($a, $b) use($key) {
            return $a[$key] >= $b[$key];
        });
    }

    /**
     * @param $bdayDate
     * @return false|string
     */
    function bdayValidation($bdayDate){

        $day = date("D", strtotime($bdayDate));
        $cakeDate = $bdayDate;

        // Lets check if the employee bday falls on a weekend
        // If so, push them out by one/two days
        if ($day === 'Sat') {
            $cakeDate = date('Y-m-d', strtotime($bdayDate . ' +3 Weekday'));
        }
        if ($day === 'Sun') {
            $cakeDate = date('Y-m-d', strtotime($bdayDate . ' +2 Weekday'));
        }

        // Does this new day fall on a public holiday
        // if so, push to another day
        $rawCakeDate = \DateTime::createFromFormat("Y-m-d", $cakeDate);
        $month = $rawCakeDate->format("m");
        $day = $rawCakeDate->format("d");

        $fallsOnHoliday = UkBankHolidayFactory::getByDate(self::CURRENT_YEAR, $month, $day, self::REGION);
        if (!empty($fallsOnHoliday)) {
            // Lets add a day and check if this new date is also not
            // a weekend or another public holiday recursively
            $cakeDate = date('Y-m-d', strtotime($bdayDate . ' +1 Weekday'));
            $this->bdayValidation($cakeDate);
        }

        return $cakeDate;
    }

    /**
     * This will accept filtered array
     * It will find out how many cakes per year
     * @param array $fArray
     */
    public function cakeParser(array $fArray) {

        if (empty($fArray)) {
            throw new \Exception('Cannot parse cake data!');
        }

        foreach ($fArray as $idx => $employee) {
            if (isset($employee['name']) && $employee['date']) {
                $fArray[$idx]['cake_day'] = $employee['date']->format(self::CURRENT_YEAR.'-m-d');
            }
        }
        $this->filteredContents = $fArray;
        $this->arraySorting('cake_day');

        $employeeObj = [];
        foreach($this->filteredContents as $idxa => $emply) {
            $eObj = new Employee();
            $bdate = $emply['date'];
            $eObj->setBday($bdate->format("Y-m-d"))
                 ->setCakeday($this->bdayValidation($emply['cake_day']))
                 ->setName($emply['name'])
                 ->setCakedayStr();

            $employeeObj[] = $eObj;
        }
        $this->filteredContents = $employeeObj;

        try {
            /** @var Employee $empObj */
            foreach($this->filteredContents as $empObj) {
               $this->cakeDayOrganiser($employeeObj, $empObj);
            }
        } catch (\Exception $e) {
            print_r($e->getMessage());
        }
        return $this;
    }

    function cakeDayOrganiser(array $myArray, Employee $emp) {
        /** @var Employee $element */
        foreach ($myArray as $idx => $element) {
            // Already in a group, or has a cake..move on
            if (!empty($emp->getGroupNames() || $emp->getLargeCake())) {
                continue;
            }

            // Check for same cake day
            if ($element->getCakedayStr() == $emp->getCakedayStr()
                && $element->getName() !== $emp->getName())
            {
                $element->setGroup($element->getName() . ',' . $emp->getName());
                $element->setLargeCake(1);
                $this->filteredContents[$idx] = $element;
                continue;
            }
            // check for everything else
            if ($element->getCakedayStr() !== $emp->getCakedayStr()
                && $element->getName() !== $emp->getName())
            {

                // If this emp is found with element less than day away
                // we group them together but set ext for health day
                if ($this->dateChecker($element->getCakeday(), $emp->getCakeday()) < 2) {

                    // lets also check if the cake extension is done here
                    // set the health day for previous grouped cakes
                    if ($element->getCakeDayExt()) {
                        $emp->setHealthDay(true);
                        $extCakeDate = date('Y-m-d', strtotime($emp->getCakeday() . ' +2 Weekday'));
                        $emp->setCakeday($this->bdayValidation($extCakeDate));
                        $emp->setSmallCake(1);
                        $nextIdx = $idx+1;
                        if (isset($this->filteredContents[$nextIdx])) {
                            $this->filteredContents[$nextIdx] = $emp;
                        }
                        continue;
                    }

                    $element->setLargeCake(1);
                    $element->setGroup($element->getName() . ',' . $emp->getName());
                    $element->setCakeDayExtension(true);
                    $extCakeDate = date('Y-m-d', strtotime($element->getCakeday() . ' +1 Weekday'));
                    $element->setCakeday($this->bdayValidation($extCakeDate));
                    $this->filteredContents[$idx] = $element;
                    continue;
                }
            }
        }
    }

    public function dateChecker($date1, $date2) : int {
        $date1Obj = new \DateTime($date1);
        $date2Obj = new \DateTime($date2);

        $interval = $date1Obj->diff($date2Obj);
        return $interval->days;
    }



}