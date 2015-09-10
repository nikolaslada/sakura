<?php

/**
* Testing of TreeGenerating
* @author Nikolas Lada
*/

use Tester\Assert;


require_once __DIR__ . '/../../tests_config.php';
require_once __DIR__ . '/../../src/SQLPerformer/TreeGenerator.php';


Assert::same( 1, 1 );

$intervals = array(
	0 => array(0 => 1, 1 => 3, 2 => 4, 3 => 5),
	1 => array(0 => 3, 1 => 4, 2 => 5, 3 => 10),
);

$tree = new TreeGenerator(30, 3, $intervals);
$tree->setTableNames('', '_example_1');
$tree->generateTree();
$tree->setTraversalAndOrderValues();
$tree->setLevelValues();
$tree->traversalInsert(1000);
$tree->orderInsert(1000);
$tree->parentInsert(1000);
$tree->levelInsert($maxLvl = 4, 1000);
$tree->checkTables();