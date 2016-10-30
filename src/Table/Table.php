<?php

namespace Sakura\Table;

use Dibi\Connection;
/**
* This file is part of the Sakura project.
* Copyright (c) 2015 Nikolas Lada
*/

class Table {

    /** @var Dibi\Connection */
    private             $connection;
    
    /** @var string Name of a table. */
    private		$name;

    /** @var string Alias of a table. */
    private		$alias;

    /** @var array */
    private		$columns;

    /** @var bool */
    private		$enabledTransaction;

    /** @var array An array for defining numbered columns. */
    private		$numbered;

    /** @var string|int A string or an integer for setting active NumberedColumns instance. */
    private		$activeNumbered;

    /** @var int Limit for SQL queries. */
    private		$limit;

    /** @var int Offset for SQL queries. */
    private		$offset;

    /** @var array */
    private		$checkedColumns;


    /**
    * Table constructor.
    * @param DibiConnection
    * @param string Pass string, same as table's identifier/name.
    * @param array Pass array; empty or with values, which represent at least some columns of current table.
    * @param string Pass string or let empty. This will be used in some SQL query.
    * @param mixed Pass False|0|NULL|empty array|0.0|'' if you cannot use or don't want to use transaction on this table.
    * @return void
    * @throws InvalidArgumentException
    */
    public function __construct(Connection $connection, $name, $columns, $alias = '', $enabledTransaction = True) {

        if (!is_string($name) or empty($name)) {
            throw new \InvalidArgumentException('Class Table: parameter $name must be a non-empty string! Type of $name is ' . gettype($name) . '.');
        }

        if (!is_array($columns)) {
            throw new \InvalidArgumentException('Class Table: parameter $columns must be an array! Type of $columns is ' . gettype($columns) . '.');
        }

        if (!is_string($alias)) {
            throw new \InvalidArgumentException('Class Table: parameter $alias must be a string! Type of $alias is ' . gettype($alias) . '.');
        }
        
        $this->connection = $connection;

        if (empty($alias)) {
            $this->alias = substr($name, 0, 1) . substr(md5($name), 0, 2);
        } else {
            $this->alias = $alias;
        }

        $this->name = $name;
        $this->columns = $columns;
        $this->enabledTransaction = $enabledTransaction;
        $this->limit = 0;
        $this->offset = 0;
    }


    /** NumberedColumns instance **/


    /**
    * It checks existence of collection of NumberedColumns class.
    * @return bool
    */
    public function isNumberedExists () {

        return (isset($this->numbered) and isset($this->activeNumbered));
    }


    /**
    * It will be create new NumberedColumns instance under $this->numbered[$key].
    * @param mixed Pass identifier for NumberedColumns.
    * @param string Pass column name without numbers.
    * @param int Right endpoint of closed interval.
    * @param int Left endpoint of closed interval.
    * @param string|NULL Pass 'int'/'integer', 'string', 'bin'/'binary', 'float', 'date' or 'datetime' for setting data type for all columns. If NULL is passed, it will check data type for each column.
    * @return void
    * @throws InvalidArgumentException
    */
    public function addNumbered ($key, $name, $max, $min = 1, $type = NULL) {

        if (empty($key)) {
            throw new \InvalidArgumentException("\$key must not be empty; value: $key, type: " . gettype($key) . '.');
        }

        if(isset($this->numbered)) {
            $this->numbered[$key] = new NumberedColumns($name, $this->getAlias(), $max, $min, $type);
            $this->activeNumbered = $key;
        } else {
            $this->numbered = array($key => new NumberedColumns($name, $this->getAlias(), $max, $min, $type));
            $this->activeNumbered = $key;
        }
    }


    /**
    * It changes current active identifier of NumberedColumns instances to other.
    * @param mixed Pass identifier of NumberedColumns, which you want to be active.
    * @return void
    * @throws DomainException
    */
    public function setActiveNumbered ($key) {

        if (!isset($this->numbered[$key])) {
            throw new \DomainException("There is no $key key/identifier in the \$this->numbered collection of NumberedColumns instances.");
        }

        $this->activeNumbered = $key;
    }


    /**
    * It returns identifier/keys of instances of NumberedColumns class.
    * @return array
    * @throws LogicException
    */
    public function getKeysOfNumbered () {

        if (!isset($this->numbered)) {
            throw new \LogicException('There is no $this->numbered. Call $this->addNumbered() before.');
        }

        return array_keys($this->numbered);
    }


    /**
    * Calls setList() method of NumberedColumns.
    * @param array
    * @return void
    */
    public function setNumberedList ($array) {

        $this->numbered[$this->activeNumbered]->setList($array);
    }


    /**
    * Calls getList() method of NumberedColumns.
    * @param int|NULL
    * @param int|NULL
    * @return array
    */
    public function getNumberedList ($from, $to) {

        return $this->numbered[$this->activeNumbered]->getList($from, $to);
    }


