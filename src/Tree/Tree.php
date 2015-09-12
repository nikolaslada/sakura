<?php

/**
* This file is part of the Sakura project.
* Copyright (c) 2015 Nikolas Lada
*/

class Tree {

	/** @var    Table   Link to Table instance. */
	private		$table;

	/** @var    string  Shorter of active driver, it can be (t)raversal, (o)rdered, (p)arent, (l)evel. */
	private		$activeDriver;

	/** @var    array   Registry storage for GeneralTree instances */
	private		$usedDrivers;


	/**
	* Constructor of Tree.
	* @param Table Pass Table instance.
	* @param string Pass empty string for detecting a type or pass 't'* for traversal, 'o'* for ordered, 'p'* for parent and 'l'* for level driver.
	* @param mixed Pass False|0|NULL|0.0|''|array(), if you want to skip additional checking.
	* @param string Pass name of NumberedColumns for LevelTree. In most cases you can skip this. Pass it, if you have got more than one instances of NumberedColumns.
	* @throws InvalidArgumentException
	* @throws SakuraNotSupportedException
	*/
	public function __construct(Table $table, $driver = '', $checkStructure = True, $levelName = '') {

		if(!is_string($driver)) throw new InvalidArgumentException('Parameter $driver must be a string! Type: ' . gettype($driver) . '.');

		$this->table = $table;
		$this->usedDrivers = array();
		$driversWithoutNC = array('t','o','p');

		$columns = $this->table->getColumns();
		if(empty($columns)) {
			$this->table->detectColumns();
			$columns = $this->table->getColumns();
		}

		if($driver == '') {
			foreach($driversWithoutNC as $structure) {
				if($this->choose($driver, $columns)) $this->initialize($driver);

				if(isset($this->activeDriver)) break;
			}

			if(!isset($this->activeDriver)) $driver == 'l';
		} else $driver = substr($driver, 0, 1);

		if($driver == 'l') $this->checkLevelStructure($columns, $levelName);
		else {
			foreach($driversWithoutNC as $value) {
				if($driver == $value) {
					if($this->choose($driver, $columns)) $this->initialize($driver);
				}
			}
		}

		if(!isset($this->activeDriver)) throw new SakuraNotSupportedException('Cannot initialize a tree driver.');
	}


	public function __call ($method, $args) {

		if($method == '') throw new LogicException("Cannot call unnamed method of class 'Tree' or Tree's child.");

		$Reflection = new ReflectionMethod( get_class($this->usedDrivers[$this->activeDriver]), $method );

		if(!$Reflection->isPublic()) throw new LogicException("Cannot call non-public method of class 'Tree' or Tree's child.");

		return call_user_func_array( array( $this->usedDrivers[$this->activeDriver], $method ), $args);
	}


	/**
	* Choose right structure to check.
	* @param string
	* @param array
	* @return bool
	* @throws SakuraNotImplementedException
	*/
	private function choose ($driver, $columns, $levelName = '') {

		if($driver == 't') return $this->checkTraversalStructure($columns);
		elseif($driver == 'o') return $this->checkOrderStructure($columns);
		elseif($driver == 'p') return $this->checkParentStructure($columns);
		elseif($driver == 'l') return $this->checkLevelStructure($columns, $levelName);

		throw new SakuraNotImplementedException("Parameter \$driver: there is no '$driver' driver!");
	}


	/**
	* It initializes new tree driver.
	* @param string
	* @param string
	* @return void
	* @throws SakuraNotImplementedException
	*/
	private function initialize ($driver, $id = '') {

		if($driver == 't') $this->usedDrivers[$driver] = new TraversalTree($this->table);
		elseif($driver == 'o') $this->usedDrivers[$driver] = new OrderTree($this->table);
		elseif($driver == 'p') $this->usedDrivers[$driver] = new ParentTree($this->table);
		elseif($driver == 'l' and !empty($id)) $this->usedDrivers[$driver] = new LevelTree($this->table, $id);
		else throw new SakuraNotImplementedException("Parameter \$driver: there is no '$driver' driver!");

		$this->activeDriver = $driver;
	}


	/**
	* It checks level structure.
	* @param string It can be skipped.
	* @return void
	* @throws SakuraNotSupportedException
	* @throws OutOfBoundsException
	*/
	private function checkLevelStructure ($columns, $levelName = '') {

		if(!in_array('id', $columns) and !in_array($this->table->getName() . '_id', $columns)) throw new SakuraNotSupportedException("There is no 'id' or '" . $this->table->getName() . "_id'.");

		$numberedKeys = $this->table->getKeysOfNumbered();
		if($levelName and count($numberedKeys) > 1) {
			if(!in_array($levelName, $numberedKeys)) throw new OutOfBoundsException("No \$levelName $levelName found in collection of NumberedColumns class.");

			$this->table->setActiveNumbered($levelName);
			$this->initialize('l', $levelName);
		} else $this->initialize('l', $this->table->getNameOfNumbered());
	}


	/**
	* It checks passed array with traversal structure.
	* @param array
	* @return bool
	*/
	private function checkTraversalStructure ($fields) {

		return $this->checkStructure($fields, array('id', 'parent', 'left', 'right'));
	}


	/**
	* It checks passed array with ordered structure.
	* @param array
	* @return bool
	*/
	private function checkOrderStructure($fields) {

		return $this->checkStructure($fields, array('id', 'parent', 'depth', 'order'));
	}


	/**
	* It checks passed array with parent structure.
	* @param array
	* @return bool
	*/
	private function checkParentStructure($fields) {

		return $this->checkStructure($fields, array('id', 'parent'));
	}


	/**
	* It checks array.
	* @param array
	* @param array
	* @return bool
	*/
	private function checkStructure ($fields, $pattern) {

		$columns = array();
		foreach($fields as $value) {
			foreach($pattern as $k => $v) {
				if(strpos($value, $v) !== False) {
					$columns[$v] = $value;
					unset($pattern[$k]);
				}
			}
		}

		if(empty($pattern)) return True;
		else return False;
	}




	/** Getters **/

	public function getActiveDriver() {

		return $this->activeDriver;
	}

}
