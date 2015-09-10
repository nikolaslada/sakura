<?php

/**
* This file is part of the Sakura project.
* Copyright (c) 2015 Nikolas Lada
*/

class GeneralTree {

    /** @var Table */
    protected		$table;

    /** @var DibiResult[]|array */
    protected		$treeFromDB;

    /** @var array|DibiRow Property for passing node. */
    protected		$node;

    /** @var int Property for passing ID. */
    protected		$idValue;

    /** @var int ID of root. */
    protected		$rootId;

    protected		$id;

	protected		$whereTo;

	protected		$initializeRecursion;


    /**
    * TableTree constructor.
    * @param Table Put appropriate Table object.
    * @param
    * @return void
    */
    public function __construct ($table) {

        if(!$table instanceof Table) throw new InvalidArgumentException('Parameter $table must be instance of Table class. Instance of :' . get_class($table) . '.');

		$this->table = $table;
		$columns = $this->table->getColumns();
		$this->id = $columns['id'];
		$this->initializeRecursion = True;
    }




	/** Setters and settings **/


	/**
	* Value of $id will be passed to $this->idValue.
	* @param int|string Pass ID of node.
	* @return void
	* @throws InvalidArgumentException
	*/
	public function setId ($id) {

		if(!is_numeric($id)) throw new InvalidArgumentException("The \$id parameter is not numeric. Value: $id; type: " . gettype($id) . ".");

		$this->idValue = $id;
	}


	/**
	* Value of $id will be passed to $this->rootId.
	* @param int
	* @return void
	* @throws LogicException
	* @throws InvalidArgumentException
	*/
	public function setRoot ($id) {

		if(isset($this->tree) and isset($this->rootId)) throw new LogicException('Both - $this->tree and $this->rootId properties are already set. You can not change yet.');
		if(!is_int($id)) throw new InvalidArgumentException('The $id argument is not integer. Type of value is ' . gettype($id) . '.');

		$this->rootId = $id;
	}


	/**
	* Value of $node will be passed to $this->node.
	* @param array|DibiRow Pass values with appropriate keys.
	* @return void
	* @throws InvalidArgumentException
	*/
	public function setNode ($node) {

		if(!is_array($node) and !$node instanceof DibiRow) throw new InvalidArgumentException('$node argument must be array or DibiRow object. Type of $node is ' . gettype($node) . '.');

		$this->node = $node;
	}


	/**
	* It empties the node's values - $this->idValue and $this->node. If you prefers "autoselect", call it before calling $this->setId()!
	* @return void
	*/
	public function emptyNodesValues () {

		unset($this->idValue);
		unset($this->node);
	}


	/**
	* It empties tree's properties - $this->treeFromDB and $this->rootId.
	* @return void
	*/
	public function emptyTree () {

		unset($this->treeFromDB);
		unset($this->rootId);
	}


	/**
	* It empties the $this->list property.
	* @return void
	*/
	public function emptyList () {

		unset($this->list);
	}




	/** SQL Queries **/


	public function selectNode ($DibiFluent = NULL) {

		if(!$DibiFluent instanceof DibiFluent) $DibiFluent = $this->selectNodeSqlFactory();

		$node = $DibiFluent->where("`" . $this->table->getAlias() . "`.`{$this->id}` = %i", $this->getId() )->fetch();
		if(!$node) throw new SakuraRowNotFoundException("Row not found.", 0, $this->table->getName(), $this->getId() );

		$this->node = $node;
	}


	/**
	* It returns number of all rows in table.
	* @return int
	*/
	public function selectNumOfAllRows () {

		return dibi::fetchSingle("SELECT COUNT(*) FROM `" . $this->table->getName() . "`");
	}


