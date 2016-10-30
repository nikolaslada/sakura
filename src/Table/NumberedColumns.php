<?php

namespace Sakura\Table;

use Dibi\Row;
/**
* This file is part of the Sakura project.
* Copyright (c) 2015 Nikolas Lada
*/

class NumberedColumns {

	/** @var string Column name without numbers. */
	private		$name;

	/** @var int Start value of interval. */
	private		$min;

	/** @var int End value of interval. */
	private		$max;

	/** @var string|NULL Pass 'int'/'integer', 'string', 'bin'/'binary', 'float', 'date' or 'datetime' for setting data type for all columns. If NULL is passed, it will check data type for each column. */
	private		$type;

	/** @var string Table alias. */
	private		$alias;

	/** @var array An array of allowed comparison operators. */
	private		$operators;

	/** @var array */
	private		$checked;

	/** @var array Values of numbered columns mostly for where clause. For example array( 1 => 124, 2 => 0 ). */
	private		$list;


	/**
	* Constructor of NumberedColumns.
	* @param string Pass column name without numbers.
	* @param string Pass table alias.
	* @param int Right endpoint of closed interval.
	* @param int Left endpoint of closed interval.
	* @param string|NULL Pass 'int'/'integer', 'string', 'bin'/'binary', 'float', 'date' or 'datetime' for setting data type for all columns. If NULL is passed, it will check data type for each column. Try to avoid using NULL, it could not work properly.
	* @return void
	* @throws \InvalidArgumentException
	* @throws \LogicException
	* @throws \DomainException
	*/
	public function __construct($name, $alias, $max, $min, $type) {

		if(is_string($name)) $this->name = $name;
		else throw new \InvalidArgumentException('The $name argument is not string. Type of $name is ' . gettype($name) . '.');

		if(is_string($alias)) {
			if(empty($alias)) throw new \InvalidArgumentException('The $alias argument is empty.');
			$this->alias = $alias;
		} else throw new \InvalidArgumentException('The $alias argument is not string. Type of $alias is ' . gettype($alias) . '.');

		if(is_int($max) and is_int($min)) {
			if($max > $min) {
				$this->min = $min;
				$this->max = $max;
			} else throw new \LogicException('"$max" must be greater than "$min". For one numbered column you don\'t need this class.');
		} else throw new \InvalidArgumentException('$max and/or is/are not integers. Type of $max is' . gettype($max) . ' and type of $min is ' . gettype($min) . '.');

		if(is_null($type)) $this->type = NULL;
		elseif(is_string($type)) {
			switch($type) {
				case 'int':
				case 'i':
				case 'integer':
					$this->type = 'i';
					break;
				case 'string':
				case 's':
					$this->type = 's';
					break;
				case 'bin':
				case 'binary':
				case 'b':
					$this->type = 'bin';
					break;
				case 'float':
				case 'f':
					$this->type = 'f';
					break;
				case 'date':
				case 'd':
					$this->type = 'd';
					break;
				case 'datetime':
				case 't':
					$this->type = 't';
					break;
				default;
					throw new \DomainException("Value of the \$type argument '$type' is not supported.");
			}
		} else throw new \InvalidArgumentException('The $type argument is not string or NULL. Type of $type is ' . gettype($type) . '.');

		$this->operators = array('=', '<>', '!=', '>', '>=', '<', '<=');
	}


	/**
	* It sets values into $this->list for where clause. Call this method before $this->getList() | $this->setWhere().
	* @param array|Row Pass an array which are indexed by number.
	* @return void
	* @throws \OutOfBoundsException
	* @throws \InvalidArgumentException
	*/
	public function setList ($array) {


		if(!is_array($array) and !$array instanceof Row) throw new \InvalidArgumentException('The $array argument is not array or Row object.');

		$this->list = array();
		foreach($array as $key => $value) {
			$key = ltrim($key, $this->name);
			if(is_numeric($key)) settype($key, 'integer');
			else throw new \DomainException('There is "$key", that is not numeric. Original value of key is ' . $this->name . $key . '.');

			$this->list[$key] = $value;
		}
	}


	/**
	* It returns part of $this->list array. But it is not checked.
	* @param int|NULL Pass integer as left endpoint of closed interval. If NULL is passed, $from will be equal $this->min.
	* @param int|NULL Pass integer as right endpoint of closed interval. If NULL is passed, $to will be equal $this->max.
	* @return array
	* @throws \DomainException
	* @throws \InvalidArgumentException
	* @throws \LogicException
	*/
	public function getList ($from, $to) {

		if(isset($this->list)) {
			if(is_int($from)) {
				if($from < $this->min) throw new \DomainException("The \$from argument ($from) is smaller than property \$this->min.");
			} elseif(is_null($from)) $from = $this->min;
			else throw new \InvalidArgumentException('The $from argument only accepts integers or NULL. Type of input is ' . gettype($from) . '.');

			if(is_int($to)) {
				if($this->max < $to) throw new \DomainException("The \$to argument ($to) is bigger than property \$this->max.");
			} elseif(is_null($to)) $to = $this->max;
			else throw new \InvalidArgumentException('The $to argument only accepts integers or NULL. Type of input is ' . gettype($to) . '.');

			if($from > $to) throw new \LogicException("The \$from argument ($from) is bigger than the \$to argument ($to).");

			$array = array();
			if($from == $to) $array[$from] = $this->list[$from];
			else {
				foreach($this->list as $key => $value) {
					if($key >= $from and $key <= $to) $array[$key] = $value;
				}
			}
		} else throw new \LogicException('The $this->list property was not created yet. Call $this->setList() before $this->getList().');

		return $array;
	}


