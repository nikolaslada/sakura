<?php

/**
* Testing of TraversalTree
* @author Nikolas Lada
*/

use Tester\Assert;

require_once __DIR__ . '/../../tests_config.php';
require_once __DIR__ . '/../../src/SakuraException.php';
require_once __DIR__ . '/../../src/Table/Table.php';
require_once __DIR__ . '/../../src/Tree/ITreeDriver.php';
require_once __DIR__ . '/../../src/Tree/GeneralTree.php';
require_once __DIR__ . '/../../src/Tree/TraversalTree.php';


function checkWholeTable($flatTree, $defaultOrd, $movement = 0)  {

	$leftRightValues = array();
	foreach($flatTree as $ord => $node) {
		Assert::same( $defaultOrd[($ord + $movement)], $node['name'] );
		Assert::notContains( $node['left'], $leftRightValues );
		Assert::notContains( $node['right'], $leftRightValues );
		$leftRightValues[] = $node['left'];
		$leftRightValues[] = $node['right'];
	}
}


function printWholeTable($flatTree) {

	echo "id | name | parent | left | right \n";
	foreach($flatTree as $node) {
		echo $node['id'] . " ";
		echo $node['name'] . " ";
		echo $node['parent'] . " ";
		echo $node['left'] . " ";
		echo $node['right'] . "\n";
	}
}


$columns = array('id' => 'id', 'parent' => 'parent', 'left' => 'left', 'right' => 'right', 'name' => 'name');
$tableExample2 = 'traversal_example_2';
$defaultOrd = array(0 => 'R', 'X', 'G', 'L', 'V', '7', '6', 'J', 'E', 'I', 'C', 'N', 'Q', 'S', '3', 'M', 'B', 'K', '0');
$traversal_example = new Table($tableExample2, $columns, $alias = 't', $enabledTransaction = False);
$traversal = new TraversalTree($traversal_example, '');

$selectTreeSQL = $traversal->selectTreeSqlFactory();
$selectTreeSQL->select("`$alias`.`name`");
$traversal->selectTree($selectTreeSQL);
$flatTree = $traversal->getTreeFromDB();

checkWholeTable($flatTree, $defaultOrd);




$traversal->setId(3);
$traversal->selectLeftRight();
$selectBranchSQL = $traversal->selectBranchSqlFactory();
$selectBranchSQL->select("`$alias`.`name`");
$traversal->selectBranch($selectBranchSQL);
$flatTree = $traversal->getTreeFromDB();

checkWholeTable($flatTree, $defaultOrd, $movement = 7);




$traversal->emptyNodesValues();
$traversal->setId(13);
// $traversal->selectLeftRight();
$includingNode = True;
$fromRoot = True;
$selectPathSQL = $traversal->selectPathSqlFactory($includingNode, $fromRoot);
$selectPathSQL->select("`$alias`.`name`");
$selectPathSQL->select("`$alias`.`left`");
$selectPathSQL->select("`$alias`.`right`");
$traversal->selectPath($includingNode, $fromRoot, $selectPathSQL);
$list = $traversal->getList();
$result = array(0 => 'R', 'J', 'N');

$leftRightValues = array();
foreach($list as $ord => $node) {
	Assert::same( $result[$ord], $node['name'] );
	Assert::notContains( $node['left'], $leftRightValues );
	Assert::notContains( $node['right'], $leftRightValues );
	$leftRightValues[] = $node['left'];
	$leftRightValues[] = $node['right'];
}




$depth = $traversal->selectDepth();
Assert::same( $depth, 2 );


$level = $traversal->selectLevel();
Assert::same( $level, 3 );


$traversal->setId(1);
$numberOfSubnodes = $traversal->selectNumOfSubnodes();
Assert::same( $numberOfSubnodes, 18 );


dibi::begin();


$traversal->setId(2);
$traversal->deleteNode();
unset($defaultOrd[1]);
$defaultOrd = array_values($defaultOrd);

$selectTreeSQL = $traversal->selectTreeSqlFactory();
$selectTreeSQL->select("`$alias`.`id`");
$selectTreeSQL->select("`$alias`.`name`");
$selectTreeSQL->select("`$alias`.`parent`");
$selectTreeSQL->select("`$alias`.`left`");
$selectTreeSQL->select("`$alias`.`right`");
$traversal->selectTree($selectTreeSQL);
$flatTree = $traversal->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$traversal->setNode( array('name' => 'A') );
$traversal->chooseTarget(1);
$traversal->insertNode();

$traversal->selectTree($selectTreeSQL);
$flatTree = $traversal->getTreeFromDB();

//printWholeTable($flatTree);

array_unshift( $defaultOrd, 'A' );
checkWholeTable($flatTree, $defaultOrd);




/** updateBranch() **/

// $traversal->chooseTarget($id = 0, $parentID = 4, $position = 0);
$traversal->chooseTargetAsChild(4);
$traversal->setId(3);
$traversal->selectLeftRight();
$traversal->updateBranch();

$defaultOrd = array(0 => 'A', 'R', 'G', 'L', 'V', '7', '6', 'S', '3', 'M', 'B', 'K', '0', 'J', 'E', 'I', 'C', 'N', 'Q');

$traversal->selectTree($selectTreeSQL);
$flatTree = $traversal->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$traversal->setId(3);
$traversal->chooseTarget($id = 1);
$traversal->selectLeftRight();
$traversal->updateBranch();

$defaultOrd = array(0 => 'A', 'J', 'E', 'I', 'C', 'N', 'Q', 'R', 'G', 'L', 'V', '7', '6', 'S', '3', 'M', 'B', 'K', '0');

$traversal->selectTree($selectTreeSQL);
$flatTree = $traversal->getTreeFromDB();

//printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$traversal->setId(3);
// $traversal->chooseTarget($id = 0, $parentID = 17, $position = 0);
$traversal->chooseTargetAsChild(17);
$traversal->selectLeftRight();
$traversal->updateBranch();

$defaultOrd = array(0 => 'A', 'R', 'G', 'L', 'V', '7', '6', 'S', '3', 'M', 'B', 'J', 'E', 'I', 'C', 'N', 'Q', 'K', '0');

$traversal->selectTree($selectTreeSQL);
$flatTree = $traversal->getTreeFromDB();

//printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$rootID = dibi::fetchSingle("SELECT `id` FROM `$tableExample2` WHERE `name` = %s", "A");
$traversal->chooseTarget($rootID);
$traversal->setId(4);

Assert::exception(function() use ($traversal) {
	$traversal->updateBranch();
}, 'SakuraNotSupportedException');


$traversal->chooseTarget(1);
$traversal->updateBranch();

$defaultOrd = array(0 => 'A', 'S', '3', 'M', 'B', 'J', 'E', 'I', 'C', 'N', 'Q', 'K', '0', 'R', 'G', 'L', 'V', '7', '6');

$traversal->selectTree($selectTreeSQL);
$flatTree = $traversal->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




/** chooseTarget **/

Assert::exception(function() use ($traversal) {
	$traversal->chooseTarget($id = 4294967295); // that's enough ;-)
}, 'SakuraRowNotFoundException');

Assert::exception(function() use ($traversal) {
	$traversal->chooseTarget(0);
}, 'DomainException');

dibi::rollback();
