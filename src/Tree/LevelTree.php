<?php

namespace Sakura\Tree;

use Dibi\Connection;
use Dibi\Fluent;
use Sakura\Table\Table;
use Dibi\Row;
/**
* This file is part of the Sakura project.
* Copyright (c) 2015 Nikolas Lada
*/

class LevelTree extends GeneralTree implements ITreeDriver {

    /** @var array */
    protected	$whereTo;

    /** @var bool For $this->chooseTarget. It helps to avoid some quries. */
    private     $isMax;

    /** @var bool For $this->chooseTarget. It helps to determine changes in structure. */
    private     $isParent;


    /**
    * Constructor of LevelTree.
    * @param Connection $connection
    * @param Table $table
    * @return void
    */
    public function __construct(Connection $connection, Table $table) {

        parent::__construct($connection, $table);
        $this->isMax = False;
    }




    /** Setters and setting function **/


    /**
    * It sets levels into $this->node array.
    * @param array Pass numeric array of level values from FUNCTION.
    * @return void
    * @throws InvalidArgumentException
    * @throws DomainException
    */
    public function setLevels ($array) {

        if (!is_array($array) and ! $array instanceof Row) {
            throw new \InvalidArgumentException('Parameter $array must be an array or DibiRow object. Type of value is ' . gettype($array) . '.');
        }

        $addName = $this->table->getNameOfNumbered();
        foreach($array as $k => $v) {
            if (!is_int($v)) {
                throw new \DomainException('A value of passed $array is not integer. Current type is ' . gettype($v) . '.');
            }

            if(is_int($k)) {
                $this->node[($addName . $k)] = $v;
            } elseif (is_string($k)) {
            $this->node[$k] = $v;
            } else {
                throw new \DomainException('A key of passed $array is not integer or string. Current type is ' . gettype($k) . '.');
            }
        }
    }


    /**
    * It Sets target ($this->whereTo) for insertNode and updateBranch.
    * @param int If integer > 0 is passed, it choose node with this id.
    * @return void
    * @throws DomainException
    * @throws SakuraRowNotFoundException
    */
    public function chooseTarget ($id) {

        if($id < 1) {
            throw new \DomainException("Pass  \$id > 0. Passed value: $id.");
        }

        $this->isParent = True;
        $this->getNodesPositions($id);
    }


    /**
    * It Sets target ($this->whereTo) for insertNode and updateBranch.
    * @param int Pass integer > 0. It gets some information from parent node.
    * @param int If integer > 0 is passed, it adds information about position - this will be not check. If integer is 0, it will finds first free position and will use less SQL update later. For first free position is always better choice.
    * @return void|NULL
    * @throws DomainException
    * @throws SakuraRowNotFoundException
    */
    public function chooseTargetAsChild ($id, $position = 0) {

        if($id < 1 or $position < 0) {
            throw new \DomainException("Pass \$id > 0 and \$position >= 0 together. Values: $id; $position.");
        }

        $this->isParent = False;
        $nodesPositions = $this->getNodesPositions($id);

        if($position > 0) {
            $this->whereTo[$nodesPositions[1]] = $position;
            return NULL;
        }

        $columnPrefix = $this->table->getNameOfNumbered();
        $firstNull = $nodesPositions[1];
        $alias = $this->table->getAlias();

        $Fluent = $this->connection->select("MAX(`$alias`.`$columnPrefix$firstNull`)+1")
            ->from("`" . $this->table->getName() . "` AS `$alias`");

        $Fluent = $this->table->setNumberedWhere($Fluent, $this->table->getMinNumbered(), $nodesPositions[0]);
        $max = $Fluent->fetchSingle();

        if(!$max) {
            throw new \Sakura\SakuraNoRowReturnedException("Look at query.", 0, $this->table->getName(), $this->connection->$sql);
        }

        $this->whereTo[$firstNull] = $max;
        $this->isMax = True;
    }


