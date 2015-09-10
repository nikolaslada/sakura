<?php

/**
* This file is part of the Sakura project.
* Copyright (c) 2015 Nikolas Lada
*/

class TreeGenerator {

	private		$count;

	private		$maxCount;

	private		$maxDepth;

	private		$intervals;

	private		$tree;


	public function __construct($maxCount, $maxDepth, $intervals) {

		if(!is_int($maxCount)) throw new InvalidArgumentException('$maxCount must be integer! Type: ' . gettype($maxCount));
		if(!is_int($maxDepth)) throw new InvalidArgumentException('$maxDepth must be integer! Type: ' . gettype($maxDepth));
		if(!is_array($intervals)) throw new InvalidArgumentException('$intervals must be array! Type: ' . gettype($intervals));

		$this->maxCount = $maxCount;
		$this->maxDepth = $maxDepth;
		$this->intervals = $intervals;
		$this->count = 0;
		$this->prefix = '';
		$this->suffix = '';
	}


	public function setTableNames ($prefix, $suffix) {

		if(is_string($prefix)) $this->prefix = $prefix;
		if(is_string($suffix)) $this->suffix = $suffix;
	}


	private function getNumOfChilds ($depth) {

		return mt_rand($this->intervals[0][$depth], $this->intervals[1][$depth]);
	}


	public function setAndTestIntervals ($intervals = NULL) {

		if(!empty($intervals)) {
			if(!is_array($intervals)) throw new InvalidArgumentException('$intervals must be array! Type: ' . gettype($intervals));
			$this->intervals = $intervals;
		}

		echo "\n Testing random value from intervals:\n";
		for($i = 0; $i <= $maxDepth; $i++) {
			echo "For $i: " . $this->getNumOfChilds($i) . ".\n";
		}
	}


	public function generateTree () {

		if(!isset($this->tree)) {
			$this->tree = new ArrayObject();
			$this->tree[++$this->count] = new ArrayObject();
			$this->tree[$this->count]->depth = 0;
			$this->tree[$this->count]->parent = 0;
			$numberOfNew = $this->getNumOfChilds(0);

			for($i = 1; $i <= $numberOfNew; $i++) {
				$this->tree[++$this->count] = new ArrayObject();
				$this->tree[1]->childs[$this->count] = $this->tree[$this->count];
			}

			foreach($this->tree[1]->childs as $id => $child) {
				$this->setChilds($child, $depth = 1, 1, $id);
			}
		}
	}


	private function setChilds ($node, $depth, $parent, $nextParent) {

		$node->depth = $depth++;
		$node->parent = $parent;
		if($this->maxDepth == $depth) return NULL;

		$numberOfNew = $this->getNumOfChilds($depth);
		if($this->count + $numberOfNew > $this->maxCount) return NULL;

		for($i = 1; $i <= $numberOfNew; $i++) {
			$this->tree[++$this->count] = new ArrayObject();
			$this->tree[$nextParent]->childs[$this->count] = $this->tree[$this->count];
		}

		foreach($node->childs as $id => $child) {
			$this->setChilds($child, $depth, $nextParent, $id);
		}
	}


	public function setTraversalAndOrderValues ($id = 1, $lft = 0, $rgt = 0, $ord = 0) {

		$this->tree[$id]->lft = ++$lft;
		$this->tree[$id]->ord = ++$ord;
		$rgt++;

		if(isset($this->tree[$id]->childs)) {
			foreach($this->tree[$id]->childs as $childID => $node) {
				$returned = $this->setTraversalAndOrderValues($childID, $lft, $rgt, $ord);
				$lft = $returned[0];
				$rgt = $returned[1];
				$ord = $returned[2];
			}
		}

		$this->tree[$id]->rgt = ++$rgt;
		return array(0 => ++$lft, 1 => $rgt, 2 => $ord);
	}


	public function setLevelValues ($id = 1, $LVLs = array()) {

		$depth = $this->tree[$id]->depth;
		if(count($LVLs) == $depth) $LVLs[$depth] = 1;
		else $LVLs[$depth]++;

		$this->tree[$id]->LVLs = $LVLs;
		if(isset($this->tree[$id]->childs)) {
			foreach($this->tree[$id]->childs as $id => $child) {
				$LVLs = $this->setLevelValues($id, $LVLs);

			}

			array_pop($LVLs);
		}

		return $LVLs;
	}


	public function traversalInsert ($rows = 5000) {

		if(!is_int($rows)) throw new InvalidArgumentException('Parameter $rows must be integer. Type: ' . gettype($rows));

		// echo "Inserting to: `{$this->prefix}traversal{$this->suffix}`.\n";
		$values = array();
		$values['id'] = array();
		$values['parent'] = array();
		$values['left'] = array();
		$values['right'] = array();

		foreach($this->tree as $id => $node) {
			$values['id'][] = $id;
			$values['parent'][] = $node->parent;
			$values['left'][] = $node->lft;
			$values['right'][] = $node->rgt;
			if($id % $rows == 0) {
				dibi::query("INSERT INTO `{$this->prefix}traversal{$this->suffix}` %m", $values);
				// echo dibi::affectedRows() . " affected rows \n";
				$values = array();
				$values['id'] = array();
				$values['parent'] = array();
				$values['left'] = array();
				$values['right'] = array();
			}
		}

		if($this->isNotEmpty($values)) {
			dibi::query("INSERT INTO `{$this->prefix}traversal{$this->suffix}` %m", $values);
			// echo dibi::affectedRows() . " affected rows \n";
		}
	}


