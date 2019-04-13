<?php

declare(strict_types=1);

namespace Sakura\Order;

use Sakura\ITree;

final class Tree implements ITree
{

    /** @var Table */
    private $table;

    /** @var IRepository */
    private $repository;

    /** @var Factory */
    private $factory;


    public function __construct(Table $table, IRepository $repository, Factory $factory)
    {
        $this->table = $table;
        $this->repository = $repository;
        $this->factory = $factory;
    }

    public function createNodeAfter(array $data, INode $previousNode): int
    {
        $data[$this->table->getDepthColumn()] = $previousNode->getDepth();
        $data[$this->table->getParentColumn()] = $previousNode->getParent();
        $data[$this->table->getOrderColumn()] = $previousNode->getOrder() + 1;
        
        $this->repository->beginTransaction();
        $this->repository->updateTree($previousNode->getOrder(), 1);
        $newId = $this->repository->addData($data);
        $this->repository->commitTransaction();
        
        return $newId;
    }

    public function createNodeAsFirstChild(array $data, INode $parentNode): int
    {
        $data[$this->table->getDepthColumn()] = $parentNode->getDepth() + 1;
        $data[$this->table->getParentColumn()] = $parentNode->getId();
        $data[$this->table->getOrderColumn()] = $parentNode->getOrder() + 1;
        
        $this->repository->beginTransaction();
        $this->repository->updateOrderInTree($parentNode->getOrder(), 1);
        $newId = $this->repository->addData($data);
        $this->repository->commitTransaction();
        
        return $newId;
    }

    public function getBranch(INode $node, int $maxDepth): Branch
    {
        $branch = $this->factory->createBranch($node);
        
        if ($node->getDepth() >= $maxDepth) {
            throw new \InvalidArgumentException("Node's depth must be lower than maxDepth!");
        }
        
        $endNode = $this->repository->getEndNode($order, $node->getDepth(), $maxDepth);
        $nodeList = $this->repository->getBranch($fromOrder, $endNode->getOrder());
        
        return $branch;
    }

    public function getDepth(int $nodeId): int
    {
        return $this->repository->getOrderById($nodeId);
    }

    public function getNode(int $id): INode
    {
        return $this->repository->getNodeById($id);
    }

    public function getNumberOfChilds(int $nodeId): int
    {
        return $this->repository->getNumberOfChilds($nodeId);
    }

    public function getParent(int $id): INode
    {
        return $this->repository->getParentNode($id);
    }

    public function getPath(INode $node): NodeList
    {
        $list = [];
        $this->appendIntoList($node, $list);
        \krsort($list);
        return $this->factory->createNodeList($list);
    }
    
    private function appendIntoList(INode $node, array $list): void
    {
        $list[$node->getId()] = $node;
        $parentNode = $this->repository->getNodeByParent($node->getId());
        $this->appendIntoList($parentNode, $list);
    }
    
    public function getRoot(): INode
    {
        return $this->repository->getNodeByOrder(1);
    }

    public function moveBranchAfter(INode $branchNode, INode $previousNode): void
    {
    }

    public function moveBranchAsFirstChild(INode $branchNode, INode $parent): void
    {
    }

    public function removeNode(INode $currentNode): void
    {
        $this->repository->beginTransaction();
        
        $nodeList = $this->repository->getChildsByParent($currentNode->getId());
        $this->repository->updateNode($currentNode->getParent(), $nodeList);
        $this->repository->updateOrderInTree($currentNode->getOrder(), 1);
        $this->repository->delete($currentNode->getId());
        
        $this->repository->commitTransaction();
    }

}