    private function getNodesPositions ($id) {

        $Fluent = $this->table->setNumberedSelect($this->baseSqlFactory(), $from = NULL, $to = NULL);
        $node = $Fluent->where("`" . $this->table->getAlias() . "`.`{$this->id}` = %i", $id)->fetch();

        if(!$node) {
            throw new \Sakura\SakuraRowNotFoundException("Row not found.", 0, $this->table->getName(), $id );
        }

        $this->table->setNumberedList($node);
        $firstNull = $this->getPositionOfFirstNull($node);
        if(is_null($firstNull)) {
            $firstNull = $this->table->getMaxNumbered();
            $lastNotNull = $firstNull;
        } else $lastNotNull = $firstNull - 1;

        $this->whereTo = $this->table->getNumberedList(1, $lastNotNull);
        return array(0 => $lastNotNull, 1 => $firstNull);
    }


    private function getLastNonEmptyLevel ($startLevel) {

        $Fluent = $this->baseSqlFactory();
        $Fluent->select("1");
        $Fluent = $this->table->setNumberedWhere($Fluent, $this->table->getMinNumbered(), $startLevel++);

        $max = $this->table->getMaxNumbered();
        if($startLevel > $max) {
            throw new \Sakura\SakuraBadColumnException("There is no level $startLevel!");
        }

        for( ; $startLevel <= $max; $startLevel++) {
            $clone = clone $Fluent;
            $clone->where("`" . $this->table->getAlias() . "`.`" . $this->table->getNameOfNumbered() . "$startLevel` = %i", 1);

            if(!$clone->fetchSingle()) {
                return $startLevel - 1;
            }
        }

        return $startLevel - 1;
    }




    /** SQL Selects **/


    /**
    * It selects node by earlier passed ID via $this->getId(). Then it saves selected node to property $this->node or passes to $this->setLevels($node).
    * @see $this->setId()
    * @see $this->baseSqlFactory()
    * @param Fluent|NULL You can pass your Fluent instance, then it will save result to $this->node property. Or you can skip it, NULL will be passed and result will be passed into $this->setLevels().
    * @return void
    * @throws SakuraRowNotFoundException
    */
    public function selectNode ($Fluent = NULL) {

        if(!$Fluent instanceof Fluent) {
            $setLevels = True;
            $Fluent = $this->baseSqlFactory();
            $Fluent = $this->table->setNumberedSelect($Fluent, $from = NULL, $to = NULL);
        }

        $node = $Fluent->where("`" . $this->table->getAlias() . "`.`{$this->id}` = %i", $this->getId())->fetch();
        if (!$node) {
            throw new \Sakura\SakuraRowNotFoundException("Row not found.", 0, $this->table->getName(), $this->getId());
        }

        $this->node = $node;
        if (isset($setLevels)) {
            $this->setLevels($node);
        }
    }


    /**
    * @param int Pass ID of node which will be goal - where to move.
    * @param int Pass position of  You can skip it, then
    * @return void
    * @throws InvalidArgumentException
    * @throws SakuraRowNotFoundException
    */
    protected function selectNodeWhereTo ($nodeID, $position = 0) {

        if(!is_int($nodeID)) {
            throw new \InvalidArgumentException('Type of "$nodeID" must be integer. Type of "$nodeID" is ' . gettype($nodeID) . '.');
        }

        $Fluent = $this->baseSqlFactory();
        $Fluent = $this->table->setNumberedSelect($Fluent, $from = NULL, $to = NULL);
        $alias = $this->table->getAlias();
        $node = $Fluent->where("`$alias`.`{$this->id}` = %i", $nodeID)->fetch();
        if(!$node) {
            throw new \Sakura\SakuraRowNotFoundException("Row not found.", 0, $table, $this->getId());
        }

        $this->setLevels($node);
        $firstNull = $this->getPositionOfFirstNull($this->getNode());
        $lastNotNull = $firstNull - 1;
        $this->whereTo = $this->table->getNumberedList(1, $lastNotNull);

        if(!$position) {
            $columnName = $this->table->getNameOfNumbered();
            $Fluent = $this->connection->select("SELECT MAX(`$columnName$firstNull`)+1");
            $Fluent = $this->setNumberedWhere($Fluent, 1, $lastNotNull);
            $this->whereTo[$firstNull] = $Fluent->fetchSingle();
            $this->isMax = True;
        } elseif(is_int($position)) {
            $this->whereTo[$firstNull] = $position;
        } else {
            throw new \InvalidArgumentException('Type of "$position" must be integer. Or pass empty value. Type of "$position" is ' . gettype($position) . '.');
        }
    }


