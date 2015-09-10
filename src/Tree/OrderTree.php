<?php

/**
* This file is part of the Sakura project.
* Copyright (c) 2015 Nikolas Lada
*/

class OrderTree extends GeneralTree implements ITreeDriver {

	protected   $parent;

	protected   $order;

	protected   $depth;

	public function __construct($table) {

		parent::__construct($table);
		$columns = $this->table->getColumns();
		$this->parent = $columns['parent'];
		$this->order = $columns['order'];
		$this->depth = $columns['depth'];
	}


	/**
	* Set 'order' and possibly 'endOrder' keys.
	* @param int|string|array|DibiRow Pass value/values with appropriate keys.
	* @return void
	* @throws SakuraBadColumnException
	* @throws DomainException
	*/
	public function setOrder ($node) {

		if(!isset($this->node)) $this->node = array();

		if(is_numeric($node)) {
			$this->node[$this->order] = $node;
			return NULL;
		}

		if(!isset($node[$this->order])) throw new SakuraBadColumnException('Column "order" must be set!', 0, '', array('order' => $this->order), 'notfound');
		if(!is_numeric($node[$this->order])) throw new DomainException("Value under '{$this->order}' key must be integer or numeric string! Values: " . $node[$this->order]);

		if(isset($node['endOrder'])) {
			if(!is_numeric($node['endOrder'])) throw new DomainException("Value under 'endOrder' key must be integer or numeric string! Values: " . $node['endOrder']);

			$this->node['endOrder'] = $node['endOrder'];
		}

		$this->node[$this->order] = $node[$this->order];
	}


	/**
	* It Sets target ($this->whereTo) for insertNode and updateBranch.
	* @param int If integer > 0 is passed, it choose node with this id.
	* @return void
	* @throws DomainException
	* @throws SakuraRowNotFoundException
	* @throws SakuraNotImplementedException
	*/
	public function chooseTarget ($id) {

		if($id < 1) throw new DomainException("Pass  \$id > 0. Passed value: $id.");

		$table = $this->table->getName();

		$targetPDO = dibi::fetch("SELECT `{$this->parent}`, `{$this->depth}`, `{$this->order}` FROM `$table` WHERE `{$this->id}` = %i", $id);

		if(!$targetPDO) throw new SakuraRowNotFoundException('Row not found!', 0, $table, $id);

		$this->whereTo = $targetPDO->toArray();
	}


	/**
	* It Sets target ($this->whereTo) for insertNode and updateBranch.
	* @param int Pass integer > 0. It gets some information from parent node.
	* @param int If integer is 0, it will finds first free position and will use less SQL update later.
	* @return void
	* @throws DomainException
	* @throws SakuraRowNotFoundException
	* @throws SakuraNotImplementedException
	*/
	public function chooseTargetAsChild ($id, $position = 0) {

		if($id < 1) throw new DomainException("Pass  \$id > 0. Passed value: $id.");

		$table = $this->table->getName();

		if($position) {
			$targetPDO = dibi::fetch("SELECT $id AS `{$this->parent}`, `{$this->depth}`+1 AS `{$this->depth}`, `{$this->order}`+$position AS `{$this->order}`" .
			" FROM `$table` WHERE `{$this->id}` = %i", $id);

			if(!$targetPDO) throw new SakuraRowNotFoundException('Row not found!', 0, $table, $id);

			$this->whereTo = $targetPDO->toArray();

		} else {
			$depthOrder = dibi::fetch("SELECT `{$this->depth}`+1 AS `depth`, `{$this->order}`+1 AS `order` FROM `$table` WHERE `{$this->id}` = %i", $id);

			if(!$depthOrder) throw new SakuraRowNotFoundException('Row not found!', 0, $table, $id);

			$maxOrder = $this->selectEndOrder( array( $this->id => $id, 'endOrder' => $depthOrder['order'] ) );
			$this->whereTo = array( $this->parent => $id, $this->depth => $depthOrder['depth'], $this->order => $maxOrder );
		}
	}



	/** SELECTS **/


