<?php

/**
* This file is part of the Sakura project.
* Copyright (c) 2015 Nikolas Lada
*/

class SQLPerformer {

	/** @var int Start time. */
	protected		$start;

	/** @var array Times of execution. */
	protected		$times;

	/** @var bool Print output on screen. */
	protected		$printOutput;

	/** @var string Name of table for loading actions. */
	protected		$actionTable;


	public function __construct ($printOutput = True) {

		$this->printOutput = $printOutput;
		$this->times = array();
	}


	/**
	* It sets start of performing and initialize array of times.
	* @param string Pass time in format like '13:13' or '13:13:13'.
	* @return void
	* @throws InvalidArgumentException
	*/
	public function setStartTime ($time = '') {

		if(!is_string($time)) throw new InvalidArgumentException('Parameter $time must be a string! Current type is ' . gettype($time));

		if(!empty($time)) {
			$units = explode(':', $time);
			if(count($units) < 2) throw new InvalidArgumentException("Bad format \$time parameter: $time");

			$today = getdate();
			$date = '' . $today['mday'] . ' ' . $today['month'] . ' ' . $today['year'] . ' ';
			$hour = intval($units[0]);
			if(is_int($hour) and $hour < 24) $date .= $hour . ':';
			else throw new InvalidArgumentException("Bad format \$time parameter: $time");

			$minute = intval($units[1]);
			if(is_int($minute) and $minute < 60) $date .= $minute . ':';
			else throw new InvalidArgumentException("Bad format \$time parameter: $time");

			if(isset($units[2])) {
				$second = intval($units[2]);
				if(is_int($second) and $second < 60) $date .= $second;
				else $date .= '00';
			} else $date .= '00';

			$utcGoal = strtotime($date);
			$microtime = microtime();
			$times = explode(' ', $microtime);
			$secs = intval($times[1]);

			echo "sec $secs utcGoal $utcGoal \n";
			if($secs > $utcGoal) throw new InvalidArgumentException("Set higher time than current time or let \$time parameter be empty. Value of \$time: $time.");

			$usleep = round(floatval($times[0]) * 1000000) - 5000;
			$sleep = $utcGoal - $secs;
			if($usleep > 0) usleep($usleep);

			if($this->printOutput) echo "\n Will wait $sleep seconds.";
			sleep($sleep);
		}

    	$this->times[] = array('u' => $this->getTime(), 't' => 0.0, 'id' => 'START');
	}


	protected function getTime () {

		list ($micro, $sec) = explode(' ', microtime());
    	return ((float)$sec + (float)$micro);
	}


	protected function writeTime ($id) {

		$endTime = $this->getTime();
		$this->times[] = array(
			'u' => $endTime,
			't' => ($endTime - $this->start),
			'id' => $id);
	}


	public function setTableForLoadActions ($tablename) {

		if(!is_string($tablename)) throw new InvalidArgumentException('The $tablename parameter must be a string! Current type is: ' . gettype($tablename));

		$this->actionTable = $tablename;
	}


	/**
	* It insert results into Database.
	* @param string Set tablename.
	* @param mixed Set name of session.
	* @param integer You can set number of current thread.
	*/
	public function intoDB ($table, $name, $thread = 0) {

		$values = array();
		$values['unixtime_float'] = array();
		$values['time'] = array();
		$values['name'] = array();
		$values['test'] = array();

		foreach($this->times as $record) {
			$values['unixtime_float'][] = $record['u'];
			$values['time'][] = $record['t'];
			$values['name'][] = $name;
			$values['test'][] = $record['id'];
		}

		dibi::query("INSERT INTO `$table` %m", $values);
	}


	public function generateRandomString($minLength, $maxLength) {

		if(!is_int($minLength) or !is_int($maxLength)) {
			throw new InvalidArgumentException('Both $minLength and $maxLength parameters must be integers! Current types: ' . gettype($minLength) . ' and ' . gettype($maxLength));
		}

		if($minLength < 1) throw new DomainException('$minLength parameter must be > 1! Value: ' . $minLength);

		$length = mt_rand($minLength, $maxLength);
		$chars = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
		$count = count($chars);
		$string = '';

		for($i = 1; $i <= $length; $i++) {
			$random = $chars[ mt_rand(0, $count -1) ];
			if(2 >= mt_rand(1, 10)) $random = strtoupper($random);

			$string .= $random;
		}

		return $string;
	}




	/** Getters **/


	public function getTimes () {

		return $this->times;
	}
}