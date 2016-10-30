<?php

/**
* Testing of OrderTree
* @author Nikolas Lada
*/

use Tester\Assert;
use Sakura\Table\Table;
use Sakura\Tree\Tree;
use Sakura\Tree\OrderTree;

require_once __DIR__ . '/../../tests_config.php';
require_once __DIR__ . '/../../src/SakuraException.php';
require_once __DIR__ . '/../../src/Table/Table.php';
require_once __DIR__ . '/../../src/Tree/ITreeDriver.php';
require_once __DIR__ . '/../../src/Tree/GeneralTree.php';
require_once __DIR__ . '/../../src/Tree/OrderTree.php';


function checkWholeTable($flatTree, $defaultOrd, $movement = 0)  {

	$orderValues = array();
	foreach($flatTree as $ord => $node) {
		Assert::same( $defaultOrd[($ord + $movement)], $node['name'] );
		Assert::notContains( $node['order'], $orderValues );
		$orderValues[] = $node['order'];
	}
}


function printWholeTable($flatTree) {

	echo "id | name | parent | depth | order \n";
	foreach($flatTree as $node) {
		echo $node['id'] . " ";
		echo $node['name'] . " ";
		echo $node['parent'] . " ";
		echo $node['depth'] . " ";
		echo $node['order'] . "\n";
	}
}


$columns = array('id' => 'id', 'parent' => 'parent', 'depth' => 'depth', 'order' => 'order', 'name' => 'name');
$orderExample2 = 'order_example_2';
$defaultOrd = array(0 => 'R', 'X', 'G', 'L', 'V', '7', '6', 'J', 'E', 'I', 'C', 'N', 'Q', 'S', '3', 'M', 'B', 'K', '0');
$order_example = new Table($dibiConnection, $orderExample2, $columns, $alias = 'o', $enabledTransaction = False);
$order = new OrderTree($dibiConnection, $order_example, '');

$selectTreeSQL = $order->selectTreeSqlFactory();
$selectTreeSQL->select("`$alias`.`name`");
$selectTreeSQL->select("`$alias`.`order`");
$order->selectTree($selectTreeSQL);
$flatTree = $order->getTreeFromDB();

checkWholeTable($flatTree, $defaultOrd);




$order->setId(3);
$order->selectOrder(True);
$selectBranchSQL = $order->selectBranchSqlFactory();
$selectBranchSQL->select("`$alias`.`name`");
$selectBranchSQL->select("`$alias`.`order`");
$order->selectBranch($selectBranchSQL);
$flatTree = $order->getTreeFromDB();

checkWholeTable($flatTree, $defaultOrd, $movement = 7);




$order->emptyNodesValues();
$order->setId(13);
$includingNode = True;
$fromRoot = True;
$selectPathSQL = $order->selectPathSqlFactory($includingNode, $fromRoot);
$selectPathSQL->select("`$alias`.`name`");
$selectPathSQL->select("`$alias`.`order`");
$order->selectPath($includingNode, $fromRoot, $selectPathSQL);
$list = $order->getList();
$result = array(0 => 'R', 'J', 'N');

$orderValues = array();
$i = 0;

foreach($list as $node) {
	Assert::same( $result[$i], $node['name'] );
	Assert::notContains( $node['order'], $orderValues );
	$orderValues[] = $node['order'];
	$i++;
}


$depth = $order->selectDepth();
Assert::same( $depth, 2 );


$level = $order->selectLevel();
Assert::same( $level, 3 );


$order->setId(1);
$numberOfSubnodes = $order->selectNumOfSubnodes();
Assert::same( $numberOfSubnodes, 18 );


$dibiConnection->begin();


$order->setId(2);
$order->deleteNode();
unset($defaultOrd[1]);
$defaultOrd = array_values($defaultOrd);