	/**
	* It selects 'order' value. It can add 'endOrder' value.
	* @param bool If True is passed, it adds 'endOrder' value.
	* @param bool If True is passed, it adds 'depth' value.
	* @return void
	* @throws SakuraRowNotFoundException
	*/
	public function selectOrder ($endOrder = False, $depth = False) {

		$id = $this->getId();
		$sql = "SELECT `{$this->order}`";
		if($depth) $sql .= ", `{$this->depth}`";

		$sql .= " FROM `" . $this->table->getName() . "` WHERE `{$this->id}` = %i";
		$node = dibi::fetch($sql, $id);
		if(!$node) throw new SakuraRowNotFoundException('Row not found!', 0, $this->table->getName(), $id);
		$this->node = array();
		$this->node[$this->order] = $node[$this->order];
		if($depth) $this->node[$this->depth] = $node[$this->depth];

		if(!$endOrder) return NULL;

		$this->node['endOrder'] = $this->selectEndOrder( array( $this->id => $id, 'endOrder' => $node[$this->order] + 1 ) );
	}


	/**
	* It returns max order value from childs. If there any child, it returns order from argument $node.
	* @param array|DibiRow
	* @return void
	*/
	private function selectEndOrder ($node) {

		$result = dibi::fetch("SELECT `{$this->id}`, `{$this->order}`+1 AS `endOrder` FROM `" . $this->table->getName() .
			"` WHERE `{$this->order}` >= %i", $node['endOrder'], "AND `{$this->parent}` = %i", $node[$this->id], "ORDER BY %by", $this->order, "DESC", "%ofs", 0, "%lmt", 1);
		if(!$result) return $node['endOrder'];
		else return $this->selectEndOrder($result);
	}


	/**
	* It executes SQL selects and loads rows to $this->treeFromDB as whole tree.
	* @see $this->selectTreeSqlFactory()
	* @param DibiFluent Pass NULL or your DibiFluent SQL from selectTreeSqlFactory().
	* @return void
	* @throws SakuraNoRowReturnedException
	*/
	public function selectTree ($DibiFluent = NULL) {

		if(!$DibiFluent instanceof DibiFluent) $DibiFluent = $this->selectTreeSqlFactory();
		$this->treeFromDB = $DibiFluent->fetchAll();
		if(empty($this->treeFromDB)) throw new SakuraNoRowReturnedException('There is no tree in table!', 0, $table, dibi::$sql);
	}


	/**
	* It executes SQL selects and loads rows to $this->treeFromDB as a branch.
	* @see $this->selectBranchSqlFactory()
	* @see $this->setId()
	* @see $this->selectOrder(True)
	* @see $this->emptyNodesValues()
	* @param DibiFluent Pass NULL or your DibiFluent SQL from selectTreeSqlFactory().
	* @return void
	* @throws SakuraNoRowReturnedException
	*/
	public function selectBranch ($DibiFluent = NULL) {

		$id = $this->getId();
		$orders = $this->getOrder(True);
		$alias = $this->table->getAlias();

		if(!$DibiFluent instanceof DibiFluent) $DibiFluent = $this->selectBranchSqlFactory();

		$this->treeFromDB = $DibiFluent->where("`$alias`.`{$this->order}` >= %i", $orders[$this->order])
			->and("`$alias`.`{$this->order}` < %i", $orders['endOrder'])->fetchAll();

		if(empty($this->treeFromDB)) throw new SakuraNoRowReturnedException('There is no tree in table!', 0, $table, dibi::$sql);
	}


	/**
	* It returns depth of node.
	* @see $this->setId()
	* @see $this->emptyNodesValues()
	* @return int
	* @throws SakuraRowNotFoundException
	*/
	public function selectDepth () {

		$depth = dibi::fetchSingle("SELECT `{$this->depth}` FROM `" . $this->table->getName() . "` WHERE `{$this->id}` = %i", $this->getId());
		if($depth === False) throw new SakuraRowNotFoundException('Row not found!', 0, $this->table->getName(), $this->getId());

		return $depth;
	}


	/**
	* It returns level of node.
	* @see $this->setId()
	* @see $this->emptyNodesValues()
	* @return int
	* @throws SakuraRowNotFoundException
	*/
	public function selectLevel () {

		return $this->selectDepth() + 1;
	}


	/**
	* It returns number of subnodes of choosen node.
	* @see $this->setId()
	* @see $this->selectOrder(True)
	* @see $this->emptyNodesValues()
	* @return int
	* @throws SakuraBadColumnException
	*/
	public function selectNumOfSubnodes () {

		$orders = $this->getOrder(True);
		return $orders['endOrder'] - 1 - $orders[$this->order];
	}


