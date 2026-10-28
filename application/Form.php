<?php
/**
 * @package	programing
 * https://www.lotryka.com/
 * https://www.youtube.com/@lutryka/
 * https://www.instagram.com/@lutryka/
 * https://www.x.com/@lutryka/
 * https://www.x.com/@lutryka/
 *  طراحی و بیاده سازیه شده توسط حمید مرادی برنامه نویس و فرانت نویس 1 سال زمان برد تا این سیستم تراوین لجند رو طراحی کنم
 *  design projct 2024 no License free work 1 year i had design to travian legend no buge first game browser with and full project no body to design like this
 *  Designed and updated by Hamid Moradi, a programmer and front-end writer
 */
class Form {

	private $errorarray = array();
	public $valuearray = array();
	private $errorcount;

	public function __construct() {
		if(isset($_SESSION['errorarray']) && isset($_SESSION['valuearray'])) {
			$this->errorarray = $_SESSION['errorarray'];
			$this->valuearray = $_SESSION['valuearray'];
			$this->errorcount = count($this->errorarray);

			unset($_SESSION['errorarray']);
			unset($_SESSION['valuearray']);
		}
		else {
			$this->errorcount = 0;
		}
	}

	public function addError($field,$error) {
		$this->errorarray[$field] = $error;
		$this->errorcount = count($this->errorarray);
	}

	public function getError($field) {
		if(array_key_exists($field,$this->errorarray)) {
			return $this->errorarray[$field];
		}
		else {
			return "";
		}
	}

	public function getValue($field) {
		if(array_key_exists($field,$this->valuearray)) {
			return $this->valuearray[$field];
		}
		else {
			return "";
		}
	}

	public function getDiff($field,$cookie) {
		if(array_key_exists($field,$this->valuearray) && $this->valuearray[$field] != $cookie) {
			return $this->valuearray[$field];
		}
		else {
			return $cookie;
		}
	}

	public function getRadio($field,$value) {
		if(array_key_exists($field,$this->valuearray) && $this->valuearray[$field] == $value) {
			return "checked";
		}
		else {
			return "";
		}
	}

	public function returnErrors() {
		return $this->errorcount;
	}

	public function getErrors() {
		return $this->errorarray;
	}
};
