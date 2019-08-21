<?php

declare(strict_types=1);

namespace Sakura\Recursive;

use Sakura\Exceptions;

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

    /**
     * @throws Exceptions\BadArgumentException
     */
    public function createNodeAfter(array $data, INode $previous): int
    {
        if (self::isRoot($previous)) {
            throw new Exceptions\BadArgumentException('Cannot create node after root in ' . $this->table->getName() . ' table!');
        }

        $data[$this->table->getParentColumn()] = $previous->getParent();
        return $this->repository->addData($data);
    }

    public function createNodeAsFirstChild(array $data, INode $parentNode): int
    {
        $data[$this->table->getParentColumn()] = $parentNode->getId();
        return $this->repository->addData($data);
    }

    public function getBranch(INode $node, ?int $maxDepth = \null): Branch
    {
        if (\is_null($maxDepth)) {
            $maxDepth = PHP_INT_MAX;
        }

        if ($maxDepth > 0) {
            $branch = $this->factory->createBranch($node);
            $this->appendIntoBranch($branch, --$maxDepth);
        }

        return $branch;
    }

    private function appendIntoBranch(Branch $branch, int $maxDepth): void
    {
        $node = $branch->getRootNode();
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

    public function getNode(int $id): ?INode
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
    public function getPath(INode $node, bool $isAscending = true): NodeList
    {
        $list = $this->appendIntoList($node, []);
        
        if ($isAscending) {
            $reversed = \array_reverse($list, true);
            return $this->factory->createNodeList($reversed);
        } else {
            return $this->factory->createNodeList($list);
        }
    }
    
    private function appendIntoList(INode $node, array $list): array
    {
        $list[$node->getId()] = $node;
        $parent = $node->getParent();

        if (\is_int($parent))
        {
            $parentNode = $this->repository->getNodeById($parent);
            if (\is_null($parentNode)) {
                throw new Exceptions\NoExpectedNodeException('There is broken node or whole tree in ' . $this->table->getName() . ' table.');
            }

            return $this->appendIntoList($parentNode, $list);
        }

        return $list;
    }

    /**
     * @throws Exceptions\NoRootException
     */
    public function getRoot(): INode
    {
        return $this->repository->getRoot();
    }

    /**
     * @throws Exceptions\BadArgumentException
     */
    public function moveBranchAfter(INode $branch, INode $goal): void
    {
        if (self::isRoot($branch) || self::isRoot($goal))
        {
            throw new Exceptions\BadArgumentException("The whole tree cannot be moved and branch cannot be moved after root node!");
        }

        self::checkCrossing($branch, $goal);
        $this->repository->updateParentByIdList([$branch->getId()], $goal->getParent());
    }

    /**
     * @throws Exceptions\BadArgumentException
     */
    public function moveBranchAsChild(INode $branch, INode $goal): void
    {
        if (self::isRoot($branch))
        {
            throw new Exceptions\BadArgumentException("The whole tree cannot be moved!");
        }

        self::checkCrossing($branch, $goal);
        $this->repository->updateParentByIdList([$branch->getId()], $goal->getId());
    }

    private static function checkCrossing(INode $branch, INode $goal): void
    {
        $branchBranch = $this->getBranch($branch);

        if ($branchBranch->existsNode($goal->getId())) {
            throw new Exceptions\BadArgumentException("Goal destination cannot be in same branch!");
        }
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
        $this->repository->updateParentByIdList($idList, $node->getParent());
        $this->repository->delete($node->getId());
        
        $this->repository->commitTransaction();
    }

    public static function isRoot(INode $node): bool
    {
        return \is_null($node->getParent());
    }

}