	public function insertNode () {

		try {
			if($this->table->getEnabledTransaction()) dibi::begin();

			$PDO = $this->getWhereTo();
			$table = $this->table->getName();

			if($PDO[$this->parent] xor $PDO[$this->depth]) throw new SakuraNotSupportedException("Parent and depth values must be both 0 or both greater than 0.");

			if($PDO[$this->parent] and $PDO[$this->depth]) {
				dibi::query("UPDATE `" . $this->table->getName() . "` SET `{$this->order}`=`{$this->order}`+1 WHERE `{$this->order}`>=%i", $PDO[$this->order]);
			}

			if(!$PDO[$this->parent] and !$PDO[$this->depth]) {
				if($PDO[$this->order] != 1) throw new SakuraNotSupportedException('Order value is ' . $PDO[$this->order] . ' and should be 1!');
				else dibi::query("UPDATE `" . $this->table->getName() . "` SET `{$this->depth}`=`{$this->depth}`+1, `{$this->order}`=`{$this->order}`+1");
			}

			$node = $this->getNode();
			if(is_array($node)) $values = array_merge($node, $PDO);
			else $values = array_merge($node->toArray(), $PDO);

			dibi::query("INSERT INTO `$table` %v", $values);

			if(dibi::affectedRows() != 1) throw new SakuraException('After inserting node a value of affectedRows() is not equal to 1!');

			if(!$PDO[$this->parent] and !$PDO[$this->depth]) {
				dibi::query("UPDATE `" . $this->table->getName() . "` SET `{$this->parent}`=%i", dibi::getInsertId(), "WHERE `{$this->order}` = 2");
			}

			if($this->table->getEnabledTransaction()) dibi::commit();
		} catch(Exception $e) {
			if($this->table->getEnabledTransaction()) dibi::rollback();
			throw $e;
		}
	}


	/**
	* Delete one node from DB and update table.
	* @see $this->setId()
	* @return void
	*/
	public function deleteNode () {

		try {
			if($this->table->getEnabledTransaction()) dibi::begin();

		$id = $this->getId();
		$table = $this->table->getName();

		$node = dibi::fetch("SELECT `{$this->parent}`, `{$this->order}` FROM `$table` WHERE `{$this->id}` = %i", $id);
		if(!$node) throw new SakuraRowNotFoundException('Row not found!', 0, $table, $id);

		$node = $node->toArray();
		$node[$this->id] = $id;
		$node['endOrder'] = $node[$this->order] + 1;
		$node['endOrder'] = $this->selectEndOrder($node) - 1;

			dibi::query("DELETE FROM `$table` WHERE `{$this->id}` = %i", $id);

		if($node[$this->order] < $node['endOrder']) {
			dibi::query("UPDATE `$table` SET `{$this->parent}` = %i", $node[$this->parent], "WHERE `{$this->parent}` = %i", $this->getId());
			dibi::query("UPDATE `$table` SET `{$this->depth}` = `{$this->depth}`-1 WHERE `{$this->order}` > %i", $node[$this->order],
				"AND `{$this->order}` <= %i", $node['endOrder']);
		}

		dibi::query("UPDATE `$table` SET `{$this->order}` = `{$this->order}`-1 WHERE `{$this->order}` > %i", $node[$this->order]);

			if($this->table->getEnabledTransaction()) dibi::commit();
		} catch(Exception $e) {
			if($this->table->getEnabledTransaction()) dibi::rollback();
			throw $e;
		}
	}