    /**
    * Calls setSelect() method of NumberedColumns.
    * @param DibiFluent
    * @param int|NULL
    * @param int|NULL
    * @return DibiFluent
    */
    public function setNumberedSelect ($DibiFluent, $from, $to) {

        return $this->numbered[$this->activeNumbered]->setSelect($DibiFluent, $from, $to);
    }


    /**
    * Calls setOrderBy() method of NumberedColumns.
    * @param DibiFluent
    * @param int|NULL
    * @param int|NULL
    * @param bool You can skip this, it's prefilled with True.
    * @return DibiFluent
    */
    public function setNumberedOrderBy ($DibiFluent, $from, $to, $ASC = True) {

        return $this->numbered[$this->activeNumbered]->setOrderBy($DibiFluent, $from, $to, $ASC);
    }


    /**
    * Calls checkList() method of NumberedColumns.
    * @param int
    * @param int
    * @return void
    */
    public function checkNumberedColumns ($from, $to) {

        $this->numbered[$this->activeNumbered]->checkList($from, $to);
    }


    /**
    * Calls setWhere() method of NumberedColumns.
    * @param DibiFluent
    * @param int
    * @param int
    * @param string You can skip this, it's prefilled with '='.
    * @param bool You can skip this, it's prefilled with True.
    * @return DibiFluent
    */
    public function setNumberedWhere ($DibiFluent, $from, $to, $comparisonOperator = '=', $useAnd = True) {

        return $this->numbered[$this->activeNumbered]->setWhere($DibiFluent, $from, $to, $comparisonOperator, $useAnd);
    }


    /**
    * Calls setWhere() method of NumberedColumns.
    * @param int
    * @param int|NULL
    * @param int You can skip this, it's prefilled with 1.
    * @param int You can skip this, it's prefilled with 0.
    * @param DibiFluent You can skip this, it's prefilled with NULL.
    * @return DibiFluent
    */
    public function setNumberedUpdate ($from, $to, $movement = 1, $add = 0, $DibiFluent = NULL) {

        if (is_null($DibiFluent)) {
            $DibiFluent = $this->connection->command()->update("`{$this->name}` AS `{$this->alias}`");
        }

        return $this->numbered[$this->activeNumbered]->setUpdate($DibiFluent, $from, $to, $movement, $add);
    }


    /**
    * Calls setWhere() method of NumberedColumns.
    * @param array Pass num array. For example 1 => 1, 1 => 1, 3 => 4.
    * @param DibiFluent You can skip this, it's prefilled with NULL.
    * @return DibiFluent
    */
    public function setNumberedUpdateValues ($values, $from, $to, $DibiFluent = NULL) {

        if (is_null($DibiFluent)) {
            $DibiFluent = $this->connection->command()->update("`{$this->name}` AS `{$this->alias}`");
        }

        return $this->numbered[$this->activeNumbered]->setUpdateValues($DibiFluent, $values, $from, $to);
    }


    /**
    * Calls getName() method of NumberedColumns.
    * @return string
    */
    public function getNameOfNumbered () {

        return $this->numbered[$this->activeNumbered]->getName();
    }


    /**
    * Calls getMin() method of NumberedColumns.
    * @return int
    */
    public function getMinNumbered () {

        return $this->numbered[$this->activeNumbered]->getMin();
    }


    /**
    * Calls getMax() method of NumberedColumns.
    * @return int
    */
    public function getMaxNumbered () {

        return $this->numbered[$this->activeNumbered]->getMax();
    }


    /**
    * This will detect all columns of the table and divides it to $this->columns array and $this->numbered collection of objects.
    * @return void|NULL
    */
    public function detectColumns () {

        $fields = $this->connection->query("SELECT * FROM `{$this->name}` %ofs", 0, "%lmt", 1);
        if (count($fields) == 0) {
            throw new \Sakura\SakuraNoRowException("There is no row in table `{$this->name}`.");
        }

        $columns = array();
        foreach($fields as $v) {
            foreach($v as $key => $value) {
                $columns[] = $key;
            }
        }

        unset($fields);
        $numberedSet = $this->detectNumbered($columns);
        if($numberedSet == False) {
            $this->columns = $columns;
                return NULL;
        }

        foreach($numberedSet as $findName => $numbered) {
            foreach($columns as $key => $column) {
                $numberMaybe = ltrim($column, $findName);
                if(is_numeric($numberMaybe)) {
                    $number = intval($numberMaybe);
                    if ($number >= $numbered['min'] and $number <= $numbered['max']) {
                        unset($columns[$key]);
                    }
                }
            }

            $this->addNumbered($findName, $findName, $numbered['max'], $numbered['min']);
        }

        // reset to the first founded numbered colums
        $this->activeNumbered = key($this->numbered);
        $this->columns = $columns;
    }