    /**
    * It will select whole tree and then will save result to $this->treeFromDB property.
    * @see $this->selectTreeSqlFactory()
    * @param Fluent You can pass your edited Fluent instance from $this->selectTreeSqlFactory(). You can skip it.
    * @return void
    * @throws SakuraNoRowReturnedException
    */
    public function selectTree ($Fluent = NULL) {

        if (!$Fluent instanceof Fluent) {
            $Fluent = $this->selectTreeSqlFactory();
        }

        $this->treeFromDB = $Fluent->fetchAll();
        if (empty($this->treeFromDB)) {
            throw new \Sakura\SakuraNoRowReturnedException("There is no tree in table.", 0, $this->table->getName(), $this->connection->$sql);
        }
    }


    /**
    * It will select a branch and will save it into $this->treeFromDB property.
    * @see $this->setId()
    * @see $this->selectBranchSqlFactory()
    * @param Fluent You can pass your edited Fluent instance from $this->selectBranchSqlFactory(). You can skip it too.
    * @return void
    * @throws SakuraRowNotFoundException
    */
    public function selectBranch ($Fluent = NULL) {

        $alias = $this->table->getAlias();
        $rootFluent = $this->connection->select(False)
            ->from("`" . $this->table->getName() . "` AS `$alias`")
            ->where("`$alias`.`{$this->id}` = %i", $this->getId());
        $rootFluent = $this->table->setNumberedSelect($rootFluent, $from = NULL, $to = NULL);
        $root = $rootFluent->fetch();
        if (!$root) {
            throw new \Sakura\SakuraRowNotFoundException('Row not found.', 0, $this->table->getName(), $this->getId());
        }

        $lastNotNull = $this->getPositionOfFirstNull($root) - 1;
        if (!$Fluent instanceof Fluent) {
            $Fluent = $this->selectBranchSqlFactory();
        }

        $Fluent = $this->table->setNumberedSelect($Fluent, $lastNotNull, $to = NULL);
        $this->table->setNumberedList($root);
        $Fluent = $this->table->setNumberedWhere($Fluent, $this->table->getMinNumbered(), $lastNotNull);
        $this->treeFromDB = $Fluent->fetchAll();
    }


    /**
    * It selects path between node and root.
    * @see $this->setId()
    * @see $this->selectPathSqlFactory()
    * @param bool If True is passed it adds ending node.
    * @param bool If True is passed it sorts nodes from root to ending node.
    * @param Fluent Pass your Fluent object.
    * @return void
    * @throws SakuraException
    */
    public function selectPath ($includingNode = True, $fromRoot = True, $Fluent = NULL) {

        if (!$Fluent instanceof Fluent) {
            $Fluent = $this->selectPathSqlFactory($includingNode, $fromRoot);
        }

        $firstNodeDF = $this->table->setNumberedSelect(clone $Fluent, NULL, NULL);
        $this->selectNode($firstNodeDF);
        $node = $this->getNode();

        $currentColumn = $this->getPositionOfFirstNull($node) - 1;
        $alias = $this->table->getAlias();
        $name = $this->table->getNameOfNUmbered();
        $min = $this->table->getMinNumbered();
        $max = $this->table->getMaxNumbered();
        $this->list = array();

        for($i = $min; $i < $currentColumn; $i++) {
            $DF = clone $Fluent;

            for($j = $min; $j <= $i; $j++) {
                $DF->where("`$alias`.`$name$j` = %i", $node[$name . $j]);
            }

            for($j; $j < $max; $j++) {
                $DF->where("`$alias`.`$name$j` = %i", 0);
            }

            $this->list[$i] = $DF->fetch();
        }

        if ($includingNode) {
            $this->list[$currentColumn] = $node;
        }

        if (count($this->list) + (!$includingNode) != $currentColumn) {
            throw new \Sakura\SakuraException('Property $this->list has got less records than should be contain!');
        }
        if ($fromRoot) {
            ksort($this->list);
        }
    }


