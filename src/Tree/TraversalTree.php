<?php

namespace Sakura\Tree;

use Sakura\Table\Table;
use Dibi\Connection;
use Dibi\Fluent;
/**
* This file is part of the Sakura project.
* Copyright (c) 2015 Nikolas Lada
*/

class TraversalTree extends GeneralTree implements ITreeDriver {

    /** @var string Name of parent column. */
    protected   $parent;

    /** @var string Name of left column. */
    protected   $left;

    /** @var string Name of right column. */
    protected   $right;

    /** @var array For getting depth. */
    protected   $intervals;


    /**
     * Constructor of TraversalTree.
     * @param Connection $connection
     * @param Table $table
     * @return void
     */
    public function __construct(Connection $connection, Table $table) {

        parent::__construct($connection, $table);
        $columns = $this->table->getColumns();
        $this->parent = $columns['parent'];
        $this->left = $columns['left'];
        $this->right = $columns['right'];
    }


    /**
    * Set 'left' and 'right' keys.
    * @param array|Dibi\Row Pass numeric values with appropriate keys.
    * @return void
    * @throws SakuraBadColumnException
    * @throws DomainException
    */
    public function setLeftRight ($node) {

        if(!isset($node[$this->left]) or !isset($node[$this->right])) {
            throw new \Sakura\SakuraBadColumnException("Both left and right keys must be set!", 0, '', array('left' => $this->left, 'right' => $this->right), 'notset');
        }

        if (!is_numeric($node[$this->left]) or ! is_numeric($node[$this->right])) {
            throw new \DomainException("Values under '{$this->left}' and '{$this->right}' keys must be integers or numeric strings! Values: {$node[$this->left]}; {$node[$this->right]}.");
        }

        $this->node[$this->left] = $node[$this->left];
        $this->node[$this->right] = $node[$this->right];
    }


    /**
    * It Sets target ($this->whereTo) for insertNode and updateBranch.
    * @param int If integer > 0 is passed, it choose node with this id. Otherwise it will use $parentID and $position parameters!
    * @return void
    * @throws DomainException
    * @throws SakuraRowNotFoundException
    * @throws SakuraNotImplementedException
    */
    public function chooseTarget ($id) {

        if($id < 1) {
            throw new \DomainException("Pass  \$id > 0. Passed value: $id.");
        }

        $table = $this->table->getName();
        $targetPL = $this->connection->fetch("SELECT `{$this->parent}`, `{$this->left}`, `{$this->right}`+2 AS `{$this->right}` FROM `$table` WHERE `{$this->id}` = %i", $id);

        if (!$targetPL) {
            throw new \Sakura\SakuraRowNotFoundException("Row not found!", 0, $table, $id);
        }

        $this->whereTo = $targetPL->toArray();
    }


    /**
    * It Sets target ($this->whereTo) for insertNode and updateBranch.
    * @param int Pass integer > 0. It gets some information from parent node.
    * @param int If integer is 0, it will finds first free position and will use less SQL update later. Values > 0 for exact position is not supported.
    * @return void
    * @throws DomainException
    * @throws SakuraRowNotFoundException
    * @throws SakuraNotImplementedException
    */
    public function chooseTargetAsChild($id, $position = 0) {

        if ($id < 1) {
            throw new \DomainException("Pass  \$id > 0. Passed value: $id.");
        }
        
        if($position != 0) {
            throw new \Sakura\SakuraNotImplementedException("This configuration for exact position (\$position > 0) is not implemented yet!");
        }

        $table = $this->table->getName();
        $targetPL = $this->connection->fetch("SELECT `{$this->right}` AS `{$this->left}`, `{$this->right}`+1 AS `{$this->right}`" .
            " FROM `$table` WHERE `{$this->id}` = %i", $id);

        if(!$targetPL) {
            throw new \Sakura\SakuraRowNotFoundException("Row not found!", 0, $table, $id);
        }

        $this->whereTo = $targetPL->toArray();
        $this->whereTo[$this->parent] = $id;
    }





