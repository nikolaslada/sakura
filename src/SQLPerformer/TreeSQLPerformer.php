<?php

/**
* This file is part of the Sakura project.
* Copyright (c) 2015 Nikolas Lada
*/

class TreeSQLPerformer extends SQLPerformer {

	/** @var Tree Tree instance. */
	private		$tree;

	private		$groups;

	public function __construct ($tree, $printOutput = True) {

		parent::__construct($printOutput);

		if(!$tree instanceof Tree and !is_subclass_of($tree, 'GeneralTree')) {
			throw new InvalidArgumentException('Parameter $table must be instance of class Tree or any class which is subclass of GeneralTree! Type :' .
			gettype($tree) . '; instance of: ' . get_class($tree) . '.');
		}

		$this->tree = $tree;
	}


	public function benchmarkGeneral ($maxId, $repetition = 30) {

		if(!is_int($maxId)) throw new InvalidArgumentException('Parameter $maxId must be integer. Current type is ' . gettype($maxId));
		if(!is_int($repetition)) throw new InvalidArgumentException('Parameter $repetition must be integer. Current type is ' . gettype($repetition));

		for($i = 1; $i <= $repetition; $i++) {
			$this->performSelectNode( mt_rand(1, $maxId) );
			$this->performSelectTree();
			$this->performSelectBranch( mt_rand(1, $maxId) );
			$this->performSelectPath( mt_rand(1, $maxId) );
			$this->performSelectPath( mt_rand(1, $maxId), $includingNode = False, $fromRoot = False );
			$this->performSelectDepth( mt_rand(1, $maxId) );
			$this->performSelectNumOfSubnodes( mt_rand(1, $maxId) );
		}
	}


	public function generateActions ($maxId, $repetition, $name) {

		if(!is_int($maxId)) throw new InvalidArgumentException('Parameter $maxId must be integer. Current type is ' . gettype($maxId));
		if(!is_int($repetition)) throw new InvalidArgumentException('Parameter $repetition must be integer. Current type is ' . gettype($repetition));
		if(!is_string($name)) throw new InvalidArgumentException('Parameter $name must be string. Current type is ' . gettype($name));
		if(!isset($this->actionTable)) throw new LogicException('$this->actionTable is not set, call setTableForLoadActions($tablename) before generateActions()!');

		$inTable = dibi::fetchSingle("SELECT 1 FROM `{$this->actionTable}` WHERE `name` = %s", $name);
		if($inTable) throw new InvalidArgumentException('Parameter $name is used yet. Set other identifier. Current value is: ' . $name);

		$maxIdInTable = dibi::fetchSingle("SELECT MAX(`id`) FROM `" . $this->tree->getTable()->getName() . "`");
		if($maxId) {
			if($maxIdInTable < $maxId) throw new DomainException("Value of parameter \$maxId ($maxId) is > the biggest ID in table: $maxIdInTable");
		} else $maxId = $maxIdInTable;

		$numOfMethods = 12;
		$this->groups = array();
		$orders = array();
		$whereToList = array();
		$IDs = array();

		for($i = 0; $i < $repetition * $numOfMethods; $i++) {
			$numOfAction = $this->getRandomNumberOfMethod($numOfMethods - 1, $repetition);

			$id = $this->checkRowInTable(1, $maxId);
			$whereTo = 0;

			try {

				switch($numOfAction) {
					case 0:
						$this->performSelectNode($id);
						break;

					case 1:
						$this->performSelectTree();
						break;

					case 2:
						$this->performSelectBranch($id);
						break;

					case 3:
						$this->performSelectPath($id);
						break;

					case 4:
						$this->performSelectPath($id, $includingNode = False, $fromRoot = False);
						break;

					case 5:
						$this->performSelectDepth($id);
						break;

					case 6:
						$this->performSelectNumOfSubnodes($id);
						break;

					case 7:
						$this->tree->chooseTarget($id);
						$this->performInsertNode();
						break;

					case 8:
						$this->tree->chooseTargetAsChild($id, $position = 0);
						$this->performInsertNode();
						break;

					case 9:
						$this->performDeleteNode($id);
						break;

					case 10:
						$whereTo = $this->getRandomWhereTo($id, 1, $maxId);
						$this->tree->chooseTarget($whereTo);
						$this->performUpdateBranch($id);
						break;

					case 11:
						$whereTo = $this->getRandomWhereTo($id, 1, $maxId);
						$this->tree->chooseTargetAsChild($whereTo, $position = 0);
						$this->performUpdateBranch($id);
						break;
				}

				$orders[$i] = $numOfAction;
				$IDs[$i] = $id;
				$whereToList[$i] = $whereTo;

			} catch (Exception $e) {
				$i--;
				echo $e->getMessage();
				echo "\nCase/method_id: $numOfAction\n";
			}
		}

		foreach($orders as $k => $numOfAction) {
			$values = array(
				'method_id' => $numOfAction,
				'node_id' => $IDs[$k],
				'whereto_id' => $whereToList[$k],
				'name' => $name);
			dibi::query("INSERT INTO `{$this->actionTable}`", $values);
		}

	}


