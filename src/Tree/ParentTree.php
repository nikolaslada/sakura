<?php

namespace Sakura\Tree;

use Dibi\Connection;
use Dibi\Fluent;
use Sakura\Table\Table;
/**
* This file is part of the Sakura project.
* Copyright (c) 2015 Nikolas Lada
*/

class ParentTree extends GeneralTree implements ITreeDriver {

    protected   $parent;

    public function __construct(Connection $connection, Table $table) {

        parent::__construct($connection, $table);
        $columns = $this->table->getColumns();
        $this->parent = $columns['parent'];
    }


    /**
    * It Sets target ($this->whereTo) for insertNode and updateBranch.
    * @param int If integer > 0 is passed, it choose node with this id.
    * @return void
    * @throws DomainException
    * @throws SakuraRowNotFoundException
    */
    public function chooseTarget ($id) {

        if ($id < 1) {
            throw new \DomainException("Pass  \$id > 0. Passed value: $id.");
        }

        $targetParent = $this->connection->fetchSingle("SELECT `{$this->parent}` FROM `" . $this->table->getName() . "` WHERE `{$this->id}` = %i", $id);
        if ($targetParent === False) {
            throw new \Sakura\SakuraRowNotFoundException("Row not found!", 0, $this->table->getName(), $id);
        }

        $this->whereTo = array($this->parent => $targetParent);
    }


    /**
    * It Sets target ($this->whereTo) for insertNode and updateBranch.
    * @param int Pass integer > 0. It gets some information from parent node.
    * @param int It has no effect.
    * @return void
    * @throws DomainException
    */
    public function chooseTargetAsChild ($id, $position = 0) {

        if ($id < 1) {
            throw new \DomainException("Pass  \$id > 0. Passed value: $id.");
        }

        $target = $this->connection->fetchSingle("SELECT 1 FROM `" . $this->table->getName() . "` WHERE `{$this->id}` = %i", $id);
        if ($target === False) {
            throw new \Sakura\SakuraRowNotFoundException("Row not found!", 0, $this->table->getName(), $id);
        }

        $this->whereTo = array($this->parent => $id);
    }




    /** SELECTS **/


    /**
    * It selects root's ID from ParentTree.
    * @return void|NULL
    * @throws SakuraNoRowReturnedException
    */
    public function selectRootId () {

        $this->rootId = $this->connection->fetchSingle("SELECT `{$this->id}` FROM `" . $this->table->getName() . "` WHERE `{$this->parent}` = 0", "%lmt", 1, "%ofs", 0);
        if ($this->rootId !== False) {
            return NULL;
        }

        $this->rootId = $this->connection->fetchSingle("SELECT `{$this->id}` FROM `" . $this->table->getName() . "` WHERE `{$this->parent}` IS NULL %lmt", 1, "%ofs", 0);
        if ($this->rootId !== False) {
            return NULL;
        }

        throw new \Sakura\SakuraNoRowReturnedException("There is no root in ParentTree!", 0, $this->table->getName(), 'dibi::$sql');
    }


    /**
    * It will select whole tree and then will save result to $this->treeFromDB property.
    * @see $this->selectTreeSqlFactory()
    * @param Fluent You can pass your edited Fluent instance from $this->selectTreeSqlFactory(). You can skip it too.
    * @return void
    * @throws SakuraRowNotFoundException
    */
    public function selectTree ($Fluent = NULL) {

        $this->selectRootId();
        $this->treeFromDB = array();
        if (!$Fluent instanceof Fluent) {
            $Fluent = $this->selectTreeSqlFactory();
        }

        $rootsDF = clone $Fluent;
        $this->treeFromDB[0] = $rootsDF->where("`" . $this->table->getAlias() . "`.`{$this->id}` = %i", $this->getRootId())->fetch();
        if (!$this->treeFromDB[0]) {
            throw new \Sakura\SakuraRowNotFoundException("Row not found!", 0, $this->table->getName(), $this->getRootId());
        }

        $this->selectSubnodesRecursion($this->rootId, $Fluent);
    }