    /**
    * It returns array of numbered columns on success or False on failure.
    * @param array
    * @return array|bool
    */
    private function detectNumbered ($columns) {

        $candidates = array();
        foreach($columns as $column) {
            preg_match('#^(.*[^0-9]+)([0-9]+)$#', $column, $matches);
                if(!empty($matches)) {
                    $candidates[$matches[1]][] = intval($matches[2]);
                }
        }

        if(empty($candidates)) {
            return False;
        }

        $numbered = array();
        foreach($candidates as $candidate => $orderList) {
            sort($orderList);
            $previous = 0;
            $allFromInterval = True;
            foreach($orderList as $order) {
                if($previous) {
                    if($previous != ($order - 1)) {
                        $allFromInterval = False;
                        break;
                    } else $previous = $order;
                } else $previous = $min = $order;
            }

            ($allFromInterval and ($order - $min) > 0) && $numbered[$candidate] = array('max' => $order, 'min' => $min);
        }

        if (empty($numbered)) {
            return False;
        } else {
            return $numbered;
        }
    }


    /**
    * It searches actual common columns with passed array $columns and writes matches to $this->checkedColumns.
    * @param array
    * @return void
    * @throws InvalidArgumentException
    */
    public function checkColumns ($columns) {

        if (!is_array($columns)) {
            throw new \InvalidArgumentException('"$columns" must be array; current type is ' . gettype($columns) . '.');
        }

        $this->checkedColumns = array();
        foreach($this->columns as $keyOfRecorded => $recordedColumn) {
            foreach($columns as $keyOfRequired => $requiredColumn) {
                if($requiredColumn == $recordedColumn or $this->name . '_' . $requiredColumn == $recordedColumn) {
                    $this->checkedColumns[$requiredColumn] = $recordedColumn;
                    unset($columns[$keyOfRequired]);
                    break;
                }
            }
        }

    }


    /**
    * It sets limit and offset, that is used in SQL.
    * @param int Pass integer greater than or equal to 0.
    * @param int Pass integer greater than or equal to 0.
    * @return void
    * @throws InvalidArgumentException
    * @throws DomainException
    */
    public function setLimitOffset ($limit = 0, $offset = 0) {

        if (!is_int($limit) or ! is_int($offset)) {
            throw new \InvalidArgumentException('Both $limit and $offset must be integers! Types: ' . gettype($limit) . ', ' . gettype($offset) . '.');
        }

        if ($limit < 0 or $offset < 0) {
            throw new \DomainException("Variables \$limit and \$offset must be both in greater than or equal to 0. Values: $limit, $offset.");
        }

        $this->limit = $limit;
        $this->offset = $offset;
    }


    /**
    * It increases offset.
    * @param int Pass integer from closed interval from 0 to 18446744073709551615.
    * @return void
    * @throws InvalidArgumentException
    * @throws DomainException
    */
    public function increaseOffset ($movement = 1) {

        if (!is_int($movement)) {
            throw new \InvalidArgumentException('Variable $movement must be an integer! Type: ' . gettype($movement) . '.');
        }

        if ($movement < 0) {
            throw new \DomainException('The value of $movement must be from 0 and should not exceed in total with $this->offset 18446744073709551615. Value: ' . $movement . '; type: ' . gettype($movement) . '.');
        }

        $this->offset += $movement;
    }


    /** Getters **/

    /**
    * It returns name of this table which are associated with this instance.
    * @return string
    */
    public function getName () {

        return $this->name;
    }


    /**
    * It returns alias of this table which are associated with this instance.
    * @return string
    */
    public function getAlias () {

        return $this->alias;
    }


    /**
    * It returns common columns. There will be no column from NumberedColumns.
    * @return array
    */
    public function getColumns () {

        return $this->columns;
    }


    /**
    * It returns all found/registered columns from this table.
    * @return array
    */
    public function getAllColumns () {

        if (!isset($this->numbered)) {
            return $this->columns;
        }

        $columns = $this->columns;
        foreach($this->numbered as $obj) {
            $i = $obj->getMin();
            while($i <= $obj->getMax()) {
                $columns[] = $obj->getName() . $i++;
            }
        }

        return $columns;
    }


    /**
    * It returns True if transaction are enabled and False if not.
    * @return bool
    */
    public function getEnabledTransaction () {

        return $this->enabledTransaction;
    }


    /** It returns value of limit that is used in SQL.
    * @return int
    */
    public function getLimit () {

        return $this->limit;
    }


    /** It returns value of offset that is used in SQL.
    * @return int
    */
    public function getOffset () {

        return $this->offset;
    }


    /**
    * It returns array of checked columns.
    * @return array
    * @throws LogicException
    */
    public function getCheckedColumns () {

        if (!isset($this->checkedColumns)) {
            throw new \LogicException('$this->checkedColumns was not set yet. Call $this->checkColumns() before.');
        }

        return $this->checkedColumns;
    }

}