	protected function checkRowInTable ($minId, $maxId) {

		$id = mt_rand($minId, $maxId);
		$exists = dibi::fetchSingle("SELECT 1 FROM `" . $this->tree->getTable()->getName() . "` WHERE `id` = %i", $id);
		if($exists) return $id;

		return $this->checkRowInTable($minId, $maxId);
	}


	protected function getRandomNumberOfMethod ($count, $repetition) {

		$numOfAction = mt_rand(0, $count);
		if(!isset($this->groups[$numOfAction])) {
			$this->groups[$numOfAction] = 1;
			return $numOfAction;
		} else {

			if($this->groups[$numOfAction] < $repetition) {
				$this->groups[$numOfAction]++;
				return $numOfAction;
			} else {
				foreach($this->groups as $k => $v) {
					if($v < $repetition) {
						$this->groups[$k]++;
						return $k;
					}
				}

				return 0;
			}
		}
	}


	protected function getRandomWhereTo ($nodeId, $minId, $maxId) {

		for($i = $minId; $i <= $maxId; $i++) {
			$possibleChildId = $this->checkRowInTable($minId, $maxId);

			$this->tree->setId($nodeId);
			$DibiFluent = $this->tree->selectBranchSqlFactory($selectId = True);
			$this->tree->selectBranch($DibiFluent);
			$branch = $this->tree->getTreeFromDB();

			$isChildOf = False;
			foreach($branch as $node) {
				if($node['id'] == $possibleChildId) {
					$isChildOf = True;
					break;
				}
			}

			if(!$isChildOf) return $possibleChildId;
		}

		return $possibleChildId;
	}


	public function benchmark ($name, $columns = NULL, $enableColumnName = False) {

		if(!is_string($name)) throw new InvalidArgumentException('Parameter $name must be string. Current type is ' . gettype($name));

		$actions = dibi::fetchAll("SELECT * FROM `{$this->actionTable}` WHERE `name` = %s", $name);
		if(!$actions) throw new SakuraNoRowReturnedException('There is no records for benchmark!', $code = 0, $this->actionTable, dibi::$sql);

		foreach($actions as $row) {

			if($this->printOutput) echo "Action ID: $row[id]\n";
			try {

				switch($row['method_id']) {
					case 0:
						$this->performSelectNode($row['node_id'], $columns);
						break;

					case 1:
						$this->performSelectTree($columns);
						break;

					case 2:
						$this->performSelectBranch($row['node_id'], $columns);
						break;

					case 3:
						$this->performSelectPath($row['node_id']);
						break;

					case 4:
						$this->performSelectPath($row['node_id'], $includingNode = False, $fromRoot = False);
						break;

					case 5:
						$this->performSelectDepth($row['node_id']);
						break;

					case 6:
						$this->performSelectNumOfSubnodes($row['node_id']);
						break;

					case 7:
						$this->tree->chooseTarget($row['node_id']);
						$this->performInsertNode($enableColumnName);
						break;

					case 8:
						$this->tree->chooseTargetAsChild($row['node_id']);
						$this->performInsertNode($enableColumnName);
						break;

					case 9:
						$this->performDeleteNode($row['node_id']);
						break;

					case 10:
						$this->tree->chooseTarget($row['whereto_id']);
						$this->performUpdateBranch($row['node_id']);
						break;

					case 11:
						$this->tree->chooseTargetAsChild($row['whereto_id']);
						$this->performUpdateBranch($row['node_id']);
						break;
				}

			} catch (Exception $e) {
				echo $e->getMessage();
				echo "\nCase/method_id: " . $row['method_id'] . "\n";
			}
		}
	}


