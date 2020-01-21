<?php

declare(strict_types=1);

namespace Sakura\Traversal;

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

    public function createNodeAfter(array $data, INode $previousNode): int
    {
        if (self::isRoot($previousNode)) {
            throw new Exceptions\BadArgumentException('Cannot create node after root in ' . $this->table->getName() . ' table!');
        }
        
        $newLeft = $previousNode->getRight() + 1;
        $newRight = $newLeft + 1;
        $data[$this->table->getLeftColumn()] = $newLeft;
        $data[$this->table->getRightColumn()] = $newRight;
        $data[$this->table->getParentColumn()] = $previousNode->getParent();

        $this->repository->beginTransaction();
        $this->repository->updateByRight($newLeft, \null, 2);
        $this->repository->updateByLeft($newLeft, \null, 2);
        $newId = $this->repository->addData($data);
        $this->repository->commitTransaction();
        
        return $newId;
    }

    public function createNodeAsFirstChild(array $data, INode $parentNode): int
    {
        $newLeft = $parentNode->getLeft() + 1;
        $newRight = $newLeft + 1;
        $data[$this->table->getLeftColumn()] = $newLeft;
        $data[$this->table->getRightColumn()] = $newRight;
        $data[$this->table->getParentColumn()] = $parentNode->getId();
        
        $this->repository->beginTransaction();
        $this->repository->updateByRight($newLeft, \null, 2);
        $this->repository->updateByLeft($newLeft, \null, 2);
        $newId = $this->repository->addData($data);
        $this->repository->commitTransaction();
        
        return $newId;
    }

    public function getBranch(INode $node): NodeList
    {
        return $this->repository->getBranch($node);
    }

    public function getNodeListByParent(int $parent): NodeList
    {
        return $this->repository->getNodeListByParent($parent);
    }

    public function getDepth(INode $node): int
    {
        $level = $this->repository->getLevel($node);
        return $level - 1;
    }

    public function getNode(int $id): ?INode
    {
        return $this->repository->getNodeById($id);
    }

    public function getNumberOfChilds(int $id): int
    {
        return $this->repository->getNumberOfChilds($id);
    }

    public function getParent(int $id): INode
    {
        return $this->repository->getNodeById($id);
    }

    public function getPath(INode $node, bool $isAscending = true): NodeList
    {
        return $this->repository->getPath($node, $isAscending);
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

    /**
     * @throws Exceptions\BadArgumentException
     * @throws Exceptions\RuntimeException
     */
    public function moveBranchAfter(INode $branch, INode $goal): void
    {
        if (self::isRoot($branch) || self::isRoot($goal))
        {
            throw new Exceptions\BadArgumentException("The whole tree cannot be moved or choosen node cannot be moved after root!");
        }

        $this->checkBranchWithGoal($branch, $goal);
        $leftRightDiff = self::getRightLeftDifference($branch->getLeft(), $branch->getRight());

        if ($branch->getLeft() < $goal->getLeft() && $branch->getRight() < $goal->getRight()) {
            $this->repository->beginTransaction();
            $newLeft = $goal->getRight() + 1;
            $this->repository->updateByRight($newLeft, \null, $leftRightDiff);
            $this->repository->updateByLeft($newLeft, \null, $leftRightDiff);

            $move = $newLeft - $branch->getLeft();
            $this->repository->updateByLeft($branch->getLeft(), $branch->getRight(), $move);
            $this->repository->updateByRight($branch->getLeft(), $branch->getRight(), $move);
            $this->repository->updateByLeft($branch->getLeft(), \null, $leftRightDiff * (-1));
            $this->repository->updateByRight($branch->getRight(), \null, $leftRightDiff * (-1));
            $this->repository->updateById($branch->getId(), $goal->getParent());
            $this->repository->commitTransaction();
            return;
        }

        if ($goal->getLeft() < $branch->getLeft() && $goal->getRight() < $branch->getRight()) {
            $this->repository->beginTransaction();
            $newLeft = $goal->getRight() + 1;
            $this->repository->updateByLeft($newLeft, \null, $leftRightDiff);
            $this->repository->updateByRight($newLeft, \null, $leftRightDiff);

            $movedLeft = $branch->getLeft() + $leftRightDiff;
            $movedRight = $branch->getRight() + $leftRightDiff;
            $move = ($movedLeft - $newLeft) * (-1);
            $this->repository->updateByLeft($movedLeft, $movedRight, $move);
            $this->repository->updateByRight($movedLeft, $movedRight, $move);
            $this->repository->updateByLeft(
                $movedLeft,
                \null,
                $leftRightDiff * (-1));
            $this->repository->updateByRight(
                $movedLeft,
                \null,
                $leftRightDiff * (-1));
            $this->repository->updateById($branch->getId(), $goal->getParent());
            $this->repository->commitTransaction();
            return;
        }
        
        if ($goal->getLeft() < $branch->getLeft() && $branch->getRight() < $goal->getRight()) {
            $this->repository->beginTransaction();
            $newLeft = $goal->getRight() + 1;
            $this->repository->updateByLeft($newLeft, \null, $leftRightDiff);
            $this->repository->updateByRight($newLeft, \null, $leftRightDiff);

            $move = self::getMovement($newLeft, $branch->getLeft());
            $move = $newLeft - $branch->getLeft();
            $this->repository->updateByLeft($branch->getLeft(), $branch->getRight(), $move);
            $this->repository->updateByRight($branch->getLeft(), $branch->getRight(), $move);            
            $this->repository->updateByLeft(
                $branch->getRight() + 1,
                \null,
                $leftRightDiff * (-1));
            $this->repository->updateByRight(
                $branch->getRight() + 1,
                \null,
                $leftRightDiff * (-1));
            $this->repository->updateById($branch->getId(), $goal->getParent());
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
        if (self::isRoot($branch))
        {
            throw new Exceptions\BadArgumentException("The whole tree cannot be moved!");
        }

        $this->checkBranchWithGoal($branch, $goal);
        $leftRightDiff = self::getRightLeftDifference($branch->getLeft(), $branch->getRight());

        if ($branch->getLeft() < $goal->getLeft() && $branch->getRight() < $goal->getRight()) {
            $this->repository->beginTransaction();
            $newLeft = $goal->getRight() + 1;
            $move = self::getMovement($goal->getLeft(), $branch->getLeft());
            $this->repository->updateByRight($newLeft, \null, $leftRightDiff);
            $this->repository->updateByLeft($newLeft, \null, $leftRightDiff);
            $this->repository->updateByLeft($branch->getLeft(), $branch->getRight(), $move);
            $this->repository->updateByRight($branch->getLeft(), $branch->getRight(), $move);
            $this->repository->updateByLeft($branch->getLeft(), \null, $leftRightDiff * (-1));
            $this->repository->updateByRight($branch->getRight(), \null, $leftRightDiff * (-1));
            $this->repository->updateById($branch->getId(), $goal->getId());
            $this->repository->commitTransaction();
            return;
        }

        if ($goal->getLeft() < $branch->getLeft() && $goal->getRight() < $branch->getRight()) {
            $this->repository->beginTransaction();
            $newLeft = $goal->getLeft() + 1;
            $this->repository->updateByLeft($newLeft, \null, $leftRightDiff);
            $this->repository->updateByRight($newLeft, \null, $leftRightDiff);

            $movedLeft = $branch->getLeft() + $leftRightDiff;
            $movedRight = $branch->getRight() + $leftRightDiff;
            $move = ($movedLeft - $newLeft) * (-1);
            $this->repository->updateByLeft($movedLeft, $movedRight, $move);
            $this->repository->updateByRight($movedLeft, $movedRight, $move);
            $this->repository->updateByLeft(
                $movedLeft,
                \null,
                $leftRightDiff * (-1));
            $this->repository->updateByRight(
                $movedLeft,
                \null,
                $leftRightDiff * (-1));
            $this->repository->updateById($branch->getId(), $goal->getId());
            $this->repository->commitTransaction();
            return;
        }

        if ($branch->getLeft() < $goal->getLeft() && $branch->getRight() > $goal->getRight()) {
            $this->repository->beginTransaction();

            $this->repository->commitTransaction();
            return;
        }
        
        if ($goal->getLeft() < $branch->getLeft() && $branch->getRight() < $goal->getRight()) {
            $this->repository->beginTransaction();
            $newLeft = $goal->getLeft() + 1;
            $this->repository->updateByLeft($newLeft, \null, $leftRightDiff);
            $this->repository->updateByRight($newLeft, \null, $leftRightDiff);

            $movedLeft = $branch->getLeft() + $leftRightDiff;
            $movedRight = $branch->getRight() + $leftRightDiff;
            $move = ($movedLeft - $newLeft) * (-1);
            $this->repository->updateByLeft($movedLeft, $movedRight, $move);
            $this->repository->updateByRight($movedLeft, $movedRight, $move);
            $this->repository->updateByLeft(
                $movedLeft,
                \null,
                $leftRightDiff * (-1));
            $this->repository->updateByRight(
                $movedLeft,
                \null,
                $leftRightDiff * (-1));
            $this->repository->updateById($branch->getId(), $goal->getId());
            $this->repository->commitTransaction();
            return;
        }

        throw new \Sakura\Exceptions\RuntimeException;
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
            throw new Exceptions\BadArgumentException("Goal destination can not be in same branch!");
        }

        if (
            ($branchLeft > $goalLeft && $branchRight < $goalLeft)
            || ($branchLeft < $goalLeft && $branchRight > $goalLeft)
            || ($branchLeft > $goalRight && $branchRight < $goalRight)
            || ($branchLeft < $goalRight && $branchRight > $goalRight)) {
            throw new Exceptions\RuntimeException("Branch and/or goal nodes seem not valid.");
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
        
        $this->repository->beginTransaction();
        $this->repository->delete($node->getId());
        $this->repository->updateByParent($node->getId(), $node->getParent());
        $this->repository->updateByLeft($node->getLeft(), $node->getRight(), -1);
        $this->repository->updateByRight($node->getLeft(), $node->getRight(), -1);
        $this->repository->updateByLeft($node->getRight(), \null, -2);
        $this->repository->updateByRight($node->getRight(), \null, -2);
        $this->repository->commitTransaction();
    }

    public static function getRightLeftDifference(int $left, int $right): int
    {
        return $right - $left + 1;
    }

    public static function getMovement(int $higher, int $lower): int
    {
        return $higher - $lower + 1;
    }

    public static function isRoot(INode $node): bool
    {
        return $node->getLeft() === 1 || \is_null($node->getParent());
    }

}
