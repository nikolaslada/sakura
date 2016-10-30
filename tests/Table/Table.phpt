<?php

/**
* Testing of Table
* @author Nikolas Lada
*/

use Tester\Assert;
use Sakura\Table\Table;


require_once __DIR__ . '/../../tests_config.php';
require_once __DIR__ . '/../../src/Table/Table.php';


$columns = array('id' => 'id');
$columns[1] = 'column';

$test = new Table($dibiConnection, $name = 'order_example_2', $columns, $alias = '', $enabledTransaction = False);
$tree = new Table($dibiConnection, 'traversal_example_2', array(), '', True);

Assert::false($test->getEnabledTransaction());
Assert::true($tree->getEnabledTransaction());

$tree->detectColumns();
$columns = array('id', 'parent', 'left', 'right', 'name');
Assert::same( $columns, $tree->getColumns() );
Assert::same( $columns, $tree->getAllColumns() );

$required = array('id', 'parent');
$result = array('id' => 'id', 'parent' => 'parent');
$tree->checkColumns($required);
Assert::same( $result, $tree->getCheckedColumns() );

$tree->increaseOffset();
Assert::same( 1, $tree->getOffset() );