    /**
    * It searches for first 0 from beginning in passed $node. IF no 0 is founded, it returns NULL.
    * @param array|DibiRow
    * @return int|NULL
    * @throws SakuraBadColumnException
    */
    private function getPositionOfFirstNull ($node) {

        foreach($node as $key => $value) {
            if(empty($value)) {
                $position = ltrim($key, $this->table->getNameOfNumbered());
                if($position != $key) {
                    if(!is_numeric($position)) {
                        throw new \Sakura\SakuraBadColumnException("Passed \$node's key '$key' is not valid, '$position' is not numeric.");
                    }

                    return intval($position);
                }
            }
        }

        return NULL;
    }


    /**
    *   It returns depth of node.
    *   @see $this->setId()
    *   @return int
    */
    public function selectDepth () {

        $this->selectNode();
        return $this->getPositionOfFirstNull($this->getNode()) - 2;
    }


    /**
    *   It returns level of node.
    *   @see $this->setId()
    *   @return int
    */
    public function selectLevel () {

        $this->selectNode();
        return $this->getPositionOfFirstNull($this->getNode()) - 1;
    }


    /**
    * It returns number of subnodes.
    * @see  $this->setId()
    * @return int
    * @throws SakuraNoRowReturnedException
    */
    public function selectNumOfSubnodes () {

        $this->selectNode();
        $node = $this->getNode();
        $this->table->setNumberedList($node);
        $currentColumn = $this->getPositionOfFirstNull($node);
        if(is_null($currentColumn)) {
            $lastNotNull = $this->table->getMaxNumbered();
        } else {
            $lastNotNull = $currentColumn - 1;
        }

        $alias = $this->table->getAlias();
        $forCount = $this->table->getNumberedList($lastNotNull, $lastNotNull);
        $Fluent = $this->connection->select("COUNT(`$alias`.`" . $this->table->getNameOfNumbered() . key($forCount) . "`)")
            ->from("`" . $this->table->getName() . "` AS `$alias`");
        $Fluent = $this->table->setNumberedWhere($Fluent, $this->table->getMinNumbered(), $lastNotNull);
        $subnodesWithRoot = $Fluent->fetchSingle();
        if($subnodesWithRoot === False) {
            throw new \Sakura\SakuraNoRowReturnedException('No row returned!', 0, $this->table->getName(), $this->connection->$sql);
        }

        return $subnodesWithRoot - 1;
    }




    /** INSERTS, UPDATES and DELETES **/


    /**
    * Insert one node to DB and update table.
    * @see $this->setNode()
    * @see $this->chooseTarget
    * @return void
    * @throws SakuraBadTargetException
    * @throws SakuraBadColumnException
    * @throws SakuraNotSupportedException
    * @throws DibiException
    */
    public function insertNode () {

        try {
            if($this->table->getEnabledTransaction()) {
                $this->connection->begin();
            }

            $node = $this->getNode();
            $whereTo = $this->getNodeWhereTo();

            if(!$this->isMax) {
                $count = count($whereTo);
                $this->table->setNumberedList($whereTo);

                if($this->isParent) {
                    $newColumn = $count + 1;
                    $firstEmptyLevel = $this->getLastNonEmptyLevel($newColumn) + 1;

                    if($newColumn > $this->table->getMaxNumbered()) {
                        throw new \Sakura\SakuraBadTargetException("Cannot insert new under choosed node!", 0, $this->table->getName(), $node, $whereTo);
                    }

                    $Fluent = $this->table->setNumberedUpdate($newColumn, $firstEmptyLevel, $movement = 1);
                    $Fluent->set("`" . $this->table->getAlias() . "`.`" . $this->table->getNameOfNumbered() . "$count` = %i", 1);
                    $Fluent = $this->table->setNumberedWhere($Fluent, $this->table->getMinNumbered(), $count, '=');

                } else {
                    $Fluent = $this->table->setNumberedUpdate($count, $count, $movement = 0, $add = 1);
                    $Fluent = $this->table->setNumberedWhere($Fluent, $this->table->getMinNumbered(), ($count - 1) );
                    $Fluent = $this->table->setNumberedWhere($Fluent, $count, $count, '>=');
                }

                $Fluent->execute();
            }

            if($node instanceof Row) {
                $node = $node->toArray();
            }

            if(isset($node[$this->id])) {
                unset($node[$this->id]);
            }

            $name = $this->table->getNameOfNumbered();
            foreach($whereTo as $k => $v) {
                    $node[$name . $k] = $v;
            }

            $this->connection->query('INSERT INTO `' . $this->table->getName() . '` %v', $node);
            if($this->connection->affectedRows() != 1) {
                throw new \Sakura\SakuraNotSupportedException('After inserting node a value of affectedRows() is not equal to 1!');
            }

            if($this->table->getEnabledTransaction()) {
                $this->connection->commit();
            }
        } catch(\Exception $e) {
            if($this->table->getEnabledTransaction()) {
                $this->connection->rollback();
            }

            throw $e;
        }
    }


