<?php

declare(strict_types=1);

namespace Sakura\Recursive;

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
        $data[$this->table->getParentColumn()] = $previousNode->getParent();
        $this->repository->addData($data);
    }

    public function createNodeAsFirstChild(array $data, INode $parentNode): int
    {
        $data[$this->table->getParentColumn()] = $parentNode->getId();
        $this->repository->addData($data);
    }

    public function getBranch(INode $node, int $maxDepth): Branch
    {
        $branch = $this->factory->createBranch($node);
        
        if ($maxDepth > 0) {
            $this->appendIntoBranch($branch, --$maxDepth);
        }
        
        return $branch;
    }

    private function appendIntoBranch(Branch $branch, int $maxDepth): void
    {
        $node = $branch->getNode();
        $nodeList = $this->repository->getChildsByParent($node->getId());
        
        foreach ($nodeList as $node)
        {
            $subBranch = $this->factory->createBranch($node);
            $branch->append($subBranch);
            
            if ($maxDepth > 0) {
                $this->appendIntoBranch($subBranch, $maxDepth);
            }
        }
    }

    public function getDepth(int $nodeId, $depth = 0): int
    {
        $parent = $this->repository->getParentById($nodeId);
        
        if (\is_null($parent)) {
            return $depth;
        } else {
            return $this->getDepth($parent, ++$depth);
        }
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
        $parentNode = $this->repository->getParentNode($node->getId());
        $this->appendIntoList($parentNode, $list);
    }

    public function getRoot(): INode
    {
        return $this->repository->getNodeByParent(\null);
    }

    public function moveBranchAfter(INode $branchNode, INode $previousNode): void
    {
        $this->repository->updateNode($branchNode->getId(), $previousNode->getParent());
    }

    public function moveBranchAsFirstChild(INode $branchNode, INode $parent): void
    {
        $this->repository->updateNode($branchNode->getId(), $parent->getId());
    }

    public function removeNode(INode $node): void
    {
        $this->repository->beginTransaction();
        
        $nodeList = $this->repository->getChildsByParent($node->getId());
        $this->repository->updateNode($nodeList, $node->getParent());
        $this->repository->delete($node->getId());
        
        $this->repository->commitTransaction();
    }

}