    /** Selects **/


    /**
    * It selects 'left', 'right' and eventually 'parent' columns.
    * @see $this->setId().
    * @param bool True adds 'parent' column into select.
    * @return DibiRow
    */
    public function selectLeftRight ($addParent = False) {

        $sql = "SELECT `{$this->left}`, `{$this->right}`";
        if ($addParent) {
            $sql .= ",`{$this->parent}`";
        }

        $sql .= " FROM `" . $this->table->getName() . "` WHERE `{$this->id}`=%i";
        $this->node = $this->connection->fetch($sql, $this->getId());
    }


    /**
    * It executes SQL selects and loads rows to $this->treeFromDB as whole tree.
    * @see $this->selectTreeSqlFactory()
    * @param Fluent Pass NULL or your Fluent SQL from selectTreeSqlFactory().
    * @return void
    * @throws SakuraNoRowReturnedException
    */
    public function selectTree ($Fluent = NULL) {

        if(!$Fluent instanceof Fluent) {
            $Fluent = $this->selectTreeSqlFactory();
        }
        
        $this->treeFromDB = $Fluent->fetchAll();
        if(empty($this->treeFromDB)) {
            throw new \Sakura\SakuraNoRowReturnedException('There is no tree in table!', 0, $this->getTable()->getName(), 'dibi::$sql');
        }
    }


    /**
    * It executes SQL selects and loads rows to $this->treeFromDB as a branch.
    * @see $this->selectBranchSqlFactory()
    * @see $this->setLeftRight()
    * @see $this->setId()
    * @param Fluent Pass NULL or your Fluent SQL from selectBranchSqlFactory().
    * @return void|NULL
    * @throws SakuraNoRowReturnedException
    * @throws SakuraNotSupportedException
    */
    public function selectBranch ($Fluent = NULL) {

        $LR = $this->getLeftRight();
        if (!$Fluent instanceof Fluent) {
            $Fluent = $this->selectBranchSqlFactory();
        }
        
        $this->treeFromDB = $Fluent->where("`{$this->left}` >= %i", $LR[$this->left])->and("`{$this->right}` <= %i", $LR[$this->right])->fetchAll();
        if (!empty($this->treeFromDB)) {
            return NULL;
        }

        if ($LR[$this->left] + 1 < $LR[$this->right]) {
            throw new \Sakura\SakuraNoRowReturnedException("There is no branch in table!", 0, $this->table->getName(), 'dibi::$sql');
        } else {
            throw new \Sakura\SakuraNotSupportedException("Left ({$LR[$this->left]}) must be smaller than right ({$LR[$this->right]})!");
        }
    }


    /**
    * It generates set of rows and saves to $this->list. Each row represents every level in a tree between root and passed ID of node.
    * @see $this->selectPathSqlFactory()
    * @param bool If False is passed, it returns only superior nodes.
    * @param bool If True is passed, rows will be sorted by depth from root.
    * @param Fluent Pass NULL or your Fluent SQL from selectNodeSqlFactory().
    * @return void
    */
    public function selectPath ($includingNode = True, $fromRoot = True, $Fluent = NULL) {

        if(!$Fluent instanceof Fluent) {
            $Fluent = $this->selectPathSqlFactory($includingNode, $fromRoot);
        }

        $this->list = $Fluent->fetchAll();
    }


    /**
    * It returns depth of node.
    * @see $this->selectLeftRight() or $this->setLeftRight()
    * @return int
    * @throws SakuraBadColumnException
    */
    public function selectDepth () {

        $LR = $this->getLeftRight();
        $table = $this->table->getName();
        $depth = $this->connection->fetchSingle("SELECT COUNT(*) FROM `$table` WHERE `{$this->left}` < %i", $LR[$this->left], "AND `{$this->right}` > %i", $LR[$this->right]);
        
        if ($depth === False) {
            throw new \Sakura\SakuraBadColumnException('Counting rows: no row returned from table!', 0, $table, $LR, 'norow');
        }

        return $depth;
    }