    /**
    * Delete one node from DB and update table.
    * @see $this->setId()
    * @return void
    * @throws SakuraRowNotFoundException
    * @throws SakuraBadColumnException
    * @throws SakuraNotSupportedException
    * @throws DibiException
    */
    public function deleteNode () {

        try {
            if($this->table->getEnabledTransaction()) {
                $this->connection->begin();
            }

            $this->selectNode();
            $node = $this->getNode();
            $this->table->setNumberedList($node);

            $firstNull = $this->getPositionOfFirstNull($node);
            if($firstNull == 2) {
                throw new \Sakura\SakuraNotSupportedException("Cannot delete root!");
            }

            $this->connection->query("DELETE FROM `" . $this->table->getName() . "` WHERE `{$this->id}` = %i", $this->getId());

            $affectedRows = 0;
            $alias = $this->table->getAlias();

            if(!is_null($firstNull)) {
                $lastNotNull = $firstNull - 1;
                $name = $this->table->getNameOfNumbered();
                $min = $this->table->getMinNumbered();

                $max = $this->baseSqlFactory();
                $max->select("MAX(`$name$firstNull`)");
                $max = $this->table->setNumberedWhere($max, $min, $lastNotNull);
                $max = $max->fetchSingle();

                if($max > 1 and $lastNotNull == $min) {
                    throw new \Sakura\SakuraNotSupportedException("Cannot delete root!", 0, $this->table->getName());
                }

                $newValue = $node[ $name . $lastNotNull ] - 1;

                if($max) {
                    if($max > 1) {
                        $max--;
                        $move = $this->connection->command()->update("`" . $this->table->getName() . "` AS `$alias`");
                        $move->set("`$alias`.`$name$lastNotNull` = `$alias`.`$name$lastNotNull`+$max");
                        $move = $this->table->setNumberedWhere($move, $min, $lastNotNull - 1);
                        $move->where("`$alias`.`$name$lastNotNull` > %i", $node[ $name . $lastNotNull ]);
                        $move->execute();
                    }

                    $endOfBranch = $this->getLastNonEmptyLevel($lastNotNull);

                    if($endOfBranch >= $lastNotNull + 1) {
                        $fromRightToLeft = $this->connection->command()->update("`" . $this->table->getName() . "` AS `$alias`");
                        $fromRightToLeft = $this->table->setNumberedWhere($fromRightToLeft, $min, $lastNotNull);
                        $fromRightToLeft = $this->table->setNumberedUpdate($lastNotNull, $firstNull, $movement = -1, $newValue, $fromRightToLeft);
                        $fromRightToLeft = $this->table->setNumberedUpdate($firstNull, $endOfBranch, $movement = -1, 0, $fromRightToLeft);
                        $fromRightToLeft->set("`$alias`.`$name$endOfBranch` = 0");
                        $fromRightToLeft->execute();

                        $affectedRows = $this->connection->affectedRows();
                    }

                } else {
                    $back = $this->connection->command()->update("`" . $this->table->getName() . "` AS `$alias`");
                    $back->set("`$alias`.`$name$lastNotNull` = `$alias`.`$name$lastNotNull`-1");
                    $back = $this->table->setNumberedWhere($back, $min, $lastNotNull - 1);
                    $back->where("`$alias`.`$name$lastNotNull` > %i", $newValue);
                    $back->execute();
                }


            } else {

                $back = $this->connection->command()->update("`" . $this->table->getName() . "` AS `$alias`");
                $back->set("`$alias`.`$name$lastNotNull` = `$alias`.`$name$lastNotNull`-1");
                $back = $this->table->setNumberedWhere($back, $this->table->getMinNumbered(), $lastNotNull - 1);
                $back->where("`$alias`.`$name$lastNotNull` > %i", $nodesLastNumber);
                $back->execute();
            }

            if($this->table->getEnabledTransaction()) {
                $this->connection->commit();
            }

        } catch(\Exception $e) {
            if($this->table->getEnabledTransaction()) {
                $this->connection->rollback();
            }

            throw $e;
        }
    }