	/**
	* Add columns into select clause of Fluent object.
	* @param Fluent Put your Fluent instance.
	* @param int|NULL Pass integer as left endpoint of closed interval. If NULL is passed, $from will be equal $this->min.
	* @param int|NULL Pass integer as right endpoint of closed interval. If NULL is passed, $to will be equal $this->max.
	* @return Fluent
	* @throws \DomainException
	* @throws \InvalidArgumentException
	* @throws \LogicException
	*/
	public function setSelect ($Fluent, $from, $to) {

		if(is_int($from)) {
			if($from < $this->min) throw new \DomainException("The \$from argument ($from) is smaller than property \$this->min.");
		} elseif(is_null($from)) $from = $this->min;
		else throw new \InvalidArgumentException('The $from argument only accepts integers or NULL. Type of input is ' . gettype($from) . '.');

		if(is_int($to)) {
			if($this->max < $to) throw new \DomainException("The \$to argument ($to) is bigger than property \$this->max.");
		} elseif(is_null($to)) $to = $this->max;
		else throw new \InvalidArgumentException('The $to argument only accepts integers or NULL. Type of input is ' . gettype($to) . '.');


		if($from > $to) throw new \LogicException("The \$from argument ($from) is bigger than the \$to argument ($to).");

		for( ; $from <= $to; $from++) {
			$Fluent->select("`{$this->alias}`.`{$this->name}$from`");
		}

		return $Fluent;
	}


	/**
	* Add columns into order by clause of Fluent object.
	* @param Fluent Put your Fluent instance.
	* @param int|NULL Pass integer as left endpoint of closed interval. If NULL is passed, $from will be equal $this->min.
	* @param int|NULL Pass integer as right endpoint of closed interval. If NULL is passed, $to will be equal $this->max.
	* @param bool Pass True for ascending order and False for descending order.
	* @return Fluent
	* @throws \DomainException
	* @throws \InvalidArgumentException
	* @throws \LogicException
	*/
	public function setOrderBy ($Fluent, $from, $to, $asc) {

		if(is_int($from)) {
			if($from < $this->min) throw new \DomainException("The \$from argument ($from) is smaller than property \$this->min.");
		} elseif(is_null($from)) $from = $this->min;
		else throw new \InvalidArgumentException('The $from argument only accepts integers or NULL. Type of input is ' . gettype($from) . '.');

		if(is_int($to)) {
			if($this->max < $to) throw new \DomainException("The \$to argument ($to) is bigger than property \$this->max.");
		} elseif(is_null($to)) $to = $this->max;
		else throw new \InvalidArgumentException('The $to argument only accepts integers or NULL. Type of input is ' . gettype($to) . '.');


		if($from > $to) throw new \LogicException("The \$from argument ($from) is bigger than the \$to argument ($to).");

		if($asc) {
			for( ; $from <= $to; $from++) {
				$Fluent->orderBy("`{$this->alias}`.`{$this->name}$from` ASC");
			}
		} else {
			for( ; $from <= $to; $from++) {
				$Fluent->orderBy("`{$this->alias}`.`{$this->name}$from` DESC");
			}
		}

		return $Fluent;
	}


	/**
	* Add conditions of where clause into Fluent object.
	* @see $this->checkList()
	* @param Fluent Put your Fluent instance.
	* @param int Pass integer as left endpoint of closed interval.
	* @param int Pass integer as right endpoint of closed interval.
	* @param string Pass one of this operators '=', '<>', '!=', '>', '>=', '<', '<='.
	* @param bool If False is passed, it generates OR operators between conditions.
	* @return Fluent
	* @throws \DomainException
	*/
	public function setWhere ($Fluent, $from, $to, $comparisonOperator, $useAnd) {

		if(!is_int($from) or !is_int($to)) throw new \InvalidArgumentException('"$from" and "$to" must be integers.');

		if(in_array($comparisonOperator, $this->operators)) {

			$i = 0;
			if(is_null($this->type)) {
				foreach($this->list as $key => $value) {
					if($from <= $key and $key <= $to) {
						if(is_int($value)) $type = 'i';
						elseif(is_string($value)) $type = 's';
						elseif(is_float($value)) $type = 'f';
						else throw new \DomainException('Detected type ' . gettype($value) . " of value '$value' is not implemented.");

						if($useAnd or $i == 0) $Fluent->where("`{$this->alias}`.`{$this->name}$key` $comparisonOperator %$type", $value);
						else $Fluent->or("`{$this->alias}`.`{$this->name}$key` $comparisonOperator %$type", $value);

						$i++;
					}
				}
			} else {
				foreach($this->list as $key => $value) {
					if($from <= $key and $key <= $to) {
						if($useAnd or $i == 0) $Fluent->where("`{$this->alias}`.`{$this->name}$key` $comparisonOperator %{$this->type}", $value);
						else $Fluent->or("`{$this->alias}`.`{$this->name}$key` $comparisonOperator %{$this->type}", $value);

						$i++;
					}
				}
			}
		} else throw new \DomainException("Value of the \$comparisonOperator argument '$comparisonOperator' is not supported.");

		return $Fluent;
	}


