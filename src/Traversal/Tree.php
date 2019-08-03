<?php

declare(strict_types=1);

namespace Sakura\Traversal;

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
        $newLeft = $previousNode->getRight() + 1;
        $data[$this->table->getLeftColumn()] = $newLeft;
        $data[$this->table->getRightColumn()] = $newLeft + 1;
        $data[$this->table->getParentColumn()] = $previousNode->getParent();

        $this->repository->beginTransaction();
        $this->repository->updateByLeftRight($newLeft, \null, $newLeft, \null, 2, 2);
        $newId = $this->repository->addData($data);
        $this->repository->commitTransaction();
        
        return $newId;
    }

    public function createNodeAsFirstChild(array $data, INode $parentNode): int
    {
        $newLeft = $parentNode->getLeft() + 1;
        $data[$this->table->getLeftColumn()] = $newLeft;
        $data[$this->table->getRightColumn()] = $newLeft + 1;
        $data[$this->table->getParentColumn()] = $parentNode->getId();
        
        $this->repository->beginTransaction();
        $this->repository->updateByLeftRight($newLeft, \null, $newLeft, \null, 2, 2);
        $newId = $this->repository->addData($data);
        $this->repository->commitTransaction();
        
        return $newId;
    }

    public function getBranch(INode $node): NodeList
    {
        return $this->repository->getBranch($node->getLeft(), $node->getRight());
    }

    public function getDepth(INode $node): int
    {
        $nodeList = $this->repository->getPath($node);
        return \count($nodeList);
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
        return $this->repository->getPath($node);
    }

    /**
     * @throws Exceptions\NoRootException
     */
    public function getRoot(): INode
    {
        $root = $this->repository->getNodeByLeft(1);

        if (\is_null($root)) {
            throw new Exceptions\NoRootException('No root found in ' . $this->table->getName() . ' table.');
        }

        if (!\is_null($root->getParent())) {
            throw new Exceptions\NoRootException('There is broken root or whole tree in ' . $this->table->getName() . ' table.');
        }

        return $root;
    }

    public function moveBranchAfter(INode $branch, INode $goal): void
    {
        $this->checkBranchWithGoal($branch, $goal);
        $leftRightDiff = self::getRightLeftDifference($branch->getLeft(), $branch->getRight());

        if ($branch->getLeft() < $goal->getLeft() && $branch->getRight() < $goal->getRight()) {
            $this->repository->beginTransaction();
            $newLeft = $goal->getRight() + 1;
            $move = self::getMovement($goal->getLeft(), $branch->getLeft());
            $this->repository->updateByLeftRight(
                $newLeft,
                \null,
                $newLeft,
                \null,
                $leftRightDiff,
                $leftRightDiff);
            $this->repository->updateByLeftRight(
                $branch->getLeft(),
                $branch->getRight(),
                $branch->getLeft(),
                $branch->getRight(),
                $move,
                $move);
            $this->repository->updateByLeftRight(
                $branch->getLeft(),
                \null,
                $branch->getRight(),
                \null,
                $leftRightDiff * (-1),
                $leftRightDiff * (-1));
            $this->repository->commitTransaction();
            return;
        }

        if ($branch->getLeft() > $goal->getLeft() && $branch->getRight() > $goal->getRight()) {
            $this->repository->beginTransaction();
            $newLeft = $goal->getRight() + 1;
            $this->repository->updateByLeftRight(
                $newLeft,
                \null,
                $newLeft,
                \null,
                $leftRightDiff,
                $leftRightDiff);

            $movedLeft = $branch->getLeft() + $leftRightDiff;
            $movedRight = $branch->getRight() + $leftRightDiff;
            $move = self::getMovement($movedLeft, $goal->getLeft()) * (-1);
            $this->repository->updateByLeftRight(
                $movedLeft,
                $movedRight,
                $movedLeft,
                $movedRight,
                $move,
                $move);
            $this->repository->updateByLeftRight(
                $movedLeft,
                \null,
                $movedRight,
                \null,
                $leftRightDiff * (-1),
                $leftRightDiff * (-1));
            $this->repository->commitTransaction();
            return;
        }

        if ($branch->getLeft() < $goal->getLeft() && $branch->getRight() > $goal->getRight()) {
            $this->repository->beginTransaction();

            $this->repository->commitTransaction();
            return;
        }

        throw new \Sakura\Exceptions\RuntimeException;
    }

    public function moveBranchAsFirstChild(INode $branch, INode $goal): void
    {
    }

    private function checkBranchWithGoal(INode $branch, INode $goal): void
    {
        $branchLeft = $branch->getLeft();
        $branchRight = $branch->getRight();
        $goalLeft = $goal->getLeft();
        $goalRight = $goal->getRight();

        if (
            ($branchLeft < $goalLeft && $branchRight > $goalRight)
            || ($branchLeft === $goalLeft)) {
            throw new \InvalidArgumentException("Goal destination can not be in same branch!");
        }

        if (
            ($branchLeft > $goalLeft && $branchRight < $goalLeft)
            || ($branchLeft < $goalLeft && $branchRight > $goalLeft)
            || ($branchLeft > $goalRight && $branchRight < $goalRight)
            || ($branchLeft < $goalRight && $branchRight > $goalRight)) {
            throw new \Sakura\Exceptions\RuntimeException("Branch and/or goal nodes seem not valid.");
        }
    }

    /**
     * @throws Exceptions\BadArgumentException
     */
    public function removeNode(INode $node): void
    {
        if ($node->getLeft() === 1 || \is_null($node->getParent()))
        {
            throw new Exceptions\BadArgumentException("Root node cannot be removed!");
        }
    }

    public static function getRightLeftDifference(int $left, int $right): int
    {
        return $right - $left + 1;
    }

    public static function getMovement(int $higher, int $lower): int
    {
        return $higher - $lower + 1;
    }

}
