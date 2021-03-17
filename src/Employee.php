<?php

namespace App;

class Employee
{

    protected $name;
    protected $bday;
    protected $cakeday;
    protected $cakedayStr;
    protected $smallCake;
    protected $largeCake;
    protected $groupNames;
    protected $extendCakeday;
    protected $healthday;

    public function __construct() {
        $this->groupNames = '';
    }

    public function setName(string $name) {
        $this->name = $name;
        return $this;
    }

    public function getName() {
        return $this->name;
    }

    public function setBday(string $date) {
        $this->bday = $date;
        return $this;
    }

    public function setCakeday(string $date) {
        $this->cakeday = $date;
        return $this;
    }
    public function setCakedayStr() {
        $this->cakedayStr = null;
        if (!empty($this->cakeday) && $this->validateDate($this->cakeday)) {
            $this->cakedayStr = strtotime($this->cakeday);
        }
        return $this;
    }

    public function setGroup($name) {
        $this->groupNames .= $name;
        return $this;
    }

    public function setCakeDayExtension($extend) {
        $this->extendCakeday = $extend;
    }

    public function setHealthDay($healthday) {
        $this->healthday = $healthday;
    }

    public function getHealthDay() {
        return $this->healthday;
    }

    public function getCakeDayExt() {
        return $this->extendCakeday;
    }

    public function getGroupNames() {
        return $this->groupNames;
    }

    public function getBday() {
        return $this->bday;
    }

    public function getCakeday() :string {
        return $this->cakeday;
    }

    public function getCakedayStr() :string {
        return $this->cakedayStr;
    }

    public function setSmallCake(int $cake) {
        $this->smallCake = $cake;
        return $this;
    }

    public function getSmallCake() {
        return $this->smallCake;
    }

    public function setLargeCake(int $cake) {
        $this->largeCake = $cake;
        return $this;
    }

    public function getLargeCake() {
        return $this->largeCake;
    }

    function validateDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

}