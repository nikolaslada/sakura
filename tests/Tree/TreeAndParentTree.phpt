<?php

/**
* Testing of ParentTree
* @author Nikolas Lada
*/

use Tester\Assert;
use Sakura\Table\Table;
use Sakura\Tree\Tree;
use Sakura\Tree\ParentTree;

require_once __DIR__ . '/../../tests_config.php';
require_once __DIR__ . '/../../src/SakuraException.php';
require_once __DIR__ . '/../../src/Table/Table.php';
require_once __DIR__ . '/../../src/Tree/Tree.php';
require_once __DIR__ . '/../../src/Tree/ITreeDriver.php';
require_once __DIR__ . '/../../src/Tree/GeneralTree.php';
require_once __DIR__ . '/../../src/Tree/ParentTree.php';


function checkWholeTable($flatTree, $defaultOrd, $movement = 0)  {

	foreach($flatTree as $ord => $node) {
		Assert::same( $defaultOrd[($ord + $movement)], $node['name'] );
	}
}


function printWholeTable($flatTree) {

	echo "id | name | parent \n";
	foreach($flatTree as $node) {
		echo $node['id'] . " ";
		echo $node['name'] . " ";
		echo $node['parent'] . "\n";
	}
}


$columns = array('id' => 'id', 'parent' => 'parent', 'name' => 'name');
$tableExample2 = 'parent_example_2';
$defaultOrd = array(0 => 'R', 'X', 'G', 'L', 'V', '7', '6', 'J', 'E', 'I', 'C', 'N', 'Q', 'S', '3', 'M', 'B', 'K', '0');
$tableParentE2 = new Table($dibiConnection, $tableExample2, $columns, $alias = 'p', $enabledTransaction = False);

$parent = new Tree($dibiConnection, $tableParentE2, $driver = 'parent');

$selectTreeSQL = $parent->selectTreeSqlFactory();
$selectTreeSQL->select("`$alias`.`parent`");
$selectTreeSQL->select("`$alias`.`name`");
$parent->selectTree($selectTreeSQL);
$flatTree = $parent->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$parent->setId(3);
$selectBranchSQL = $parent->selectBranchSqlFactory();
$selectBranchSQL->select("`$alias`.`parent`");
$selectBranchSQL->select("`$alias`.`name`");
$parent->selectBranch($selectBranchSQL);
$flatTree = $parent->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd, $movement = 7);




$parent->emptyNodesValues();
$parent->emptyList();
$parent->setId(13);
$includingNode = True;
$fromRoot = True;
$selectPathSQL = $parent->selectPathSqlFactory($includingNode, $fromRoot);
$selectPathSQL->select("`$alias`.`name`");
$parent->selectPath($includingNode, $fromRoot, $selectPathSQL);
$list = $parent->getList();
$result = array(0 => 'R', 'J', 'N');

$i = 0;
foreach($list as $node) {
	Assert::same( $result[$i++], $node['name'] );
}

$parent->emptyList();




$depth = $parent->selectDepth();
Assert::same( $depth, 2 );


$level = $parent->selectLevel();
Assert::same( $level, 3 );


$parent->setId(1);
$numberOfSubnodes = $parent->selectNumOfSubnodes();
Assert::same( $numberOfSubnodes, 18 );


$dibiConnection->begin();


$parent->setId(2);
$parent->deleteNode();

$defaultOrd = array(0 => 'R', '6', '7', 'G', 'J', 'C', 'E', 'I', 'N', 'Q', 'L', 'S', '0', '3', 'B', 'K', 'M', 'V');

$selectTreeSQL = $parent->selectTreeSqlFactory();
$selectTreeSQL->select("`$alias`.`name`");
$selectTreeSQL->select("`$alias`.`parent`");
$selectTreeSQL->orderBy("`$alias`.`name`");
$parent->selectTree($selectTreeSQL);
$flatTree = $parent->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$parent->setNode( array('name' => 'A') );
$parent->chooseTarget($id = 1);
$parent->insertNode();

$parent->selectTree($selectTreeSQL);
$flatTree = $parent->getTreeFromDB();

// printWholeTable($flatTree);

array_unshift( $defaultOrd, 'A' );
checkWholeTable($flatTree, $defaultOrd);




/** updateBranch() **/

// $parent->chooseTarget($id = 0, $parentID = 4, $position = 0);
$parent->chooseTargetAsChild(4);
$parent->setId(3);
$parent->updateBranch();

$defaultOrd = array(0 => 'A', 'R', '6', '7', 'G', 'L', 'S', '0', '3', 'B', 'J', 'C', 'E', 'I', 'N', 'Q', 'K', 'M', 'V');

$parent->selectTree($selectTreeSQL);
$flatTree = $parent->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$parent->setId(3);
$parent->chooseTarget($id = 1);
$parent->updateBranch();

$defaultOrd = array(0 => 'A', 'J', 'C', 'E', 'I', 'N', 'Q', 'R', '6', '7', 'G', 'L', 'S', '0', '3', 'B', 'K', 'M', 'V');

$parent->selectTree($selectTreeSQL);
$flatTree = $parent->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$parent->setId(3);
// $parent->chooseTarget($id = 0, $parentID = 17, $position = 0);
$parent->chooseTargetAsChild(17);
$parent->updateBranch();

$defaultOrd = array(0 => 'A', 'R', '6', '7', 'G', 'L', 'S', '0', '3', 'B', 'J', 'C', 'E', 'I', 'N', 'Q', 'K', 'M', 'V');

$parent->selectTree($selectTreeSQL);
$flatTree = $parent->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$rootID = $dibiConnection->fetchSingle("SELECT `id` FROM `$tableExample2` WHERE `name` = %s", "A");
$parent->chooseTarget($rootID);
$parent->setId(4);
/*
Assert::exception(function() use ($parent) {
	$parent->updateBranch();
}, '\Sakura\SakuraNotSupportedException');
*/

$parent->chooseTarget(1);
$parent->updateBranch();

$defaultOrd = array(0 => 'A', 'R', '6', '7', 'G', 'L', 'V', 'S', '0', '3', 'B', 'J', 'C', 'E', 'I', 'N', 'Q', 'K', 'M');

$parent->selectTree($selectTreeSQL);
$flatTree = $parent->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




/** chooseTarget **/

Assert::exception(function() use ($parent) {
	$parent->chooseTarget($id = 4294967295); // that's enough ;-)
}, '\Sakura\SakuraRowNotFoundException');

Assert::exception(function() use ($parent) {
	$parent->chooseTarget(0);
}, '\DomainException');

$dibiConnection->rollback();

/*
$traversal_example = new Table($name = 'traversal_example', $columns, $alias = 't', $enabledTransaction = True);
$tree = new Tree($traversal_example, 't');
*/

//$tree = new Table('tree', array(), '', True);

//Assert::notTrue($traversal->getEnabledTransaction());

/*$tree->increaseOffset();
Assert::same( 1, $tree->getOffset() );*/
