<?php

namespace App;

/**
 * Class Employee
 * @package App
 */
class Employee
{

    /**
     * @var
     */
    protected $name;
    /**
     * @var
     */
    protected $bday;
    /**
     * @var
     */
    protected $cakeday;
    /**
     * @var
     */
    protected $cakedayStr;
    /**
     * @var
     */
    protected $smallCake;
    /**
     * @var
     */
    protected $largeCake;
    /**
     * @var string
     */
    protected $groupNames;
    /**
     * @var
     */
    protected $extendCakeday;
    /**
     * @var
     */
    protected $healthday;
    /**
     * @var
     */
    protected $ignore;

    /**
     * Employee constructor.
     */
    public function __construct() {
        $this->groupNames = '';
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $date
     * @return $this
     */
    public function setBday(string $date) {
        $this->bday = $date;
        return $this;
    }

    /**
     * @param int $ignore
     * @return $this
     */
    public function setIgnore(int $ignore) {
        $this->ignore = $ignore;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIgnore() {
        return $this->ignore;
    }

    /**
     * @param string $date
     * @return $this
     */
    public function setCakeday(string $date) {
        $this->cakeday = $date;
        return $this;
    }

    /**
     * @return $this
     */
    public function setCakedayStr() {
        $this->cakedayStr = null;
        if (!empty($this->cakeday) && $this->validateDate($this->cakeday)) {
            $this->cakedayStr = strtotime($this->cakeday);
        }
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setGroup($name) {
        $this->groupNames .= $name;
        return $this;
    }

    /**
     * @param $extend
     */
    public function setCakeDayExtension($extend) {
        $this->extendCakeday = $extend;
    }

    /**
     * @param $healthday
     */
    public function setHealthDay($healthday) {
        $this->healthday = $healthday;
    }

    /**
     * @return mixed
     */
    public function getHealthDay() {
        return $this->healthday;
    }

    /**
     * @return mixed
     */
    public function getCakeDayExt() {
        return $this->extendCakeday;
    }

    /**
     * @return string
     */
    public function getGroupNames() {
        return $this->groupNames;
    }

    /**
     * @return mixed
     */
    public function getBday() {
        return $this->bday;
    }

    /**
     * @return string
     */
    public function getCakeday() :string {
        return $this->cakeday;
    }

    /**
     * @return string
     */
    public function getCakedayStr() :string {
        return $this->cakedayStr;
    }

    /**
     * @param int $cake
     * @return $this
     */
    public function setSmallCake(int $cake) {
        $this->smallCake = $cake;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSmallCake() {
        return $this->smallCake;
    }

    /**
     * @param int $cake
     * @return $this
     */
    public function setLargeCake(int $cake) {
        $this->largeCake = $cake;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLargeCake() {
        return $this->largeCake;
    }

    /**
     * @param $date
     * @param string $format
     * @return bool
     */
    function validateDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

}