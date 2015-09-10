<?php

/**
* This file is part of the Sakura project.
* Copyright (c) 2015 Nikolas Lada
*/

class SakuraException extends Exception {


	protected		$tableName;


	public function __construct ($message = NULL, $code = 0, $tableName = '') {

		parent::__construct($message, (int) $code);
		$this->tableName = $tableName;
	}


	public function getTableName () {

		return $this->tableName;
	}

}


class SakuraNotSupportedException extends SakuraException {}


class SakuraNotImplementedException extends SakuraException {}


class SakuraNoRowReturnedException extends SakuraException {


	protected		$sql;


	public function __construct ($message = NULL, $code = 0, $tableName = '', $sql = '') {

		parent::__construct($message, (int) $code, $tableName);
		$this->sql = $sql;
	}


	public function getSql () {

		return $this->sql;
	}

}


class SakuraRowNotFoundException extends SakuraException {


	protected		$id;


	public function __construct ($message = NULL, $code = 0, $tableName = '', $id = 0) {

		parent::__construct($message, (int) $code, $tableName);
		$this->id = $id;
	}


	public function getId () {

		return $this->id;
	}

}


class SakuraBadColumnException extends SakuraException {


	protected		$columns;

	protected		$type;


	public function __construct ($message = NULL, $code = 0, $tableName = '', $columns = NULL, $type = '') {

		parent::__construct($message, (int) $code, $tableName);
		$this->columns = $columns;
		$this->type = $type;
	}


	public function getColumns () {

		return $this->columns;
	}


	public function getType () {

		return $this->type;
	}

}


class SakuraBadTargetException extends SakuraException {


	protected		$from;

	protected		$whereTo;


	public function __construct ($message = NULL, $code = 0, $tableName = '', $from = NULL, $whereTo = NULL) {

		parent::__construct($message, (int) $code, $tableName);
		$this->from = $from;
		$this->whereTo = $whereTo;
	}


	public function getNodeFrom () {

		return $this->from;
	}


	public function getNodeWhereTo () {

		return $this->whereTo;
	}
}