    /**
    * It generates set of rows. Each row represents every level in a tree between root and passed ID of node.
	* @see $this->setId()
	* @see $this->selectPathSqlFactory()
	* @see $this->emptyList()
    * @param bool If False is passed, it returns only superior nodes.
    * @param bool If True is passed, rows will be sorted by depth from root.
    * @param DibiFluent Pass NULL or your DibiFluent SQL from selectNodeSqlFactory().
    * @return void|NULL
	* @throws SakuraRowNotFoundException
	* @throw SakuraNoRowReturnedException
    */
    public function selectPath ($includingNode = True, $fromRoot = True, $DibiFluent = NULL) {

		$alias = $this->table->getAlias();
		if($this->initializeRecursion) {
			$this->list = array();
			if(!$DibiFluent instanceof DibiFluent) $DibiFluent = $this->selectPathSqlFactory($includingNode, $fromRoot, $selectId = False);

			$DF = clone $DibiFluent->select("`$alias`.`{$this->parent}`");
			$node = $DF->where("`$alias`.`{$this->id}` = %i", $this->getId() )->fetch();

			if(!$node) throw new SakuraRowNotFoundException("Row not found!", 0, $table, $this->getId() );

			if($includingNode) $this->list[] = $node;

			if(empty($node[$this->parent])) return NULL;
			$this->initializeRecursion = False;

			$DibiFluent->where("`$alias`.`{$this->id}` = %i", $node[$this->parent]);
			$this->selectPath($includingNode, $fromRoot, $DibiFluent);

		} else {
			$node = $DibiFluent->fetch();
			if(!$node) {
				$msg = 'Parent\'s node not found! Structure of Parent/Order Tree is broken.';
				throw new SakuraNoRowReturnedException($msg, 0, $this->table->getName(), dibi::$sql);
			}

			$this->list[] = $node;

			if(empty($node[$this->parent])) {

				if($fromRoot) krsort($this->list);

				$this->initializeRecursion = True;
				return NULL;
			}

			$DibiFluent->where(False);
			$DibiFluent->where("`$alias`.`{$this->id}` = %i", $node[$this->parent]);
			$this->selectPath($includingNode, $fromRoot, $DibiFluent);
		}
    }




    /** DibiFluent SQL Factories **/

    /**
    * It generates basic DibiFluent SQL for getting one node.
    * @return DibiFluent
    */
    public function selectNodeSqlFactory ($selectId = False) {

        $DF = new DibiFluent(dibi::getConnection());
		if($selectId) $DF = dibi::select("`" . $this->table->getAlias() . "`.`{$this->id}`");
		else $DF = dibi::select(False);

		return $DF->from('`' . $this->table->getName() . '` AS `' . $this->table->getAlias() . '`');
    }


	public function selectPathSqlFactory ($includingNode, $fromRoot, $selectId = False) {

		return $this->selectNodeSqlFactory($selectId);
	}



	/** Getters **/


	/**
	* It returns ID. It firstly tries $this->idValue and then ID column in $this->node.
	* @return int
	* @throws LogicException
	*/
	public function getId () {

		if(!isset($this->idValue)) {
			if(!isset($this->node[$this->id])) throw new LogicException('Cannot get id! Both $this->id and $this->node[$this->id] are not set. Call $this->setId() before!');
			return $this->node[$this->id];
		}

		return $this->idValue;
	}


	/**
	* It returns whole node from $this->node and $this->numbered.
	* @param bool If True is passed it checks whether all registered columns are included.
	* @param bool If True is passed it checks whether all columns in $this->node are registered / known.
	* @return array
	* @throws SakuraNotSupportedException
	* @throws SakuraBadColumnException
	*/
	public function getNode ($containsAll = False, $noUnknown = False) {

		if(!isset($this->node)) throw new SakuraNotSupportedException('The $this->node property is not set.');

		if(!is_array($this->node) and !$this->node instanceof DibiRow) SakuraNotSupportedException('Type of $this->node is not supported, instance of : ' . get_class($this->node) . '; type: ' . gettype($this->node) . '.');

		if($containsAll) {
			$allColumns = $this->table->getAllColumns();
			foreach($this->node as $column => $v) {
				foreach($allColumns as $key => $required) {
					if($column == $required) {
						unset($allColumns[$key]);
						break;
					}
				}
			}

			if($allColumns) throw new SakuraBadColumnException('Columns not found!', 0, '', $allColumns, 'notfound');
		}

		if($noUnknown) {
			$unknownColumns = array();
			foreach($this->node as $column => $v) {
				if(!in_array($column, $this->table->getAllColumns())) $unknownColumns[] = $column;
			}

			if($unknownColumns) throw new SakuraBadColumnException('There are unknown columns for Table instance!', 0, '', $unknownColumns, 'unknown');
		}

		if($this->node instanceof DibiRow) return $this->node->toArray();
		return $this->node;
	}


	/**
	* It returns 'parent' value.
	* @return int
	* @throws SakuraBadColumnException
	*/
	public function getParent () {

		if(!isset($this->node[$this->parent])) throw new SakuraBadColumnException('Column not found in $this->node.', 0, '', array('parent' => $this->parent), 'notfound');

		return $this->node[$this->parent];
	}


	/**
	* It returns non-hierarchical result - value of the $this->list property.
	* @return array
	* @throws LogicException
	*/
	public function getList () {

		if(!isset($this->list)) throw new LogicException('The $this->list property is not set. Maybe it was not yet created from queries.');

		return $this->list;
	}


	/**
	* It returns result from DB.
	* @return DibiResult
	* @throws LogicException
	*/
	public function getTreeFromDB () {

		if(!isset($this->treeFromDB)) throw new LogicException('The $this->treeFromDB property is not set. Maybe it was already converted to $this->tree.');

		return $this->treeFromDB;
	}


	public function getTable () {

		return $this->table;
	}

}