    private function selectSubnodesRecursion ($root, $Fluent) {

        $DF = clone $Fluent;
        $subnodes = $DF->where("`" . $this->table->getAlias() . "`.`{$this->parent}` = %i", $root)->fetchAll();
        if (!$subnodes) {
            return NULL;
        }

        foreach($subnodes as $subnode) {
            $this->treeFromDB[] = $subnode;
            $this->selectSubnodesRecursion($subnode[$this->id], $Fluent);
        }
    }


    /**
    * It executes SQL selects and loads rows to $this->treeFromDB.
    * @see $this->selectBranchSqlFactory()
    * @param Fluent Put your Fluent from $this->selectBranchSqlFactory().
    * @return void
    * @throws SakuraRowNotFoundException
    */
    public function selectBranch ($Fluent = NULL) {

        $this->setRoot($this->getId());
        $this->treeFromDB = array();
        if (!$Fluent instanceof Fluent) {
            $Fluent = $this->selectBranchSqlFactory();
        }

        $root = $this->getRootId();
        $rootsDF = clone $Fluent;
        $this->treeFromDB[0] = $rootsDF->where("`" . $this->table->getAlias() . "`.`{$this->id}` = %i", $root)->fetch();
        if (!$this->treeFromDB[0]) {
            throw new \Sakura\SakuraRowNotFoundException("Row not found!", 0, $this->table->getName(), $root);
        }

        $this->selectSubnodesRecursion($root, $Fluent);
    }


    /**
    * It returns depth of node.
    * @see $this->setId()
    * @return int
    */
    public function selectDepth () {

        $this->selectPath(False, False, NULL);
        $count = count($this->list);
        unset($this->list);
        return $count;
    }


    /**
    * It returns level of node.
    * @see $this->setId()
    * @return int
    */
    public function selectLevel () {

        $this->selectPath(False, False, NULL);
        $count = count($this->list) + 1;
        unset($this->list);
        return $count;
    }


    /**
    * It returns number of subnodes from selects.
    * @see $this->setId()
    * @param int You can skip this.
    * @return int
    */
    public function selectNumOfSubnodes ($num = 0) {

        $subnodes = $this->connection->fetchAll("SELECT `{$this->id}` FROM `" . $this->table->getName() . "` WHERE `{$this->parent}` = %i", $this->getId());
        if (!$subnodes) {
            return $num;
        }

        foreach($subnodes as $subnode) {
            $this->setId($subnode[$this->id]);
            $num = $this->selectNumOfSubnodes(++$num);
        }

        return $num;
    }




    /** INSERTS, UPDATES and DELETES SQL **/


    /**
    * Insert one node to DB and update table.
    * @see $this->setNode()
    * @see $this->chooseTarget
    * @return void
    * @throws SakuraException
    */
    public function insertNode () {

        try {
            $table = $this->table->getName();
            if ($this->table->getEnabledTransaction()) {
                $this->connection->begin();
            }

            $node = $this->getNode();
            if (is_array($node)) {
                $values = array_merge($node, $this->getWhereTo());
            } else {
                $values = array_merge($node->toArray(), $this->getWhereTo());
            }

            $this->connection->query("INSERT INTO `$table` %v", $values);
            if ($this->connection->affectedRows() != 1) {
                throw new \Sakura\SakuraException('After inserting node a value of affectedRows() is not equal to 1!');
            }

            if(empty($values[$this->parent])) {
                $lastInsertId = $this->connection->getInsertId();
                $this->connection->query("UPDATE `$table` SET `{$this->parent}`= %i", $lastInsertId,
                    "WHERE %and",
                        array(
                            array("%or",
                                array(
                                    "`{$this->parent}` = 0",
                                    "`{$this->parent}` IS NULL",
                                ),
                            ),
                            array("`{$this->id}` != %i", $lastInsertId),
                        )
                    );
            }

            if ($this->table->getEnabledTransaction()) {
                $this->connection->commit();
            }
        } catch(\Exception $e) {
            if ($this->table->getEnabledTransaction()) {
                $this->connection->rollback();
            }

            throw $e;
        }
    }