    /**
    * It returns level of node.
    * @see $this->selectLeftRight() or $this->setLeftRight()
    * @return int
    * @throws SakuraBadColumnException
    */
    public function selectLevel () {

        $LR = $this->getLeftRight();
        $table = $this->table->getName();
        $level = $this->connection->fetchSingle("SELECT COUNT(*) FROM `$table` WHERE `{$this->left}` <= %i", $LR[$this->left], "AND `{$this->right}` >= %i", $LR[$this->right]);
        
        if ($level === False) {
            throw new \Sakura\SakuraBadColumnException('Counting rows: no row returned from table!', 0, $table, $LR, 'norow');
        }

        return $level;
    }


    /**
    * It returns number of subnodes of choosen node.
    * @see $this->setId()
    * @return int
    * @throws SakuraRowNotFoundException
    */
    public function selectNumOfSubnodes () {

        $shouldBeFloat = $this->connection->fetchSingle("SELECT (`{$this->right}`-`{$this->left}`-1)/2 FROM `" . $this->table->getName() . "` WHERE `{$this->id}` = %i", $this->getId());
        if ($shouldBeFloat === False) {
            throw new \Sakura\SakuraRowNotFoundException("Row not found.", 0, $this->table->getName(), $this->getId());
        }
        
        $num = intval($shouldBeFloat);
        if($shouldBeFloat - $num != 0.0) {
            $msg = "Broken Traversal Tree. After calculating (`right`-`left`-1)/2 was returned floating number with not null fractional part.";
            throw new \Sakura\SakuraNotSupportedException($msg, $this->table->getName());
        }

        return $num;
    }




    /** UPDATES and DELETES SQL **/


    /**
    * Insert one node to DB and update table.
    * @see $this->setNode()
    * @see $this->chooseTarget
    * @return void
    * @throws SakuraException
    */
    public function insertNode () {

        try {
            if($this->table->getEnabledTransaction()) {
                $this->connection->begin();
            }

            $LRP = $this->getWhereTo();
            $table = $this->table->getName();
            if ($LRP[$this->left] >= $LRP[$this->right]) {
                throw new \LogicException("Left ({$this->left}) column must be smaller than right ({$this->right}) column!");
            }

            if($LRP[$this->left] + 1 == $LRP[$this->right]) {
                $this->connection->query("UPDATE `$table` SET `{$this->left}` = `{$this->left}`+2 WHERE `{$this->left}` >= %i", $LRP[$this->left]);
                $this->connection->query("UPDATE `$table` SET `{$this->right}` = `{$this->right}`+2 WHERE `{$this->right}` >= %i", $LRP[$this->right]);
            } else {
                $rightEnd = $LRP[$this->right] - 2;
                $this->connection->query("UPDATE `$table` SET `{$this->right}` = `{$this->right}`+2 WHERE `{$this->right}` > $rightEnd");
                $this->connection->query("UPDATE `$table` SET `{$this->left}` = `{$this->left}`+2 WHERE `{$this->left}` > $rightEnd");
                $this->connection->query("UPDATE `$table` SET `{$this->left}` = `{$this->left}`+1, `{$this->right}` = `{$this->right}`+1".
                    " WHERE `{$this->left}` >= %i", $LRP[$this->left], " AND `{$this->right}` <= $rightEnd");
            }

            $node = $this->getNode();
            if (is_array($node)) {
                $values = array_merge($node, $LRP);
            } else {
                $values = array_merge($node->toArray(), $LRP);
            }

            $this->connection->query("INSERT INTO `$table` %v", $values);
            $affected = $this->connection->affectedRows();

            if($LRP[$this->left] + 1 != $LRP[$this->right]) {
            $rightEnd++;
            $this->connection->query("UPDATE `$table` SET `{$this->parent}` = %i", $this->connection->getInsertId(),
                "WHERE `{$this->left}` >= %i", $LRP[$this->left],
                "AND `{$this->right}` <= %i", $rightEnd,
                "AND `{$this->parent}` = %i", $LRP[$this->parent]);
            }

            if($this->table->getEnabledTransaction()) {
                if($affected != 1) {
                    throw new \Sakura\SakuraException('After inserting node a value of affectedRows() is not equal to 1!');
                }

                $this->connection->commit();
            }
                
        } catch(Exception $e) {
            if ($this->table->getEnabledTransaction()) {
                $this->connection->rollback();
            }
            
            throw $e;
        }
    }


