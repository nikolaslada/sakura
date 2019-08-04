<?php

declare(strict_types=1);

namespace Sakura\Order;

use Sakura\ITree;
use Sakura\Exceptions;

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
        $this->repository->updateByOrder($previousNode->getOrder(), \null, 1, 0);
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
        $this->repository->updateByOrder($parentNode->getOrder(), \null, 1, 0);
        $newId = $this->repository->addData($data);
        $this->repository->commitTransaction();
        
        return $newId;
    }

    /**
     * @throws Exceptions\BadArgumentException
     */
    public function getBranch(INode $node, ?int $maxDepth): NodeList
    {
        if (!\is_null($maxDepth) && $node->getDepth() >= $maxDepth) {
            throw new Exceptions\BadArgumentException("Node's depth must be lower than maxDepth!");
        }
        
        $endNode = $this->repository->getEndNode($node->getOrder(), $node->getDepth());
        $nodeList = $this->repository->getBranch($node->getOrder(), $endNode->getOrder(), $maxDepth);
        
        return $nodeList;
    }

    public function getDepth(int $nodeId): int
    {
        return $this->repository->getDepthById($nodeId);
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
        return $this->repository->getNodeById($id);
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
        $parentNode = $this->repository->getNodeById($node->getId());
        $this->appendIntoList($parentNode, $list);
    }
    
    /**
     * @throws Exceptions\NotRootException
     */
    public function getRoot(): INode
    {
        $root = $this->repository->getNodeByOrder(1);

        if (\is_null($root)) {
            throw new Exceptions\NoRootException('No root found in ' . $this->table->getName() . ' table.');
        }

        if (!\is_null($root->getParent())) {
            throw new Exceptions\NoRootException('There is broken root or whole tree in ' . $this->table->getName() . ' table.');
        }

        return $root;
    }

    /**
     * @throws Exceptions\BadArgumentException
     */
    public function moveBranchAfter(INode $branch, INode $goal): void
    {
        $endNode = $this->getEndNode($branch, $goal);
        $this->repository->beginTransaction();

        if ($branch->getOrder() < $goal->getOrder() && $endNode->getOrder() < $goal->getOrder()) {
            $this->repository->updateByOrder(
                $goal->getOrder(),
                \null,
                self::getOrderCount($branch->getOrder(), $endNode->getOrder()),
                0);
            $this->repository->updateByOrder(
                $branch->getOrder(),
                $endNode->getOrder(),
                self::getOrderMovement($branch->getOrder(), $goal->getOrder()),
                self::getDepthMovement($branch->getDepth(), $goal->getDepth(), false));
            $this->repository->updateByOrder(
                $branch->getOrder(),
                \null,
                self::getOrderCount($branch->getOrder(), $endNode->getOrder()) * (-1),
                0);
        }

        if ($branch->getOrder() > $goal->getOrder()) {
            $orderBranchCount = self::getOrderCount($branch->getOrder(), $endNode->getOrder());
            $this->repository->updateByOrder($goal->getOrder(), \null, $orderBranchCount, 0);
            $this->repository->updateByOrder(
                $branch->getOrder() + $orderBranchCount,
                $endNode->getOrder() + $orderBranchCount,
                self::getOrderMovement($goal->getOrder(), $branch->getOrder()) * (-1),
                self::getDepthMovement($branch->getDepth(), $goal->getDepth(), false));
            $this->repository->updateByOrder(
                $branch->getOrder(),
                \null,
                self::getOrderMovement($goal->getOrder(), $branch->getOrder()) * (-1),
                0);
        }

        $this->repository->commitTransaction();
    }

    /**
     * @throws Exceptions\BadArgumentException
     */
    public function moveBranchAsFirstChild(INode $branch, INode $goal): void
    {
        $endNode = $this->getEndNode($branch, $goal);
        $this->repository->beginTransaction();

        if ($branch->getOrder() < $goal->getOrder() && $endNode->getOrder() < $goal->getOrder()) {
            $this->repository->updateByOrder(
                $goal->getOrder(),
                \null,
                self::getOrderCount($branch->getOrder(), $endNode->getOrder()),
                0);
            $this->repository->updateByOrder(
                $branch->getOrder(),
                $endNode->getOrder(),
                self::getOrderMovement($branch->getOrder(), $goal->getOrder()),
                self::getDepthMovement($branch->getDepth(), $goal->getDepth(), true));
            $this->repository->updateByOrder(
                $branch->getOrder(),
                \null,
                self::getOrderCount($branch->getOrder(), $endNode->getOrder()) * (-1),
                0);
        }

        if ($branch->getOrder() > $goal->getOrder()) {
            $orderBranchCount = self::getOrderCount($branch->getOrder(), $endNode->getOrder());
            $this->repository->updateByOrder($goal->getOrder(), \null, $orderBranchCount, 0);
            $this->repository->updateByOrder(
                $branch->getOrder() + $orderBranchCount,
                $endNode->getOrder() + $orderBranchCount,
                self::getOrderMovement($goal->getOrder(), $branch->getOrder()) * (-1),
                self::getDepthMovement($branch->getDepth(), $goal->getDepth(), true));
            $this->repository->updateByOrder(
                $branch->getOrder(),
                \null,
                self::getOrderMovement($goal->getOrder(), $branch->getOrder()) * (-1),
                0);
        }

        $this->repository->commitTransaction();
    }

    private function getEndNode(INode $branch, INode $goal): INode
    {
        $endNode = $this->repository->getEndNode($branch->getOrder(), $branch->getDepth());

        if ($branch->getOrder() < $goal->getOrder() && $endNode->getOrder() > $goal->getOrder()) {
            throw new Exceptions\BadArgumentException("Goal destination can not be in same branch!");
        }

        if ($branch->getOrder() === $goal->getOrder()) {
            throw new Exceptions\BadArgumentException("Goal destination can not be in same branch!");
        }

        return $endNode;
    }

    /**
     * @throws Exceptions\BadArgumentException
     */
    public function removeNode(INode $node): void
    {
        if ($node->getOrder() === 1 || \is_null($node->getParent()) || $node->getDepth() === 0)
        {
            throw new Exceptions\BadArgumentException("Root node cannot be removed!");
        }

        $this->repository->beginTransaction();
        $nodeList = $this->repository->getNodesByParent($node->getId());
        $idList = [];

        foreach ($nodeList as $node) {
            /* @var $node INode */
            $idList[] = $node->getId();
        }

        $this->repository->updateByIdList($idList, $node->getParent());
        $this->repository->updateByOrder($node->getOrder(), \null, 1, -1);
        $this->repository->delete($node->getId());
        $this->repository->commitTransaction();
    }

    public static function getOrderCount(int $start, int $end): int
    {
        return $end - $start + 1;
    }

    public static function getOrderMovement(int $lower, int $higher): int
    {
        return $higher - $lower + 1;
    }

    public static function getDepthMovement(int $current, int $new, bool $asChild = false): int
    {
        return $new - $current + (int) $asChild;
    }

}
