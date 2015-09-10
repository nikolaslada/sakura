<?php

/**
* Testing of NumberedColumns
* @author Nikolas Lada
*/

use Tester\Assert;


require_once __DIR__ . '/../../tests_config.php';
require_once __DIR__ . '/../../src/Table/NumberedColumns.php';


$name = 'lvl';
$alias = 'l';
$max = 10;
$min = 1;
$type = 'int';
$table = 'levels';

$lvl = new NumberedColumns($name, $alias, $max, $min, $type);

Assert::same( $max, $lvl->getMax() );
Assert::same( $min, $lvl->getMin() );
Assert::same( $name, $lvl->getName() );

$rows = array();
for($i = 1; $i<=10; $i++) {
	$rows[($name . $i)] = 13;
}

$DF1 = new DibiFluent(dibi::getConnection());
$DF1->select(False)->from("`$table` AS `$alias`");
$DF1 = $lvl->setSelect($DF1, $from = NULL, $to = 2);
$select = "SELECT `$alias`.`lvl1` , `$alias`.`lvl2` FROM `$table` AS `$alias`";
Assert::same( $select, $DF1->__toString() );

$DF1 = $lvl->setOrderBy($DF1, $from = NULL, $to = 2, True);
$orderBy = " ORDER BY `$alias`.`lvl1` ASC , `$alias`.`lvl2` ASC";
$sql = $select . $orderBy;
Assert::same( $sql, $DF1->__toString() );

$lvl->setList($rows);
$DF1 = $lvl->setWhere($DF1, $from = 8, $to = 10, '=', $useAnd = True);
$where = " WHERE `$alias`.`lvl8` = 13 AND `$alias`.`lvl9` = 13 AND `$alias`.`lvl10` = 13";
$sql = $select . $where . $orderBy;
Assert::same( $sql, $DF1->__toString() );

$DF2 = new DibiFluent(dibi::getConnection());
$DF2->update("`$table`");
$DF2 = $lvl->setUpdate($DF2, 1, 1, 1, 0);
$DF2 = $lvl->setUpdate($DF2, 10, NULL, 1, 0);
$sql = "UPDATE `$table` SET `$alias`.`$name" . "10` = `$alias`.`$name" . "9`";
Assert::same( $sql, $DF2->__toString() );

$DF3 = new DibiFluent(dibi::getConnection());
$DF3->update("`$table`");
$DF3 = $lvl->setUpdate($DF3, 8, NULL, 2, 0);
$sql = 'UPDATE `levels` SET `l`.`lvl10` = `l`.`lvl8` , `l`.`lvl9` = `l`.`lvl7` , `l`.`lvl8` = `l`.`lvl6`';
Assert::same( $sql, $DF3->__toString() );

$DF4 = new DibiFluent(dibi::getConnection());
$DF4->update("`$table`");
$DF4 = $lvl->setUpdate($DF4, 1, NULL, 1, 3);
$sql = 'UPDATE `levels` SET `l`.`lvl10` = `l`.`lvl9`+3 , `l`.`lvl9` = `l`.`lvl8`+3 , `l`.`lvl8` = `l`.`lvl7`+3 , `l`.`lvl7` = `l`.`lvl6`+3 , `l`.`lvl6` = `l`.`lvl5`+3 , `l`.`lvl5` = `l`.`lvl4`+3 , `l`.`lvl4` = `l`.`lvl3`+3 , `l`.`lvl3` = `l`.`lvl2`+3 , `l`.`lvl2` = `l`.`lvl1`+3';
Assert::same( $sql, $DF4->__toString() );

$DF5 = new DibiFluent(dibi::getConnection());
$DF5->update("`$table`");
$values = array(1 => 1, 2 => 1, 3 => 0);
$sql = 'UPDATE `levels` SET `l`.`lvl1` = 1 , `l`.`lvl2` = 1 , `l`.`lvl3` = 0';
$DF5 = $lvl->setUpdateValues($DF5, $values, $from = 1, $to = 3);
Assert::same( $sql, $DF5->__toString() );

$L = new NumberedColumns($name = 'l', $alias = 't', $max, $min, $type = NULL);

$DF6 = new DibiFluent(dibi::getConnection());
$DF6->update("`$table`");

$rows2 = array();
for($i = 1; $i<=10; $i++) {
	$rows2[$i] = 13;
}

$DF6 = $L->setUpdateValues($DF6, $rows2, $from = 1, $to = 10);
$sql = 'UPDATE `levels` SET `t`.`l1` = 13 , `t`.`l2` = 13 , `t`.`l3` = 13 , `t`.`l4` = 13 , `t`.`l5` = 13 , `t`.`l6` = 13 , `t`.`l7` = 13 , `t`.`l8` = 13 , `t`.`l9` = 13 , `t`.`l10` = 13';
Assert::same( $sql, $DF6->__toString() );

$rows2[11] = 1;
Assert::exception(function() use ($L, $DF6, $rows2) {
	$L->setUpdateValues($DF6, $rows2, $from = 1, $to = 11);
}, 'OutOfRangeException');

$DF7 = new DibiFluent(dibi::getConnection());
$DF7->update("`$table`");
$row3 = array();
$row3[1] = '1';
$row3[2] = '2';

$DF7 = $L->setUpdateValues($DF7, $row3, $from = 1, $to = 2);
// the $L instance can detect type
// this can be wrong in real app
Assert::same( "UPDATE `levels` SET `t`.`l1` = '1' , `t`.`l2` = '2'", $DF7->__toString() );

// the $lvl instance has got disable detecting of type
// so setUpdateValues() method will add integers
$DF7 = $lvl->setUpdateValues($DF7, $row3, $from = 1, $to = 2);
Assert::same( "UPDATE `levels` SET `t`.`l1` = '1' , `t`.`l2` = '2' , `l`.`lvl1` = 1 , `l`.`lvl2` = 2", $DF7->__toString() );