$selectTreeSQL = $order->selectTreeSqlFactory();
$selectTreeSQL->select("`$alias`.`id`");
$selectTreeSQL->select("`$alias`.`name`");
$selectTreeSQL->select("`$alias`.`parent`");
$selectTreeSQL->select("`$alias`.`order`");
$order->selectTree($selectTreeSQL);
$flatTree = $order->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$order->setNode( array('name' => 'A') );
$order->chooseTarget(1);
$order->insertNode();

// checkTree($order, $alias, $defaultOrd);

$order->selectTree($selectTreeSQL);
$flatTree = $order->getTreeFromDB();

// printWholeTable($flatTree);

array_unshift( $defaultOrd, 'A' );
checkWholeTable($flatTree, $defaultOrd);




/** updateBranch() **/

// $order->chooseTarget($id = 0, $parentID = 4, $position = 0);
$order->chooseTargetAsChild(4);
$order->setId(3);
$order->selectOrder(True, True);
$order->updateBranch();

$defaultOrd = array(0 => 'A', 'R', 'G', 'L', 'V', '7', '6', 'S', '3', 'M', 'B', 'K', '0', 'J', 'E', 'I', 'C', 'N', 'Q');

$order->selectTree($selectTreeSQL);
$flatTree = $order->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$order->setId(3);
$order->chooseTarget($id = 1);
$order->selectOrder(True, True);
$order->updateBranch();

$defaultOrd = array(0 => 'A', 'J', 'E', 'I', 'C', 'N', 'Q', 'R', 'G', 'L', 'V', '7', '6', 'S', '3', 'M', 'B', 'K', '0');

$order->selectTree($selectTreeSQL);
$flatTree = $order->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$order->setId(3);
// $order->chooseTarget($id = 0, $parentID = 17, $position = 0);
$order->chooseTargetAsChild(17);
$order->selectOrder(True, True);
$order->updateBranch();

$defaultOrd = array(0 => 'A', 'R', 'G', 'L', 'V', '7', '6', 'S', '3', 'M', 'B', 'J', 'E', 'I', 'C', 'N', 'Q', 'K', '0');

$order->selectTree($selectTreeSQL);
$flatTree = $order->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$rootID = $dibiConnection->fetchSingle("SELECT `id` FROM `$orderExample2` WHERE `name` = %s", "A");
$order->chooseTarget($rootID);
$order->setId(4);


Assert::exception(function() use ($order) {
	$order->updateBranch();
}, '\Sakura\SakuraNotSupportedException');




$order->chooseTarget(1);
$order->updateBranch();

$defaultOrd = array(0 => 'A', 'S', '3', 'M', 'B', 'J', 'E', 'I', 'C', 'N', 'Q', 'K', '0', 'R', 'G', 'L', 'V', '7', '6');

$order->selectTree($selectTreeSQL);
$flatTree = $order->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$order->setId(11);
$order->chooseTargetAsChild($id = 1, $position = 3);
$order->updateBranch();

$defaultOrd = array(0 => 'A', 'S', '3', 'M', 'B', 'J', 'E', 'C', 'N', 'Q', 'K', '0', 'R', 'G', 'L', 'I', 'V', '7', '6');

$order->selectTree($selectTreeSQL);
$flatTree = $order->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);




$order->setId(17);
$order->chooseTargetAsChild($id = 1, $position = 5);
$order->updateBranch();

$defaultOrd = array(0 => 'A', 'S', '3', 'M', 'K', '0', 'R', 'G', 'L', 'I', 'V', 'B', 'J', 'E', 'C', 'N', 'Q', '7', '6');

$order->selectTree($selectTreeSQL);
$flatTree = $order->getTreeFromDB();

// printWholeTable($flatTree);

checkWholeTable($flatTree, $defaultOrd);





/** chooseTarget **/

Assert::exception(function() use ($order) {
	$order->chooseTarget($id = 4294967295); // that's enough ;-)
}, '\Sakura\SakuraRowNotFoundException');

Assert::exception(function() use ($order) {
	$order->chooseTarget(0);
}, '\DomainException');

$dibiConnection->rollback();