    /**
    * It move node/branch from one place to other place. You can't take branch and move to its place where is subbranch/subnode!
    * @see $this->setId()
    * @see $this->chooseTarget()
    * @return void
    * @throws SakuraRowNotFoundException
    * @throws DibiException
    */
    public function updateBranch () {

        try {
            if($this->table->getEnabledTransaction()) {
                $this->connection->begin();
            }

            $this->selectNode();
            $whereTo = $this->getNodeWhereTo();
            $from = $this->getNode();
            if($from instanceof Row) {
                $from = $from->toArray();
            }

            $lastNotNullWhereTo = count($this->whereTo);
            $firstNullFrom = $this->getPositionOfFirstNull($from);

            $max = $this->table->getMaxNumbered();
            $min = $this->table->getMinNumbered();
            $alias = $this->table->getAlias();
            $name = $this->table->getNameOfNumbered();
            $firstNullWhereTo = $lastNotNullWhereTo + 1;
            $add = 0;

            if(is_null($firstNullFrom)) {
                $firstNullFrom = $max;
                $lastNotNullFrom = $max;
            } else $lastNotNullFrom = $firstNullFrom - 1;

            $allSame = True;
            for($i = $min; $i <= $lastNotNullFrom; $i++) {
                if(isset($whereTo[$i])) {
                    if($from[$name . $i] != $whereTo[$i]) {
                        $allSame = False;
                        break;
                    }
                } else {
                    $allSame = False;
                    break;
                }
            }

            if($allSame) {
                throw new \Sakura\SakuraNotSupportedException("Cannot move superior node to its descendant!");
            }

            if($this->isMax) {
                $this->isMax = False; // reset to default
            } else {
                $move = $this->connection->command()->update("`" . $this->table->getName() . "` AS `$alias`");
                $move->set("`$alias`.`$name$lastNotNullWhereTo` = `$alias`.`$name$lastNotNullWhereTo`+1");
                $move = $this->table->setNumberedWhere($move, $min, ($lastNotNullWhereTo - 1));
                $move->and("`$alias`.`$name$lastNotNullWhereTo` >= %i", $whereTo[$lastNotNullWhereTo]);
                $move->execute();

                $allSame = True;
                for($i = $min; $i < $lastNotNullWhereTo; $i++) {
                    if($whereTo[$i] != $from[$name . $i]) {
                        $allSame = False;
                        break;
                    }
                }

                if($allSame and $whereTo[$lastNotNullWhereTo] <= $from[$name . $lastNotNullWhereTo]) {
                    $from[$name . $lastNotNullWhereTo]++;
                }
            }

            $diff = $lastNotNullWhereTo - $lastNotNullFrom;


            if($diff < 0) {
                $this->table->setNumberedList($from);
                if($lastNotNullFrom == $max) {
                    $endOfBranch = $lastNotNullFrom;
                    $DF = NULL;
                } else {
                    $endOfBranch = $this->getLastNonEmptyLevel($lastNotNullFrom);
                    if($endOfBranch == $lastNotNullFrom) {
                        $DF = NULL;
                    } else {
                        $DF = $this->table->setNumberedUpdate($firstNullWhereTo, $endOfBranch, $diff, $add);
                    }
                }

                $DF = $this->table->setNumberedUpdateValues($whereTo, $min, $lastNotNullWhereTo, $DF);

                $DF = $this->table->setNumberedWhere($DF, $min, $lastNotNullFrom);

                for($i = ($endOfBranch + $diff + 1); $i <= $endOfBranch; $i++) {
                    $DF->set("`$alias`.`$name$i` = %i", 0);
                }

            } elseif($diff == 0) {

                $DF = $this->table->setNumberedUpdateValues($whereTo, $min, $lastNotNullWhereTo);

                $this->table->setNumberedList($from);
                $DF = $this->table->setNumberedWhere($DF, $min, $lastNotNullFrom);

            } else {

                $this->table->setNumberedList($from);
                $endOfBranch = $this->getLastNonEmptyLevel($lastNotNullFrom);

                $this->table->setNumberedList($whereTo);

                if($diff + $endOfBranch > $max) {
                    throw new \Sakura\SakuraBadTargetException("Cannot move current branch under choosed node!", 0, $this->table->getName(), $from, $whereTo);
                }

                $DF = $this->table->setNumberedUpdate($firstNullFrom, ($endOfBranch + $diff), $diff, $add);

                $DF = $this->table->setNumberedUpdateValues($whereTo, $min, $lastNotNullWhereTo, $DF);

                $this->table->setNumberedList($from);

                $DF = $this->table->setNumberedWhere($DF, $min, $lastNotNullFrom);
            }

            $DF->execute();

            $repair = $this->connection->command()->update("`" . $this->table->getName() . "` AS `$alias`");
            $repair->set("`$alias`.`$name$lastNotNullFrom` = `$alias`.`$name$lastNotNullFrom`-1");

            $repair = $this->table->setNumberedWhere($repair, $min, ($lastNotNullFrom - 1));

            $repair = $this->table->setNumberedWhere($repair, $lastNotNullFrom, $lastNotNullFrom, '>=');
            $repair->execute();

            if ($this->table->getEnabledTransaction()) {
                $this->connection->commit();
            }
        } catch(\Dibi\Exception $e) {
            if ($this->table->getEnabledTransaction()) {
                $this->connection->rollback();
            }

            throw $e;
        }
    }


