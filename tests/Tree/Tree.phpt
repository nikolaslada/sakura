<?php

/**
* Testing of Table
* @author Nikolas Lada
*/

use Tester\Assert;


require_once __DIR__ . '/../../tests_config.php';
require_once __DIR__ . '/../../src/SakuraException.php';
require_once __DIR__ . '/../../src/Table/Table.php';
require_once __DIR__ . '/../../src/Tree/Tree.php';
require_once __DIR__ . '/../../src/Tree/GeneralTree.php';
require_once __DIR__ . '/../../src/Tree/TraversalTree.php';
require_once __DIR__ . '/../../src/Tree/OrderTree.php';


$orderColumns = array('id' => 'id', 'parent' => 'parent', 'depth' => 'depth', 'order' => 'order', 'name');
$traversalColumns = array('id' => 'id', 'parent' => 'parent', 'left' => 'left', 'right' => 'right', 'name');

$OrderTable = new Table($name = 'order_example_2', $orderColumns, $alias = '', $enabledTransaction = False);
$TraversalTable = new Table('traversal_example_2', $traversalColumns, '', True);

Assert::false($OrderTable->getEnabledTransaction());
Assert::true($TraversalTable->getEnabledTransaction());

$OrderTree = new Tree($OrderTable, $driver = 'order');
$TraversalTree = new Tree($TraversalTable, $driver = 't');

$TraversalTree->setId(1);
Assert::same( 1, $TraversalTree->getId() );