<?php

declare(strict_types=1);

namespace Sakura\Recursive;

final class Tree
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
        return $this->repository->addData($data);
    }

    public function createNodeAsFirstChild(array $data, INode $parentNode): int
    {
        $data[$this->table->getParentColumn()] = $parentNode->getId();
        return $this->repository->addData($data);
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
        $nodeList = $this->repository->getNodesByParent($node->getId());
        
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

    public function getParent(int $id): ?int
    {
        return $this->repository->getParentById($id);
    }

    /**
     * @throws Exceptions\NoExpectedNodeException
     */
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
        $parent = $node->getParent();

        if (\is_int($parent))
        {
            $parentNode = $this->repository->getNodeById($parent);
            if (\is_null($parentNode)) {
                throw new Exceptions\NoExpectedNodeException('There is broken node or whole tree in ' . $this->table->getName() . ' table.');
            }

            $this->appendIntoList($parentNode, $list);
        }
    }

    /**
     * @throws Exceptions\NoRootException
     */
    public function getRoot(): INode
    {
        $this->repository->getRoot();
    }

    public function moveBranchAfter(INode $branchNode, INode $previousNode): void
    {
        $this->repository->updateNode($previousNode->getParent(), $branchNode->getId());
    }

    public function moveBranchAsFirstChild(INode $branchNode, INode $parent): void
    {
        $this->repository->updateNode($parent->getId(), $branchNode->getId());
    }

    /**
     * @throws Exceptions\BadArgumentException
     */
    public function removeNode(INode $node): void
    {
        if (\is_null($node->getParent()))
        {
            throw new Exceptions\BadArgumentException("Root node cannot be removed!");
        }

        $this->repository->beginTransaction();
        
        $idList = $this->repository->getIdsByParent($node->getId());
        $this->repository->updateNode($node->getParent(), $idList);
        $this->repository->delete($node->getId());
        
        $this->repository->commitTransaction();
    }

}