    /**
    * Delete one node from DB and update table.
    * @see $this->setId()
    * @return void
    */
    public function deleteNode () {

        try {
            if($this->table->getEnabledTransaction()) {
                $this->connection->begin();
            }

            $table = $this->table->getName();
            $node = $this->connection->fetch("SELECT `{$this->parent}`, `{$this->left}`, `{$this->right}` FROM `$table` WHERE `{$this->id}` = %i",
            $this->getId());

            $this->connection->query("DELETE FROM `" . $this->table->getName() ."` WHERE `{$this->id}` = %i", $this->getId());


            if ($node[$this->left] >= $node[$this->right]) {
                throw new \LogicException("Left ({$this->left}) column must be smaller than right ({$this->right}) column!");
            }


            if($node[$this->left] + 1 == $node[$this->right]) {

            $this->connection->query("UPDATE `$table` SET `{$this->left}` = `{$this->left}`-2 WHERE `{$this->left}` > %i", $node[$this->right]);
            $this->connection->query("UPDATE `$table` SET `{$this->right}` = `{$this->right}`-2 WHERE `{$this->right}` > %i", $node[$this->right]);

        } else {

            $this->connection->query("UPDATE `$table` SET `{$this->parent}` = %i", $node[$this->parent],
                                    " WHERE `{$this->parent}` = %i", $this->getId());
            $this->connection->query("UPDATE `$table` SET `{$this->left}` = `{$this->left}`-1, `{$this->right}` = `{$this->right}`-1 " .
                                    "WHERE `{$this->left}` > %i", $node[$this->left],
                                    " AND `{$this->right}` < %i", $node[$this->right]);
            $this->connection->query("UPDATE `$table` SET `{$this->left}` = `{$this->left}`-2 WHERE `{$this->left}` > %i", $node[$this->right]);
            $this->connection->query("UPDATE `$table` SET `{$this->right}` = `{$this->right}`-2 WHERE `{$this->right}` > %i", $node[$this->right]);

        }

            if($this->table->getEnabledTransaction()) {
                $this->connection->commit();
            }
        } catch(Exception $e) {
            if($this->table->getEnabledTransaction()) {
                $this->connection->rollback();
            }
            
            throw $e;
        }
    }