    /**
    * It returns generally set Fluent instance.
    * @return Fluent
    */
    public function baseSqlFactory ($selectId = False) {

        if ($selectId) {
            $DF = $this->connection->select("`" . $this->table->getAlias() . "`.`{$this->id}`");
        } else {
            $DF = $this->connection->select(False);
        }

        return $DF->from('`' . $this->table->getName() . '` AS `' . $this->table->getAlias() . '`');
    }


    /**
    * It returns Fluent instance for select tree.
    * @return Fluent
    */
    public function selectTreeSqlFactory ($selectId = False) {

        if ($selectId) {
            $DF = $this->connection->select("`" . $this->table->getAlias() . "`.`{$this->id}`");
        } else {
            $DF = $this->connection->select(False);
        }

        $DF->from('`' . $this->table->getName() . '` AS `' . $this->table->getAlias() . '`');
        $DF = $this->table->setNumberedSelect($DF, $from = NULL, $to = NULL);
        return $this->table->setNumberedOrderBy($DF, $from = NULL, $to = NULL);
    }


    /**
    * It returns Fluent instance for select branch.
    * @return Fluent
    */
    public function selectBranchSqlFactory ($selectId = False) {

        if ($selectId) {
            $DF = $this->connection->select("`" . $this->table->getAlias() . "`.`{$this->id}`");
        } else {
            $DF = $this->connection->select(False);
        }

        $DF->from('`' . $this->table->getName() . '` AS `' . $this->table->getAlias() . '`');
        return $this->table->setNumberedOrderBy($DF, $from = NULL, $to = NULL);
    }


    public function selectPathSqlFactory ($includingNode, $fromRoot, $selectId = False) {

        if ($selectId) {
            return $this->connection->select("`" . $this->table->getAlias() . "`.`{$this->id}`")->from('`' . $this->table->getName() . '` AS `' . $this->table->getAlias() . '`');
        } else {
            return $this->connection->select(False)->from('`' . $this->table->getName() . '` AS `' . $this->table->getAlias() . '`');
        }
    }




    /** Getters **/


    public function getParent () {

        throw new \Sakura\SakuraNotSupportedException("Cannot return 'parent' column. There is no 'parent' column in Level method.");
    }


    /** DO GeneralTree
    * It returns $this->whereTo property.
    * @return array
    * @throws LogicException
    */
    public function getNodeWhereTo () {

        if (!isset($this->whereTo)) {
            throw new \LogicException('There is nothing to return. Call "$this->chooseTarget()" before.');
        }

        return $this->whereTo;
    }

}
