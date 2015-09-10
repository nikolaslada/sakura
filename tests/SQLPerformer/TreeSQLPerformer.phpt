<?php

/**
* Testing of TreeSQLPerformer
* @author Nikolas Lada
*/

use Tester\Assert;


require_once __DIR__ . '/../../tests_config.php';
require_once __DIR__ . '/../../src/SakuraException.php';
require_once __DIR__ . '/../../src/SQLPerformer/SQLPerformer.php';
require_once __DIR__ . '/../../src/SQLPerformer/TreeSQLPerformer.php';
require_once __DIR__ . '/../../src/Table/Table.php';
require_once __DIR__ . '/../../src/Table/NumberedColumns.php';
require_once __DIR__ . '/../../src/Tree/ITreeDriver.php';
require_once __DIR__ . '/../../src/Tree/GeneralTree.php';
require_once __DIR__ . '/../../src/Tree/TraversalTree.php';
require_once __DIR__ . '/../../src/Tree/OrderTree.php';
require_once __DIR__ . '/../../src/Tree/ParentTree.php';
require_once __DIR__ . '/../../src/Tree/LevelTree.php';


Assert::same( 1, 1 );

$enabledTransaction = False;
$printOutput = False;

$columns = array('id' => 'id', 'parent' => 'parent', 'left' => 'left', 'right' => 'right', 'name' => 'name');
$TraversalTable = new Table('traversal_example_2', $columns, $alias = 't', $enabledTransaction);
$TraversalTree = new TraversalTree($TraversalTable);

$performTraversal = new TreeSQLPerformer($TraversalTree, $printOutput);

$performTraversal->setStartTime();

$performTraversal->performSelectTree($columns);
$performTraversal->performSelectBranch($rootID = 2);
$performTraversal->performSelectPath($nodeID = 17);
$performTraversal->performSelectDepth($nodeID = 13);
$performTraversal->performSelectNumOfSubnodes($nodeID = 3);

$traversalTimes = $performTraversal->getTimes();




$columns = array('id' => 'id', 'parent' => 'parent', 'depth' => 'depth', 'order' => 'order', 'name' => 'name');
$OrderTable = new Table('order_example_2', $columns, $alias = 'o', $enabledTransaction);
$OrderTree = new OrderTree($OrderTable);


$performOrder = new TreeSQLPerformer($OrderTree, $printOutput);

$performOrder->setStartTime();

$performOrder->performSelectTree($columns);
$performOrder->performSelectBranch($rootID = 2);
$performOrder->performSelectPath($nodeID = 17);
$performOrder->performSelectDepth($nodeID = 13);
$performOrder->performSelectNumOfSubnodes($nodeID = 3);

$orderTimes = $performOrder->getTimes();




$columns = array('id' => 'id', 'parent' => 'parent', 'name' => 'name');
$ParentTable = new Table('parent_example_2', $columns, $alias = 'p', $enabledTransaction);
$ParentTree = new ParentTree($ParentTable);


$performParent = new TreeSQLPerformer($ParentTree, $printOutput);

$performParent->setStartTime();

$performParent->performSelectTree($columns);
$performParent->performSelectBranch($rootID = 2);
$performParent->performSelectPath($nodeID = 17);
$performParent->performSelectDepth($nodeID = 13);
$performParent->performSelectNumOfSubnodes($nodeID = 3);

$parentTimes = $performParent->getTimes();




$columns = array('id' => 'id', 'name' => 'name');
$LevelTable = new Table('level_example_2', $columns, $alias = 'l', $enabledTransaction);
$LevelTable->addNumbered('L', 'L', 7, 1, 'int');
$LevelTree = new LevelTree($LevelTable, 'L');

$performLevel = new TreeSQLPerformer($LevelTree, $printOutput);

$performLevel->setStartTime();

$performLevel->performSelectTree($columns);
$performLevel->performSelectBranch($rootID = 2);
$performLevel->performSelectPath($nodeID = 17);
$performLevel->performSelectDepth($nodeID = 13);
$performLevel->performSelectNumOfSubnodes($nodeID = 3);

$levelTimes = $performLevel->getTimes();