	public function performSelectNode ($nodeID, $selectColumns = NULL) {

		$this->start = $this->getTime();
		$this->tree->setId($nodeID);
		$DibiFluent = $this->tree->selectNodeSqlFactory($selectId = True);
		if(is_array($selectColumns)) {
			foreach($selectColumns as $col) {
				$DibiFluent->select("`$col`");
			}
		}

		$this->tree->selectNode($DibiFluent);
		$this->writeTime(__METHOD__);
		$this->tree->emptyNodesValues();
	}


	public function performSelectTree ($selectColumns = NULL) {

		$this->start = $this->getTime();
		if(is_array($selectColumns)) {
			$DibiFluent = $this->tree->selectTreeSqlFactory();
			foreach($selectColumns as $col) {
				$DibiFluent->select("`$col`");
			}
		}

		if(!isset($DibiFluent)) $DibiFluent = NULL;

		$this->tree->selectTree($DibiFluent);
		$this->writeTime(__METHOD__);
		$this->tree->emptyTree();
	}


	public function performSelectBranch ($rootID, $selectColumns = NULL) {

		if($rootID < 1) throw new DomainException('Parameter $rootID must be > 0! Type :' . gettype($rootID) . '.');

		$this->start = $this->getTime();
		$this->tree->setId($rootID);
		if(is_array($selectColumns)) {
			$DibiFluent = $this->tree->selectBranchSqlFactory();
			foreach($selectColumns as $col) {
				$DibiFluent->select("`$col`");
			}
		}

		if(!isset($DibiFluent)) $DibiFluent = NULL;

		$this->tree->selectBranch($DibiFluent);
		$this->writeTime(__METHOD__);
		$this->tree->emptyTree();
	}


	public function performSelectPath ($nodeID, $includingNode = True, $fromRoot = True, $selectColumns = NULL) {

		if($nodeID < 1) throw new DomainException('Parameter $nodeID must be > 0! Type :' . gettype($nodeID) . '.');

		$this->start = $this->getTime();
		$this->tree->emptyList();
		$this->tree->setId($nodeID);
		$DibiFluent = $this->tree->selectPathSqlFactory($includingNode, $fromRoot, $selectId = True);
		if(is_array($selectColumns)) {
			foreach($selectColumns as $col) {
				$DibiFluent->select("`$col`");
			}
		}

		$this->tree->selectPath($includingNode = True, $fromRoot = True, $DibiFluent);
		$this->writeTime(__METHOD__);
		$this->tree->emptyList();
	}


	public function performSelectDepth ($nodeID) {

		if($nodeID < 1) throw new DomainException('Parameter $nodeID must be > 0! Type :' . gettype($nodeID) . '.');

		$this->start = $this->getTime();
		$this->tree->setId($nodeID);
		$this->tree->selectDepth();
		$this->writeTime(__METHOD__);
	}


	public function performSelectNumOfSubnodes ($nodeID) {

		if($nodeID < 1) throw new DomainException('Parameter $nodeID must be > 0! Type :' . gettype($nodeID) . '.');

		$this->start = $this->getTime();
		$this->tree->setId($nodeID);
		$this->tree->selectNumOfSubnodes();
		$this->writeTime(__METHOD__);
	}


	public function performInsertNode ($enableColumnName = False) {

		$this->start = $this->getTime();
		if($enableColumnName) $this->tree->setNode( array('name' => $this->generateRandomString(10, 45) ) );
		else $this->tree->setNode( array() );

		$this->tree->insertNode();
		$this->writeTime(__METHOD__);
	}


	public function performDeleteNode ($nodeID) {

		if($nodeID < 1) throw new DomainException('Parameter $nodeID must be > 0! Type :' . gettype($nodeID) . '.');

		$this->start = $this->getTime();
		$this->tree->setId($nodeID);
		$this->tree->deleteNode();
		$this->writeTime(__METHOD__);
	}


	public function performUpdateBranch ($nodeID) {

		if($nodeID < 1) throw new DomainException('Parameter $nodeID must be > 0! Type :' . gettype($nodeID) . '.');

		$this->start = $this->getTime();
		$this->tree->setId($nodeID);
		$this->tree->updateBranch();
		$this->writeTime(__METHOD__);
	}

}
