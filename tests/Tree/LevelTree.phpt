<?php

/**
* Testing of LevelTree
* @author Nikolas Lada
*/

use Tester\Assert;
use Sakura\Table\Table;
use Sakura\Tree\Tree;
use Sakura\Tree\LevelTree;

require_once __DIR__ . '/../../tests_config.php';
require_once __DIR__ . '/../../src/SakuraException.php';
require_once __DIR__ . '/../../src/Table/Table.php';
require_once __DIR__ . '/../../src/Table/NumberedColumns.php';
require_once __DIR__ . '/../../src/Tree/ITreeDriver.php';
require_once __DIR__ . '/../../src/Tree/GeneralTree.php';
require_once __DIR__ . '/../../src/Tree/LevelTree.php';


function checkWholeTable($flatTree, $defaultOrd, $movement = 0)  {

	foreach($flatTree as $ord => $node) {
		Assert::same( $defaultOrd[($ord + $movement)], $node['name'] );

		foreach($flatTree as $key => $row) {
			if($ord == $key) break;

			Assert::notSame( $node->toArray(), $row->toArray() ); // find duplicates
		}
	}
}


function printWholeTable($flatTree) {

	echo "id | name | L1 | L2 | L3 | L4 | L5 | L6 | L7\n";
	foreach($flatTree as $node) {
		echo $node['id'] . " ";
		echo $node['name'] . " ";
		echo $node['L1'] . " ";
		echo $node['L2'] . " ";
		echo $node['L3'] . " ";
		echo $node['L4'] . " ";
		echo $node['L5'] . " ";
		echo $node['L6'] . " ";
		echo $node['L7'] . "\n";
	}
}


$columns = array('id' => 'id', 'name' => 'name');
$tableExample2 = 'level_example_2';
$table_level_example = new Table($dibiConnection, $tableExample2, $columns, $alias = 'l', $enabledTransaction = False);
$table_level_example->addNumbered('L', 'L', 7, 1, 'int');
$level = new LevelTree($dibiConnection, $table_level_example, 'L');

$defaultOrd = array(0 => 'R', 'X', 'G', 'L', 'V', '7', '6', 'J', 'E', 'I', 'C', 'N', 'Q', 'S', '3', 'M', 'B', 'K', '0');

$selectTreeSQL = $level->selectTreeSqlFactory($selectid = True);
$selectTreeSQL->select("`$alias`.`name`");
$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();

checkWholeTable($flatTree, $defaultOrd);




$level->setId(3);
$selectBranchSQL = $level->selectBranchSqlFactory($selectId = True);
$selectBranchSQL->select("`$alias`.`name`");
$level->selectBranch($selectBranchSQL);
$flatTree = $level->getTreeFromDB();

checkWholeTable($flatTree, $defaultOrd, $movement = 7);




$level->emptyNodesValues();
$level->setId(13);

$includingNode = True;
$fromRoot = True;
$selectPathSQL = $level->selectPathSqlFactory($includingNode, $fromRoot);
$selectPathSQL->select("`$alias`.`name`");
$level->selectPath($includingNode, $fromRoot, $selectPathSQL);
$list = $level->getList();
$result = array(0 => 'R', 'J', 'N');

$i = 0;
foreach($list as $node) {
	Assert::same( $result[$i++], $node['name'] );
}




$depth = $level->selectDepth();
Assert::same( $depth, 2 );


$levelValue = $level->selectLevel();
Assert::same( $levelValue, 3 );


$level->setId(1);
$numberOfSubnodes = $level->selectNumOfSubnodes();
Assert::same( $numberOfSubnodes, 18 );

//print_r($defaultOrd);


$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$dibiConnection->begin();
$level->setId(9);
$level->deleteNode();


$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, array(0 => 'R', 'X', 'G', 'L', 'V', '7', 'J', 'E', 'I', 'C', 'N', 'Q', 'S', '3', 'M', 'B', 'K', '0'));
$dibiConnection->rollback();




$dibiConnection->begin();
$level->setId(3);
$level->deleteNode();


$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, array(0 => 'R', 'X', 'G', 'L', 'V', '7', '6', 'E', 'I', 'C', 'N', 'Q', 'S', '3', 'M', 'B', 'K', '0'));
$dibiConnection->rollback();




$dibiConnection->begin();
$level->setId(10);
$level->deleteNode();


$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, array(0 => 'R', 'X', 'G', 'L', 'V', '7', '6', 'J', 'I', 'C', 'N', 'Q', 'S', '3', 'M', 'B', 'K', '0'));
$dibiConnection->rollback();




$dibiConnection->begin();
$level->setId(4);
$level->deleteNode();


$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, array(0 => 'R', 'X', 'G', 'L', 'V', '7', '6', 'J', 'E', 'I', 'C', 'N', 'Q', '3', 'M', 'B', 'K', '0'));
$dibiConnection->rollback();




$dibiConnection->begin();
$level->setId(19);
$level->deleteNode();


$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, array(0 => 'R', 'X', 'G', 'L', 'V', '7', '6', 'J', 'E', 'I', 'C', 'N', 'Q', 'S', '3', 'M', 'B', 'K'));
$dibiConnection->rollback();




$dibiConnection->begin();
$level->setId(2);
$level->deleteNode();
unset($defaultOrd[1]);
$defaultOrd = array_values($defaultOrd);

$selectTreeSQL = $level->selectTreeSqlFactory($selectId = True);
$selectTreeSQL->select("`$alias`.`name`");
$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);