    public function deleteNode () {

        $id = $this->getId();
        $table = $this->table->getName();
        try {
            if ($this->table->getEnabledTransaction()) {
                $this->connection->begin();
            }

            $parent = $this->connection->fetchSingle("SELECT `{$this->parent}` FROM `$table` WHERE `{$this->id}` = %i", $id);
            if ($parent === False) {
                throw new SakuraRowNotFoundException("No row to delete! Row not found.", 0, $table, $id);
            }

            $this->connection->query("UPDATE `$table` SET `{$this->parent}` = %i", $parent, "WHERE `{$this->parent}` = %i", $id);
            $this->connection->query("DELETE FROM `$table` WHERE `{$this->id}` = %i", $id);

            if ($this->table->getEnabledTransaction()) {
                $this->connection->commit();
            }
        } catch(\Exception $e) {
            if ($this->table->getEnabledTransaction()) {
                $this->connection->rollback();
            }
            throw $e;
        }

        unset($this->idValue);
    }


    /**
    * It move node/branch from one place to other place. You can't take branch and move to its place where is subbranch/subnode!
    * @see $this->setId()
    * @see $this->chooseTarget()
    * @throws DibiException
    * @throws LogicException
    */
    public function updateBranch () {

        try {
            $target = $this->getWhereTo();
            if ($this->table->getEnabledTransaction()) {
                $this->connection->begin();
            }

            $this->connection->query("UPDATE `" . $this->table->getName() . "` SET `{$this->parent}` = %i",
                $target[$this->parent], "WHERE `{$this->id}` = %i", $this->getId());

            if ($this->table->getEnabledTransaction()) {
                    $this->connection->commit();
            }
        } catch(\Exception $e) {
            if ($this->table->getEnabledTransaction()) {
                $this->connection->rollback();
            }
            throw $e;
        }

        unset($this->idValue);
        unset($this->whereTo);
    }




    /** SQL Factories **/


    public function baseSqlFactory ($selecdId = False) {

        return $this->selectNodeSqlFactory();
    }


    public function selectTreeSqlFactory ($selectId = False) {

        $alias = $this->table->getAlias();
        $DF = new Fluent($this->connection);
        return $DF->select("`$alias`.`{$this->id}`")->from('`' . $this->table->getName() . "` AS `$alias`");
    }


    public function selectBranchSqlFactory ($selectId = False) {

        return $this->selectTreeSqlFactory();
    }




    /** Getters and simple methods **/


    /**
    * It returns ID of root.
    * @return int
    * @throw LogicException
    */
    public function getRootId () {

        if (!isset($this->rootId)) {
            throw new \LogicException('Property $this->rootId must be set!');
        }

        return $this->rootId;
    }


    /**
    * It counts level on $this->list property through count() function. Result depends on passed parameters to $this->selectPath()!
    * @return int
    * @throw SakuraException
    */
    public function getLevel () {

        if (!isset($this->list)) {
            throw new \Sakura\SakuraException('$this->list property must be set! Call $this->selectPath() before.');
        }

        return count($this->list);
    }


    /**
    * It returns $this->whereTo property.
    * @return array
    * @throws LogicException
    * @throws SakuraBadColumnException
    */
    public function getWhereTo () {

        if (!isset($this->whereTo)) {
            throw new \LogicException('There is nothing to return. Call "$this->chooseTarget()" before.');
        }

        if(!isset($this->whereTo[$this->parent])) {
            throw new \Sakura\SakuraBadColumnException("Both parent and left keys must be set!", 0, '', array('parent' => $this->parent), 'notset');
        }

        return $this->whereTo;
    }

}