	/**
	* Add columns into set clause of Fluent object. It only set column to other column. For example LVL2 = LVL3.
	* @param Fluent Put your Fluent instance.
	* @param int Pass integer as left endpoint of closed interval.
	* @param int|NULL Pass integer as right endpoint of closed interval. If NULL is passed, $to will be equal $this->max.
	* @param int Pass integer from interval <0; $this->max).
	* @param int Pass integer. For example if -1 is passed, it will set 'LVL2 = LVL2 - 1'.
	* @return Fluent
	* @throws \LogicException
	*/
	public function setUpdate ($Fluent, $from, $to, $movement, $add) {

		if(is_null($from)) $from = $this->min;
		elseif(!is_int($from)) throw new \InvalidArgumentException('The $from argument must be integer! Current type is ' . gettype($from));

		if(is_null($to)) $to = $this->max;
		elseif(!is_int($to)) \InvalidArgumentException('The $to argument must be integer or NULL! Current type is ' . gettype($to));
		elseif($from > $to) throw new \LogicException("Value of \$from must be lower or equal than value of \$to! \$from: $from, \$to: $to.");

		if(!is_int($movement)) \InvalidArgumentException('The $movement argument must be integer! Current type is ' . gettype($movement));
		if(!is_int($add)) \InvalidArgumentException('The $add argument must be integer! Current type is ' . gettype($add));

		if($add > 0) $add = "+$add";
		elseif($add == 0) $add = "";
		else $add = "$add";

		if($movement >= 0) {
			$right = $to - $movement;

			while($from <= $to) {
				if($right < $this->min) break;

				$Fluent->set("`{$this->alias}`.`{$this->name}$to` = `{$this->alias}`.`{$this->name}$right`$add");
				$to--;
				$right--;
			}

		} else {

			$to += $movement;
			$right = $from + ($movement * (-1));

			while($from <= $to) {
				$Fluent->set("`{$this->alias}`.`{$this->name}$from` = `{$this->alias}`.`{$this->name}$right`$add");
				$from++;
				$right++;
			}
		}

		return $Fluent;
	}


	/**
	* Add columns into set clause of Fluent object. It sets values to numbered columns.
	* @param Fluent Put your Fluent instance.
	* @param array Pass num array. For example 1 => 1, 1 => 1, 3 => 4.
	* @return Fluent
	* @throws \InvalidArgumentException
	* @throws \DomainException
	* @throws \OutOfRangeException
	*/
	public function setUpdateValues ($Fluent, $values, $from, $to) {

		if(!is_array($values)) throw new \InvalidArgumentException('Type of $values must be an array. Passed type is ' . gettype($values));

		if(!is_null($from)) $from = $this->min;
		elseif(!is_int($from)) throw new \InvalidArgumentException('Type of $from must be an integer. Passed type is ' . gettype($from));

		if(!is_null($to)) $to = $this->max;
		elseif(!is_int($to)) throw new \InvalidArgumentException('Type of $to must be an integer. Passed type is ' . gettype($to));
		elseif($from > $to) throw new \LogicException("Value of \$from must be lower or equal than value of \$to! \$rom: $from, \$to: $to.");

		if(is_null($this->type)) {
			foreach($values as $k => $v) {
				if(is_int($v)) $type = 'i';
				elseif(is_string($v)) $type = 's';
				elseif(is_float($v)) $type = 'f';
				else throw new \DomainException('Detected type ' . gettype($v) . " of value $v is not implemented. Set \$this->type before calling this methods.");

				if($k <= $to and $k >= $from) $Fluent->set("`{$this->alias}`.`{$this->name}$k` = %$type", $v);
				else throw new \OutOfRangeException("One or more keys from \$values is out of defined range <$from; $to>! Key: $k.");
			}
		} else {
			foreach($values as $k => $v) {
				if($k <= $to and $k >= $from) $Fluent->set("`{$this->alias}`.`{$this->name}$k` = %{$this->type}", $v);
				else throw new \OutOfRangeException("One or more keys from \$values is out of defined range <$from; $to>! Key: $k.");
			}
		}

		return $Fluent;
	}


	/** Getters **/

	/**
	* It returns identifier of NumberedColumns instance.
	* @return string
	*/
	public function getName () {

		return $this->name;
	}


	/**
	* It returns minimum of numbered columns. For most cases it will be 1.
	* @return int
	*/
	public function getMin () {

		return $this->min;
	}


	/**
	* It returns maximum of numbered columns.
	* @return int
	*/
	public function getMax () {

		return $this->max;
	}
}