	public function orderInsert ($rows = 5000) {

		if(!is_int($rows)) throw new InvalidArgumentException('Parameter $rows must be integer. Type: ' . gettype($rows));

		// echo "Inserting to: `{$this->prefix}order{$this->suffix}`.\n";
		$values = array();
		$values['id'] = array();
		$values['parent'] = array();
		$values['depth'] = array();
		$values['order'] = array();

		foreach($this->tree as $id => $node) {
			$values['id'][] = $id;
			$values['parent'][] = $node->parent;
			$values['depth'][] = $node->depth;
			$values['order'][] = $node->ord;
			if($id % $rows == 0) {
				dibi::query("INSERT INTO `{$this->prefix}order{$this->suffix}` %m", $values);
				// echo dibi::affectedRows() . " affected rows \n";
				$values = array();
				$values['id'] = array();
				$values['parent'] = array();
				$values['depth'] = array();
				$values['order'] = array();
			}
		}

		if($this->isNotEmpty($values)) {
			dibi::query("INSERT INTO `{$this->prefix}order{$this->suffix}` %m", $values);
			// echo dibi::affectedRows() . " affected rows \n";
		}
	}


	public function parentInsert ($rows = 5000) {

		if(!is_int($rows)) throw new InvalidArgumentException('Parameter $rows must be integer. Type: ' . gettype($rows));

		// echo "Inserting to: `{$this->prefix}parent{$this->suffix}`.\n";
		$values = array();
		$values['id'] = array();
		$values['parent'] = array();
		foreach($this->tree as $id => $node) {
			$values['id'][] = $id;
			$values['parent'][] = $node->parent;
			if($id % $rows == 0) {
				dibi::query("INSERT INTO `{$this->prefix}parent{$this->suffix}` %m", $values);
				// echo dibi::affectedRows() . " affected rows \n";
				$values = array();
				$values['id'] = array();
				$values['parent'] = array();
			}
		}

		if($this->isNotEmpty($values)) {
			dibi::query("INSERT INTO `{$this->prefix}parent{$this->suffix}` %m", $values);
			// echo dibi::affectedRows() . " affected rows \n";
		}
	}


	public function levelInsert ($maxLVL, $rows = 5000) {

		if(!is_int($maxLVL)) throw new InvalidArgumentException('Parameter $maxLVL must be integer! Type: ' . gettype($maxLVL));
		if($maxLVL <= $this->maxDepth) throw new DomainException('Parameter $maxLVL must be greater than $this->maxDepth property!');
		if(!is_int($rows)) throw new InvalidArgumentException('Parameter $rows must be integer. Type: ' . gettype($rows));

		// echo "Starting level tree insert.\n";
		$values = array();
		for($i = 1; $i <= $maxLVL; $i++) {
			$values[('L' . $i)] = array();
		}

		foreach($this->tree as $id => $node) {
			$values['id'][] = $id;
			$i = 1;
			foreach($node->LVLs as $depth => $v) {
				if($i == $depth + 1) $values[('L' . $i++)][] = $v;
				else throw new LogicExceptio('Levels in depth form ->LVLs[] are not in right order!');
			}

			for($i; $i <= $maxLVL; $i++) {
				$values[('L' . $i)][] = 0;
			}

			if($id % $rows == 0) {
				dibi::query("INSERT INTO `{$this->prefix}level{$this->suffix}` %m", $values);
				// echo dibi::affectedRows() . " affected rows \n";
				$values = array();
				for($i = 1; $i <= $maxLVL; $i++) {
					$values[('L' . $i)] = array();
				}
			}
		}

		if($this->isNotEmpty($values)) {
			dibi::query("INSERT INTO `{$this->prefix}level{$this->suffix}` %m", $values);
			// echo dibi::affectedRows() . " affected rows \n";
		}
	}


	private function isNotEmpty ($values) {

		if(empty($values)) return False;
		foreach($values as $subArray) {
			if(!empty($subArray)) return True;
		}

		return False;
	}


	public function checkTables () {

		$traversal = dibi::fetchAll("SELECT `id` FROM `{$this->prefix}traversal{$this->suffix}` ORDER BY `left`");
		$order = dibi::fetchAll("SELECT `id` FROM `{$this->prefix}order{$this->suffix}` ORDER BY `order`");

		$levelOrderBy = 'ORDER BY ';
		for($i = 1; $i <= $this->maxDepth; $i++) {
			$levelOrderBy .= "`L$i`, ";
		}

		$levelOrderBy .= "`L$i`";
		$level = dibi::fetchAll("SELECT `id` FROM `{$this->prefix}level{$this->suffix}` $levelOrderBy");

		foreach($traversal as $key => $node) {
			if($node['id'] != $order[$key]['id']) {
				echo "Traversal's and order's nodes aren't in same order!";
				break;
			}
		}

		foreach($traversal as $key => $node) {
			if($node['id'] != $level[$key]['id']) {
				echo "Traversal's and level's nodes aren't in same order!";
				break;
			}
		}

		foreach($order as $key => $node) {
			if($node['id'] != $level[$key]['id']) {
				echo "Order's and level's nodes aren't in same order!";
				break;
			}
		}
	}


	public function getTree () {

		return $this->tree;
	}


	public function getCount () {

		return $this->count;
	}

}
