<?php

declare(strict_types=1);

namespace Sakura\Order;

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

        $newOrder = $previous->getOrder() + 1;
        $data[$this->table->getDepthColumn()] = $previous->getDepth();
        $data[$this->table->getParentColumn()] = $previous->getParent();
        $data[$this->table->getOrderColumn()] = $newOrder;

        $this->repository->beginTransaction();
        $this->repository->updateByOrder($newOrder, \null, 1, 0);
        $newId = $this->repository->addData($data);
        $this->repository->commitTransaction();

        return $newId;
    }

    public function createNodeAsFirstChild(array $data, INode $parentNode): int
    {
        $newOrder = $parentNode->getOrder() + 1;
        $data[$this->table->getDepthColumn()] = $parentNode->getDepth() + 1;
        $data[$this->table->getParentColumn()] = $parentNode->getId();
        $data[$this->table->getOrderColumn()] = $newOrder;

        $this->repository->beginTransaction();
        $this->repository->updateByOrder($newOrder, \null, 1, 0);
        
        $newId = $this->repository->addData($data);
        $this->repository->commitTransaction();
        return $newId;
    }

    /**
     * @throws Exceptions\BadArgumentException
     */
    public function getBranch(INode $node, ?int $maxDepth = \null): NodeList
    {
        if (!\is_null($maxDepth) && $node->getDepth() >= $maxDepth) {
            throw new Exceptions\BadArgumentException("Node's depth must be lower than maxDepth!");
        }
        
        $endOrder = $this->repository->getEndOrder($node->getOrder(), $node->getDepth());
        $nodeList = $this->repository->getBranch($node->getOrder(), $endOrder, $maxDepth);
        
        return $nodeList;
    }

    public function getDepth(int $nodeId): int
    {
        return $this->repository->getDepthById($nodeId);
    }

    public function getNode(int $id): ?INode
    {
        return $this->repository->getNodeById($id);
    }

    public function getNumberOfChilds(int $nodeId): int
    {
        return $this->repository->getNumberOfChilds($nodeId);
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
        if (self::isRoot($branch) || self::isRoot($goal))
        {
            throw new Exceptions\BadArgumentException("The whole tree cannot be moved and branch cannot be moved after root node!");
        }

        $branchStart = $branch->getOrder();
        $branchEnd = $this->repository->getEndOrder($branchStart, $branch->getDepth());
        $goalStart = $goal->getOrder();
        $goalEnd = $this->repository->getEndOrder($goalStart, $goal->getDepth());
        self::checkCrossing($branchStart, $branchEnd, $goalStart, $goalEnd);

        $this->repository->beginTransaction();

        if ($branchEnd < $goalStart) {
            $this->repository->updateParentByIdList([$branch->getId()], $goal->getParent());
            $branchCount = self::getBranchCount($branchStart, $branchEnd);

            $this->repository->updateByOrder(
                $goalEnd + 1,
                \null,
                $branchCount,
                0);

            $this->repository->updateByOrder(
                $branchStart,
                $branchEnd,
                self::getOrderMovement($goalEnd, $branchStart, 1),
                self::getDepthMovement($branch->getDepth(), $goal->getDepth(), false));

            $this->repository->updateByOrder(
                $branchEnd + 1,
                \null,
                $branchCount * (-1),
                0);
        }

        if ($goalStart < $branchStart && $branchEnd <= $goalEnd) {
            $this->repository->updateParentByIdList([$branch->getId()], $goal->getParent());

            if ($branchEnd === $goalEnd) {
                $this->repository->updateByOrder(
                    $branchStart,
                    $branchEnd,
                    0,
                    self::getDepthMovement($branch->getDepth(), $goal->getDepth(), false));
            } else {
                $branchCount = self::getBranchCount($branchStart, $branchEnd);
                $this->repository->updateByOrder($goalEnd + 1, \null, $branchCount, 0);

                $this->repository->updateByOrder(
                    $branchStart,
                    $branchEnd,
                    self::getOrderMovement($goalEnd, $branchStart, $branchCount),
                    self::getDepthMovement($branch->getDepth(), $goal->getDepth(), false));

                $this->repository->updateByOrder(
                    $branchEnd + 1,
                    \null,
                    $branchCount * (-1),
                    0);
            }
        }

        $this->repository->commitTransaction();
    }

    /**
     * @throws Exceptions\BadArgumentException
     */
    public function moveBranchAsFirstChild(INode $branch, INode $goal): void
    {
        if (self::isRoot($branch))
        {
            throw new Exceptions\BadArgumentException("The whole tree cannot be moved!");
        }

        $branchStart = $branch->getOrder();
        $branchEnd = $this->repository->getEndOrder($branchStart, $branch->getDepth());
        $goalStart = $goal->getOrder();
        $goalEnd = $this->repository->getEndOrder($goalStart, $goal->getDepth());
        self::checkCrossing($branchStart, $branchEnd, $goalStart, $goalEnd);

        $this->repository->beginTransaction();

        if ($branchStart < $goalStart && $branchEnd < $goalStart) {
            $branchCount = self::getBranchCount($branchStart, $branchEnd);
            $this->repository->updateParentByIdList([$branch->getId()], $goal->getId());
            $this->repository->updateByOrder(
                $goalStart,
                \null,
                $branchCount,
                0);

            $this->repository->updateByOrder(
                $branchStart,
                $branchEnd,
                self::getOrderMovement($goalStart, $branchStart, $branchCount),
                self::getDepthMovement($branch->getDepth(), $goal->getDepth(), true));

            $this->repository->updateByOrder(
                $branchStart,
                \null,
                $branchCount * (-1),
                0);
        }

        if ($branchStart > $goalStart) {
            $branchCount = self::getBranchCount($branchStart, $branchEnd);
            $orderFrom = $goalStart + 1;
            $this->repository->updateParentByIdList([$branch->getId()], $goal->getId());
            $this->repository->updateByOrder($orderFrom, \null, $branchCount, 0);

            $this->repository->updateByOrder(
                $branchStart + $branchCount,
                $branchEnd + $branchCount,
                self::getOrderMovement($branchStart, $orderFrom, $branchCount) * (-1),
                self::getDepthMovement($branch->getDepth(), $goal->getDepth(), true));

            $this->repository->updateByOrder(
                $branchStart + $branchCount,
                \null,
                $branchCount * (-1),
                0);
        }

        $this->repository->commitTransaction();
    }

    private static function checkCrossing(int $branchStart, int $branchEnd, int $goalStart, int $goalEnd): void
    {
        if (
            ($branchStart === $goalStart)
            || ($branchStart <= $goalStart && $goalStart <= $branchEnd)
            || ($goalStart <= $branchStart && $branchStart <= $goalEnd && $goalEnd < $branchEnd)
        ) {
            throw new Exceptions\BadArgumentException("Goal destination cannot be in same branch or both must not cross together!");
        }
    }

    /**
     * @throws Exceptions\BadArgumentException
     */
    public function removeNode(INode $node): void
    {
        if (self::isRoot($node))
        {
            throw new Exceptions\BadArgumentException("Root node cannot be removed!");
        }

        $idList = [];
        $this->repository->beginTransaction();
        $endOrder = $this->repository->getEndOrder($node->getOrder(), $node->getDepth());
        $nodeList = $this->repository->getNodesByParent($node->getId());

        foreach ($nodeList as $n) {
            /* @var $n INode */
            $idList[] = $n->getId();
        }

        $affected = $this->repository->delete($node->getId());
        $this->repository->updateParentByIdList($idList, $node->getParent());
        $this->repository->updateByOrder($node->getOrder(), $endOrder, -1, -1);
        $this->repository->updateByOrder($endOrder + 1, \null, -1, 0);

        if ($affected === 1) {
            $this->repository->commitTransaction();
        } else {
            $this->repository->rollbackTransaction();
        }
    }

    public static function getBranchCount(int $start, int $end): int
    {
        return $end - $start + 1;
    }

    public static function getOrderMovement(int $higher, int $lower, int $branchCount): int
    {
        return $higher - $lower + $branchCount;
    }

    public static function getDepthMovement(int $current, int $new, bool $asChild = false): int
    {
        return $new - $current + (int) $asChild;
    }

    public static function isRoot(INode $node): bool
    {
        return $node->getOrder() === 1 || \is_null($node->getParent()) || $node->getDepth() === 0;
    }

}
