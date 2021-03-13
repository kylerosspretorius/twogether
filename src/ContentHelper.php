<?php

namespace App;

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

    /**
     * Organise array by date...just because
     */
    public function arraySorting($key) {
        usort($this->filteredContents, function ($a, $b) use($key) {
            return $a[$key] >= $b[$key];
        });
    }
    public function arrayGrouping($array, $key) {
        $return = array();
        foreach($array as $v) {
            $return[$v[$key]][] = $v;
        }
        return $return;
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
        if (empty($fallsOnHoliday)) {
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
                $origbdayDate = $employee['date']->format(self::CURRENT_YEAR.'-m-d');
                $fArray[$idx]['cake_day'] = $this->bdayValidation($origbdayDate);
            }
        }

        $this->filteredContents = $this->arrayGrouping($fArray,'cake_day');
        ksort($this->filteredContents);

        try {
            $newList = $this->filteredContents;
            $init = true;
            $group = 1;
            foreach ($newList as $cakeDay => $employees) {
                // Set initial cake day to compare rest of employees interation
                $nextEmp = next($newList);
                // is the next date more than one person
                if(null !== $nextEmp && $nextEmp) {
                    if (count($nextEmp) > 1) {
                        $nextDate = array_values($nextEmp)[0]['cake_day'];
                    }
                } else {
                    $nextDate = $nextEmp['cake_day'];
                }

                // This user has been set a new day to have
                // a cake by himself as to many cakes consecutive
                if (isset($this->filteredContents[$cakeDay]['health'])) {
                    $this->filteredContents[$cakeDay][self::CAKE_SMALL] = 1;
                    continue;
                }

                $currCakeDay = new \DateTime($cakeDay);
                $nxtCakeDay = new \DateTime($nextDate);
                $diff = $currCakeDay->diff($nxtCakeDay);

                if ($init) {
                    if ($diff->d > 1) {
                        $this->filteredContents[$cakeDay][self::CAKE_SMALL] = 1;
                    } else if ($diff->d < 2) {
                        $this->filteredContents[$cakeDay][self::LARGE_CAKE] = 1;
                        $this->filteredContents[$cakeDay]['group'] = $group;
                        $this->filteredContents[$nextDate]['group'] = $group;
                    }

                    $init = false;
                    continue;
                }
                // If two birthdays are on the same day, give them a large cake
                if (count($employees) > 1) {
                    $this->filteredContents[$cakeDay]['same_day'] = 1;
                    // check if there is a group set by the previous check
                    // if not, then this group will share there own big cake
                    if (!isset($this->filteredContents[$cakeDay]['group'])) {
                        $this->filteredContents[$cakeDay][self::LARGE_CAKE] = 1;
                        $this->filteredContents[$cakeDay]['group'] = $group;
                        $this->filteredContents[$cakeDay]['shared_day'] = $cakeDay;
                        $group++;
                        continue;
                    }
                    // if the days diff is less than 2, then we need to add
                    // grouping together so they get a cake too
                    if ($diff->d == 1) {
                        $this->filteredContents[$nextDate]['group'][self::LARGE_CAKE] = 1;
                        $this->filteredContents[$cakeDay]['group'] = $group;
                        $this->filteredContents[$nextDate]['group'] = $group;
                        $this->filteredContents[$nextDate]['shared_day'] = $nextDate;
                        $group++;
                        continue;
                    } else {

                    }
                    // next cannot be same group
                    // increment id
                    $group++;
                    continue;

                }

                // This user already in group with previous day
                // Lets push out the next persons cake day
                // to give a break
                if (isset($this->filteredContents[$cakeDay]['shared_day'])) {
                    if ($diff->d == 1) {
                        $newCakeDay = date('Y-m-d', strtotime($nextDate . ' +1 Weekday'));
                        $confirmDay = $this->bdayValidation($newCakeDay);
                        $this->filteredContents[$nextDate]['health'] = $confirmDay;
                    }
                }
                // check if this day has already been assigned to a group
                if (isset($this->filteredContents[$cakeDay]['group'])) {
                    if ($diff->d < 2) {
                        // if next person within range
                        // push out for health reasons
                        $newCakeDay = date('Y-m-d', strtotime($nextDate . ' +1 Weekday'));
                        $confirmDay = $this->bdayValidation($newCakeDay);
                        $this->filteredContents[$nextDate]['health'] = $confirmDay;
                        $group++;
                    }
                } else {

                    if ($diff->d > 1) {
                        $this->filteredContents[$cakeDay][self::CAKE_SMALL] = 1;

                    } else if ($diff->d == 1) {
                        // if diff is 1 or less, group together but set a new cake day
                        $newCakeDay = date('Y-m-d', strtotime($cakeDay . ' +1 Weekday'));
                        $confirmDay = $this->bdayValidation($newCakeDay);
                        $this->filteredContents[$cakeDay]['group'][self::LARGE_CAKE] = 1;
                        $this->filteredContents[$cakeDay]['group'] = $group;
                        $this->filteredContents[$nextDate]['group'] = $group;
                        $this->filteredContents[$cakeDay]['shared_day'] = $confirmDay;
                        $this->filteredContents[$nextDate]['shared_day'] = $confirmDay;
                        $group++;
                    } else {
                        $this->filteredContents[$cakeDay]['group'][self::LARGE_CAKE] = 1;
                        $this->filteredContents[$cakeDay]['group'] = $group;
                        $this->filteredContents[$nextDate]['group'] = $group;
                        // As the days are the same, set shared day
                        $this->filteredContents[$cakeDay]['shared_day'] = $cakeDay;
                        $this->filteredContents[$nextDate]['shared_day'] = $cakeDay;
                    }
                }

            }
        } catch (\Exception $e) {
            print_r($e->getMessage());
        }

        return $this;
    }



}