	/**
	* It move node/branch from one place to other place. You can't take branch and move to its place where is subbranch/subnode!
	* @see $this->setId()
	* @see $this->chooseTarget()
	* @throws SakuraBadColumnException
	* @throws DibiException
	*/
	public function updateBranch () {

		try {
			$table = $this->table->getName();
			$targetPDO = $this->getWhereTo();
			$node = $this->getOrder($endOrder = True, $depth = True);
			$id = $this->getId();
			if(empty($targetPDO[$this->parent])) throw new SakuraNotSupportedException("Target must not be root!");

			if($this->table->getEnabledTransaction()) dibi::begin();

			$move = $node['endOrder'] - $node[$this->order];
			$targetPDO['endOrder'] = $targetPDO[$this->order] + $move - 1;

			if($node[$this->order] <= $targetPDO[$this->order] and $targetPDO['endOrder'] <= $node['endOrder']) {
				$msg = 'Values got from $this->getWhereTo() must not be a subnode/branch of values got from $this->selectOrder() and $this->selectEndOrder()!';
				$badCols = array('current' => array($this->order => $node[$this->order], 'endOrder' => $node['endOrder']), 'target' => $targetPDO);
				throw new SakuraBadColumnException($msg, $code = 0, $table, $badCols, $type = 'subnode');
			}

			dibi::query("UPDATE `$table` SET `{$this->order}` = `{$this->order}`+$move WHERE `{$this->order}` >= %i", $targetPDO[$this->order]);

			if($node[$this->order] > $targetPDO[$this->order]) {
				$node[$this->order] += $move;
				$node['endOrder'] += $move;
			}

			$diff = $targetPDO[$this->order] - $node[$this->order];

			dibi::query("UPDATE `$table` SET `{$this->parent}` = %i", $targetPDO[$this->parent], " WHERE `{$this->id}` = %i", $id);

			$depthDiff = $targetPDO[$this->depth] - $node[$this->depth];

			$sql = array(0 => "UPDATE `$table` SET ");

			if(0 < $depthDiff) $sql[0] .= "`{$this->depth}` = `{$this->depth}`+$depthDiff, ";
			elseif($depthDiff < 0) $sql[0] .= "`{$this->depth}` = `{$this->depth}`$depthDiff, ";

			if(0 < $diff) $sql[0] .= "`{$this->order}` = `{$this->order}`+$diff ";
			elseif($diff < 0) $sql[0] .= "`{$this->order}` = `{$this->order}`$diff ";

			$sql[0] .= "WHERE `{$this->order}` >= %i";
			$sql[] = $node[$this->order];
			$sql[] = " AND `{$this->order}` < %i";
			$sql[] = $node['endOrder'];

			dibi::query($sql);

			dibi::query("UPDATE `$table` SET `{$this->order}` = `{$this->order}`-%i", $move, "WHERE `{$this->order}` >= %i", $node['endOrder']);

			if($this->table->getEnabledTransaction()) dibi::commit();
		} catch(Exception $e) {
			if($this->table->getEnabledTransaction()) dibi::rollback();
			throw $e;
		}

		unset($this->node);
		unset($this->whereTo);
	}




	/** SQL Factories **/


	public function baseSqlFactory ($selecdId = False) {

		return $this->selectNodeSqlFactory();
	}


	public function selectTreeSqlFactory ($selectId = False) {

		$alias = $this->table->getAlias();
		return dibi::select("`$alias`.`{$this->id}`, `$alias`.`{$this->parent}`, `$alias`.`{$this->depth}`")
			->from('`' . $this->table->getName() . "` AS `$alias`")->orderBy("`$alias`.`{$this->order}` ASC");
	}


	public function selectBranchSqlFactory ($selectId = False) {

		return $this->selectTreeSqlFactory();
	}




	/** Getters **/


	/**
	* @param bool If you want to get end order value, pass True.
	* @param bool If True is passed, it adds 'depth' value.
	* @return int|array
	* @throws SakuraBadColumnException
	*/
	public function getOrder ($endOrder = False, $depth = False) {

		if( !isset($this->node[$this->order]) or (!isset($this->node['endOrder']) and $endOrder) or (!isset($this->node[$this->depth]) and $depth) ) {
			$this->selectOrder($endOrder, $depth);
		}

		if(!isset($this->node[$this->order])) throw new SakuraBadColumnException('Column not found in $this->node.', 0, '', array('order' => $this->order), 'notfound');
		if(!$endOrder and !$depth) return $this->node[$this->order];

		if($endOrder) {
			if(!isset($this->node['endOrder'])) throw new SakuraBadColumnException('Column not found in $this->node.', 0, '', array('endOrder' => 'endOrder'), 'notfound');
		}

		if($depth) {
			if(!isset($this->node[$this->depth])) throw new SakuraBadColumnException('Column not found in $this->node.', 0, '', array('depth' => $this->depth), 'notfound');
		}

		return $this->node;
	}


	/**
	* It returns $this->whereTo property.
	* @return array
	* @throws LogicException
	* @throws SakuraBadColumnException
	*/
	public function getWhereTo () {

		if(!isset($this->whereTo)) throw new LogicException('There is nothing to return. Call "$this->chooseTarget()" before.');
		if(!isset($this->whereTo[$this->parent]) or !isset($this->whereTo[$this->depth]) or !isset($this->whereTo[$this->order])) {
			$msg = "All of 'parent', 'depth', 'order' and 'endOrder' columns must be set!";
			throw new SakuraBadColumnException($msg, 0, '', array('parent' => $this->parent, 'depth' => $this->depth, 'order' => $this->order), 'notset');
		}

		return $this->whereTo;
	}

}