$level->emptyNodesValues();
$level->setNode( array('name' => 'A') );
$level->chooseTarget(1);
$level->insertNode();

$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();

// printWholeTable($flatTree);

array_unshift( $defaultOrd, 'A' );
checkWholeTable($flatTree, $defaultOrd);




/** updateBranch() **/

$level->setId(3);
// $level->chooseTarget($id = 0, $parentID = 4, $position = 0);
$level->chooseTargetAsChild(4);
$level->updateBranch();

$defaultOrd = array(0 => 'A', 'R', 'G', 'L', 'V', '7', '6', 'S', '3', 'M', 'B', 'K', '0', 'J', 'E', 'I', 'C', 'N', 'Q');

$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$level->setId(3);
$level->chooseTarget($id = 1);
$level->updateBranch();

$defaultOrd = array(0 => 'A', 'J', 'E', 'I', 'C', 'N', 'Q', 'R', 'G', 'L', 'V', '7', '6', 'S', '3', 'M', 'B', 'K', '0');

$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$level->setId(3);
// $level->chooseTarget($id = 0, $parentID = 17, $position = 0);
$level->chooseTargetAsChild(17);
$level->updateBranch();

$defaultOrd = array(0 => 'A', 'R', 'G', 'L', 'V', '7', '6', 'S', '3', 'M', 'B', 'J', 'E', 'I', 'C', 'N', 'Q', 'K', '0');

$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$rootID = $dibiConnection->fetchSingle("SELECT `id` FROM `$tableExample2` WHERE `name` = %s", "A");
$level->chooseTarget($rootID);
$level->setId(4);
/*
Assert::exception(function() use ($level) {
	$level->updateBranch();
}, '\Sakura\SakuraNotSupportedException');
*/


$level->chooseTarget(1);
$level->updateBranch();

$defaultOrd = array(0 => 'A', 'S', '3', 'M', 'B', 'J', 'E', 'I', 'C', 'N', 'Q', 'K', '0', 'R', 'G', 'L', 'V', '7', '6');

$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();
// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);



$level->setId(1);
$level->chooseTarget(4);
$level->updateBranch();

$defaultOrd = array(0 => 'A', 'R', 'G', 'L', 'V', '7', '6', 'S', '3', 'M', 'B', 'J', 'E', 'I', 'C', 'N', 'Q', 'K', '0');

$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();
// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$level->setId(4);
// $level->chooseTarget($id = 0, $parentID = 6, $position = 0);
$level->chooseTargetAsChild(6);
$level->updateBranch();

$defaultOrd = array(0 => 'A', 'R', 'G', 'L', 'S', '3', 'M', 'B', 'J', 'E', 'I', 'C', 'N', 'Q', 'K', '0', 'V', '7', '6');

$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();
// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$level->setId(8);
$level->chooseTarget(6);
$level->updateBranch();

$defaultOrd = array(0 => 'A', 'R', 'G', '7', 'L', 'S', '3', 'M', 'B', 'J', 'E', 'I', 'C', 'N', 'Q', 'K', '0', 'V', '6');

$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();
// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$level->setId(9);
$level->chooseTarget(6);
$level->updateBranch();

$defaultOrd = array(0 => 'A', 'R', 'G', '7', '6', 'L', 'S', '3', 'M', 'B', 'J', 'E', 'I', 'C', 'N', 'Q', 'K', '0', 'V');

$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();
// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$level->setId(13);
$level->chooseTarget(5);
$level->updateBranch();

$defaultOrd = array(0 => 'A', 'R', 'N', 'G', '7', '6', 'L', 'S', '3', 'M', 'B', 'J', 'E', 'I', 'C', 'Q', 'K', '0', 'V');

$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();
// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$level->setId(17);
// $level->chooseTarget($id = 0, $parentID = 4, $position = 0);
$level->chooseTargetAsChild(4);
$level->updateBranch();

$defaultOrd = array(0 => 'A', 'R', 'N', 'G', '7', '6', 'L', 'S', '3', 'M', 'K', '0', 'B', 'J', 'E', 'I', 'C', 'Q', 'V');

$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();
// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$level->setId(9);
// $level->chooseTarget($id = 0, $parentID = 4, $position = 2);
$level->chooseTargetAsChild($id = 4, $position = 2);
$level->updateBranch();

$defaultOrd = array(0 => 'A', 'R', 'N', 'G', '7', 'L', 'S', '3', '6', 'M', 'K', '0', 'B', 'J', 'E', 'I', 'C', 'Q', 'V');

$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();
// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$level->setId(11);
// $level->chooseTarget($id = 0, $parentID = 4, $position = 7);
$level->chooseTargetAsChild($id = 4, $position = 7);
$level->updateBranch();

$defaultOrd = array(0 => 'A', 'R', 'N', 'G', '7', 'L', 'S', '3', '6', 'M', 'K', '0', 'B', 'J', 'E', 'C', 'Q', 'I', 'V');

$level->selectTree($selectTreeSQL);
$flatTree = $level->getTreeFromDB();
// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$level->setId(1);
$level->chooseTarget($id = 13);

Assert::exception(function() use ($level) {
	$level->updateBranch();
}, '\Sakura\SakuraNotSupportedException');




/** chooseTarget **/

Assert::exception(function() use ($level) {
	$level->chooseTarget($id = 4294967295); // that's enough ;-)
}, '\Sakura\SakuraRowNotFoundException');

Assert::exception(function() use ($level) {
	$level->chooseTarget(0);
}, '\DomainException');

$dibiConnection->rollback();