    /**
    * It move node/branch from one place to other place. You can't take branch and move to its place where is subbranch/subnode! ID parameter in chooseTarget() must not belong root.
    * @see $this->setId()
    * @see $this->selectLeftRight()
    * @see $this->chooseTarget()
    * @throws SakuraNotSupportedException
    * @throws SakuraBadColumnException
    * @throws DibiException
    */
    public function updateBranch () {

        try {
            $table = $this->table->getName();
            $LR = $this->getLeftRight();
            $targetPLR = $this->getWhereTo();
            if (empty($targetPLR[$this->parent])) {
                throw new \Sakura\SakuraNotSupportedException("Target must not be root!");
            }

            if($this->table->getEnabledTransaction()) {
                $this->connection->begin();
            }

            $move = $LR[$this->right] + 1 - $LR[$this->left];
            if($move < 2 or $move % 2 == 1) {
                $msg = '"left"+1 must be smaller than "right" and difference between them must be odd!';
                throw new \Sakura\SakuraBadColumnException($msg, $code = 0, $table, $LR, 'diff');
            }

            $targetPLR[$this->right] = $targetPLR[$this->left] + ($LR[$this->right] - $LR[$this->left]);

            if($LR[$this->left] <= $targetPLR[$this->left] and $targetPLR[$this->right] <= $LR[$this->right]) {
                $msg = 'Values got from $this->getWhereTo() must not be a subnode/branch of values got from $this->getLeftRight()!';
                throw new \Sakura\SakuraBadColumnException($msg, $code = 0, $table, array('current' => $LR, 'target' => $targetPLR), $type = 'subnode');
            }

            $this->connection->query("UPDATE `$table` SET `{$this->right}` = `{$this->right}`+$move WHERE `{$this->right}` >= %i", $targetPLR[$this->left]);
            $this->connection->query("UPDATE `$table` SET `{$this->left}` = `{$this->left}`+$move WHERE `{$this->left}` >= %i", $targetPLR[$this->left]);

            if($LR[$this->left] > $targetPLR[$this->left]) {
                $this->node = $this->node->toArray();
                $this->node[$this->left] = $LR[$this->left] + $move;
                $this->node[$this->right] = $LR[$this->right] + $move;
            }

            $diff = $targetPLR[$this->left] - $this->node[$this->left];

            $this->connection->query("UPDATE `$table` SET `{$this->parent}` = %i", $targetPLR[$this->parent],
                "WHERE `{$this->left}` = %i", $this->node[$this->left],
                "AND `{$this->right}` = %i", $this->node[$this->right]);

            $sql = array(0 => "UPDATE `$table` SET ");
            if($diff >= 0) {
                $sql[0] .= "`{$this->left}` = `{$this->left}`+$diff, ";
                $sql[0] .= "`{$this->right}` = `{$this->right}`+$diff ";
            } else {
                $sql[0] .= "`{$this->left}` = `{$this->left}`$diff, ";
                $sql[0] .= "`{$this->right}` = `{$this->right}`$diff ";
            }

            $sql[0] .= "WHERE `{$this->left}` >= %i";
            $sql[] = $this->node[$this->left];
            $sql[] = " AND `{$this->right}` <= %i";
            $sql[] = $this->node[$this->right];
            $this->connection->query($sql);

            $this->connection->query("UPDATE `" . $this->table->getName() . "` SET `{$this->left}` = `{$this->left}`-%i",
                $move, "WHERE `{$this->left}` > %i", $this->node[$this->right]);
            $this->connection->query("UPDATE `" . $this->table->getName() . "` SET `{$this->right}` = `{$this->right}`-%i",
                $move, "WHERE `{$this->right}` > %i", $this->node[$this->right]);

            if ($this->table->getEnabledTransaction()) {
                $this->connection->commit();
            }
        } catch(Exception $e) {
            if($this->table->getEnabledTransaction()) {
                $this->connection->rollback();
            }
            
            throw $e;
        }

        unset($this->node);
        unset($this->whereTo);
    }




    /** SQL Factories **/


    public function baseSqlFactory ($selecdId = False) {

        return $this->selectNodeSqlFactory();
    }


    /**
    * It creates SQL for selecting whole tree.
    * @param bool Pass True, If id column is needed.
    * @return Fluent
    */
    public function selectTreeSqlFactory ($selectId = False) {

        $alias = $this->table->getAlias();
        return $this->connection->select("`$alias`.`{$this->id}`, `$alias`.`{$this->parent}`")
            ->select("`$alias`.`{$this->left}` AS `left`")
            ->select("`$alias`.`{$this->right}` AS `right`")
            ->from('`' . $this->table->getName() . "` AS `$alias`")->orderBy("`$alias`.`{$this->left}` ASC");
    }


    public function selectBranchSqlFactory ($selectId = False) {

        return $this->selectTreeSqlFactory();
    }


