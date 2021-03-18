<?php

namespace App;

use App\Cache\CacheClient;
use RapidWeb\UkBankHolidays\Factories\UkBankHolidayFactory;

/**
 * Class ContentHelper
 * @package App
 */
class ContentHelper
{

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

    /**
     * ContentHelper constructor.
     */
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

    /**
     * @return array
     */
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
    function bdayValidation($bdayDate, $init = false){

        $day = date("D", strtotime($bdayDate));
        $cakeDate = $bdayDate;

        // Lets check if the employee bday falls on a weekend
        // or if it just follows a weekend
        switch($day) {
            case 'Sat':
                $cakeDate = date('Y-m-d', strtotime($bdayDate . ' +3 Weekday'));
                break;
            case 'Sun':
                $cakeDate = date('Y-m-d', strtotime($bdayDate . ' +2 Weekday'));
                break;
            case 'Mon':
                if ($init) {
                    $cakeDate = date('Y-m-d', strtotime($bdayDate . ' +1 Weekday'));
                }
                break;
            case 'Fri':
                if ($init) {
                    $cakeDate = date('Y-m-d', strtotime($bdayDate . ' +1 Weekday'));
                }
                break;
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
            foreach($this->filteredContents as $idx => $emp) {
                $this->compareOriginalDate($emp, $idx);
            }

        } catch (\Exception $e) {
            print_r($e->getMessage());
        }
        return $this;
    }

    /**
     * @param array $myArray
     * @param Employee $emp
     * @return $this
     */
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
                $element->setSmallCake(0);
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
                    $prvIdx = $idx-1;
                    // Flag previous iteration to ignore in final listing
                    // as we setting the element for grouping
                    if (isset($this->filteredContents[$prvIdx])) {
                        $emp->setIgnore(1);
                        $this->filteredContents[$prvIdx] = $emp;
                    }

                    $element->setLargeCake(1);
                    $element->setGroup($element->getName() . ',' . $emp->getName());
                    $element->setCakeDayExtension(true);
                    $extCakeDate = date('Y-m-d', strtotime($element->getCakeday() . ' +1 Weekday'));
                    $element->setCakeday($this->bdayValidation($extCakeDate));
                    $this->filteredContents[$idx] = $element;
                    continue;
                }
            } else {
                if( empty($element->getGroupNames())) {
                    $element->setSmallCake(1);
                    $this->filteredContents[$idx] = $element;
                    continue;
                }
            }
        }

        return $this;

    }

    /**
     * @param Employee $emp
     * @param $idx
     */
    public function compareOriginalDate(Employee $emp, $idx) {
        $rawBdate = \DateTime::createFromFormat("Y-m-d", $emp->getBday());
        if ($rawBdate->format(self::CURRENT_YEAR.'-m-d') == $emp->getCakeday()) {
            $emp->setCakeday($this->bdayValidation($emp->getCakeday(), true));
            $this->filteredContents[$idx] = $emp;
        }
    }

    /**
     * @param $date1
     * @param $date2
     * @return int
     * @throws \Exception
     */
    public function dateChecker($date1, $date2) : int {
        $date1Obj = new \DateTime($date1);
        $date2Obj = new \DateTime($date2);

        $interval = $date1Obj->diff($date2Obj);
        return $interval->days;
    }



}