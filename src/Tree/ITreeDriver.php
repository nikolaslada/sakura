<?php

/**
* This file is part of the Sakura project.
* Copyright (c) 2015 Nikolas Lada
*/

interface ITreeDriver {


	/** Setters, setting, etc. **/

	/**
	* Value of $id will be passed to $this->idValue.
	* @param int|string Pass ID of node.
	* @return void
	* @throws InvalidArgumentException
	*/
	public function setId ($id);

	/**
	* Value of $id will be passed to $this->rootID.
	* @param int
	* @return void
	* @throws LogicException
	* @throws InvalidArgumentException
	*/
	public function setRoot ($id);

	/**
	* Value of $node will be passed to $this->node.
	* @param array|DibiRow Pass values with appropriate keys.
	* @return void
	* @throws InvalidArgumentException
	*/
	public function setNode ($node);

	/**
	* It sets target ($this->whereTo) for insertNode and updateBranch.
	* @param int If integer > 0 is passed, it choose node with this id.
	* @return void
	* @throws DomainException
	* @throws SakuraRowNotFoundException
	* @throws SakuraNotImplementedException
	*/
	public function chooseTarget ($id);

	/**
	* It sets target ($this->whereTo) for insertNode and updateBranch.
	* @param int Pass integer > 0. It gets some information from parent node.
	* @param int If integer > 0 is passed, it adds information about position.
	* @return void
	* @throws DomainException
	* @throws SakuraRowNotFoundException
	* @throws SakuraNotImplementedException
	*/
	public function chooseTargetAsChild ($id, $position = 0);

	/**
	* It empties tree's properties - $this->treeFromDB and $this->rootID.
	* @return void
	*/
	public function emptyTree ();

	/**
	* It empties the $this->list property. It may be called before $this->getPath() and $this->getLevel().
	* @return void
	*/
	public function emptyList ();

	/** Selects **/

	public function selectNode ($DibiFluent);

	/**
    * It executes SQL selects and loads rows to $this->treeFromDB as whole tree.
	* @see $this->selectTreeSqlFactory()
	* @param DibiFluent Pass NULL or your DibiFluent SQL from selectTreeSqlFactory().
    * @return void
	* @throws SakuraNoRowReturnedException
	* @throws SakuraRowNotFoundException
    */
    public function selectTree ($DibiFluent);


	/**
	* It will select a branch and will save it into $this->treeFromDB property.
	* @see $this->setId()
	* @see $this->selectBranchSqlFactory()
	* @param DibiFluent You can pass your edited DibiFluent instance from $this->selectBranchSqlFactory(). You can skip it.
	* @return void
	* @throws SakuraRowNotFoundException
    * @throws SakuraNoRowReturnedException
	* @throws SakuraNotSupportedException
	*/
	public function selectBranch ($DibiFluent);


    /**
    * It generates set of rows. Each row represents every level in a tree between root and passed ID of node.
	* @see $this->setId()
	* @see $this->selectNodeSqlFactory()
	* @see $this->emptyList()
    * @param bool If False is passed, it returns only superior nodes.
    * @param bool If True is passed, rows will be sorted by depth from root.
    * @param DibiFluent Pass NULL or your DibiFluent SQL from selectNodeSqlFactory().
    * @return void|NULL
	* @throws SakuraRowNotFoundException
	* @throws SakuraNoRowReturnedException
	* @throws SakuraException
    */
    public function selectPath ($includingNode, $fromRoot, $DibiFluent);


	/**
	* It returns depth of node.
	* @see $this->setId()
	* @return int
	*/
	public function selectDepth ();


	/**
	* It returns number of subnodes from selects.
	* @see $this->setId()
	* @param int You can skip this.
	* @return int
	* @throws SakuraNoRowReturnedException
	*/
	public function selectNumOfSubnodes ();


	/** Inserts, Updates and Deletes **/

    /**
    * Insert one node to DB and update table.
    * @see $this->setNode()
	* @see $this->chooseTarget
    * @return void
	* @throws SakuraException
    */
    public function insertNode ();


	/**
	* Delete one node from DB and update table.
	* @see $this->setId()
	* @return void
	*/
	public function deleteNode ();

	/**
	* It move node/branch from one place to other place. You can't take branch and move to its place where is subbranch/subnode!
	* @see $this->setId()
	* @see $this->chooseTarget()
	* @throws SakuraBadColumnException
	* @throws DibiException
	* @throws LogicException
	*/
    public function updateBranch ();


	/** SQL Factories **/

	/**
    * It generates basic DibiFluent SQL for getting whole tree.
    * @return DibiFluent
    */
    public function selectTreeSqlFactory ($selectId);

	public function baseSqlFactory ($selectId);

    /**
    * It generates basic DibiFluent SQL for getting a branch.
    * @return DibiFluent
    */
    public function selectBranchSqlFactory ($selectId);

    /**
    * It generates basic DibiFluent SQL for getting path from root to node.
    * @return DibiFluent
    */
    public function selectPathSqlFactory ($includingNode, $fromRoot, $selectId);

    /**
    * It generates basic DibiFluent SQL for getting one node.
    * @return DibiFluent
    */
    public function selectNodeSqlFactory ($selectId);


	/** Getters **/

	/**
	* It returns ID. It firstly tries $this->idValue and then ID column in $this->node.
	* @return int
	* @throws LogicException
	*/
	public function getId ();


	/**
	* It returns whole node from $this->node and $this->numbered.
	* @param bool If True is passed it checks whether all registered columns are included.
	* @param bool If True is passed it checks whether all columns in $this->node are registered / known.
	* @return array
	* @throws SakuraNotSupportedException
	* @throws SakuraBadColumnException
	*/
	public function getNode ($containsAll = False, $noUnknown = False);


	/**
	* It returns non-hierarchical result - value of the $this->list property.
	* @return array
	* @throws LogicException
	*/
	public function getList ();


	/**
	* It returns result from DB.
	* @return DibiResult
	* @throws LogicException
	*/
	public function getTreeFromDB ();
}