    /**
    * It creates SQL for selecting a path from node to root, eventually selecting the path conversely.
    * @see $this->selectLeftRight() or $this->setLeftRight()
    * @param bool If True is passed, it includes node, which ID was passed before.
    * @param bool If True is passed, it will return in order from root to node.
    * @param bool Pass True, If id column is needed.
    * @return Fluent
    */
    public function selectPathSqlFactory ($includingNode, $fromRoot, $selectId = False) {

        $LR = $this->getLeftRight();
        $alias = $this->table->getAlias();
        $Fluent = $this->connection->select("`$alias`.`{$this->id}`")->from('`' . $this->table->getName() . "` AS `$alias`");

        if ($includingNode) {
            $Fluent->where("`$alias`.`{$this->left}` <= %i", $LR[$this->left])->and("`$alias`.`{$this->right}` >= %i", $LR[$this->right]);
        } else {
            $Fluent->where("`$alias`.`{$this->left}` < %i", $LR[$this->left])->and("`$alias`.`{$this->right}` > %i", $LR[$this->right]);
        }

        if ($fromRoot) {
            return $Fluent->orderBy("`$alias`.`{$this->left}` ASC");
        } else {
            return $Fluent->orderBy("`$alias`.`{$this->left}` DESC");
        }
    }




    /** Getters **/


    /**
    * It returns whole node from $this->node with 'left' and 'right', eventually 'parent' columns. If one of 'left'/'right' is not set, it will be select them ("autoselect").
    * @param bool If True is passed, it requires 'parent' column.
    * @return array|DibiRow
    * @throws SakuraBadColumnException
    */
    public function getLeftRight ($addParent = False) {

        if(!isset($this->node[$this->left]) or !isset($this->node[$this->right])) {
            $this->selectLeftRight($addParent);

            if(!isset($this->node[$this->left]) or !isset($this->node[$this->right])) {
                    throw new \Sakura\SakuraBadColumnException("Both left and right keys must be set!", 0, '', array('left' => $this->left, 'right' => $this->right), 'notfound');
            }
        }

        if ($addParent) {
            $this->getParent();
        }
        
        return $this->node;
    }


    /**
    * It returns $this->whereTo property.
    * @return array
    * @throws LogicException
    * @throws SakuraBadColumnException
    */
    public function getWhereTo () {

        if(!isset($this->whereTo)) {
            throw new \LogicException('There is nothing to return. Call "$this->chooseTarget()" before.');
        }
        
        if(!isset($this->whereTo[$this->parent]) or !isset($this->whereTo[$this->left])) {
            throw new \Sakura\SakuraBadColumnException("Both parent and left keys must be set!", 0, '', array('parent' => $this->parent, 'left' => $this->left), 'notset');
        }

        return $this->whereTo;
    }


    /**
    * It returns tree for presenters with 'depth' column.
    * @return array
    * @throws LogicException
    */
    public function getTreeFromDB () {

        if(!isset($this->treeFromDB)) {
            throw new \LogicException('$this->treeFromDB property was not created yet. Did you forget to call $this->selectTree() or $this->selectBranch()?');
        }

        $this->intervals = array(0 => array(0 => $this->treeFromDB[0]['left'], 1 => $this->treeFromDB[0]['right']));
        $tree = array();
        foreach($this->treeFromDB as $key => $node) {
            foreach($node as $column => $value) {
                $tree[$key][$column] = $value;
            }

            $tree[$key]['depth'] = $this->calculateDepth($node['left'], $node['right']);
        }

        return $tree;
    }


    /**
    * It calculates actual depth for given left and right values.
    * @param int
    * @param int
    * @return array
    */
    protected function calculateDepth ($left, $right) {

        $depth = 0;
        foreach($this->intervals as $key => $interval) {
            if($left > $interval[0]) {
                if ($right > $interval[1]) {
                    break;
                } else {
                    $depth++;
                }
            } elseif ($interval[0] = $left and $interval[1] = $right) {
                $depth = $key;
                break;
            } else {
                break;
            }
        }

        $this->intervals[$depth] = array(0 => $left, 1 => $right);
        return $depth;
    }

}
