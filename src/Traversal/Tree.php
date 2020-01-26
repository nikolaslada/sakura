<?php
/**
 * This file is a part of the Sakura project <https://linuxclan.com/sakura-php>.
 * Copyright (c) 2015 - 2020 Nikolas Lada <https://nikolaslada.cz>.
 */

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

    public function getDepth(INode $node): ?int
    {
        $nodeList = $this->repository->getPath($node, true);
        $level = $nodeList->count();
        
        if ($level) {
            return $level - 1;
        } else {
            return \null;
        }
    }

    public function getNode(int $id): ?INode
    {
        return $this->repository->getNodeById($id);
    }

    public function getNumberOfChilds(int $id): int
    {
        return $this->repository->getNumberOfChilds($id);
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
    public function moveBranchAfter(INode $current, INode $goal): void
    {
        if (self::isRoot($current) || self::isRoot($goal))
        {
            throw new Exceptions\BadArgumentException("The whole tree cannot be moved or choosen node cannot be moved after root!");
        }

        $this->checkCurrenthWithGoal($current, $goal);
        $leftRightDiff = self::getRightLeftDifference($current->getLeft(), $current->getRight());
        $diffBack = $leftRightDiff * (-1);

        /**
         * Current before goal
         */
        if ($current->getRight() < $goal->getLeft()) {
            $this->repository->beginTransaction();
            
            $newLeft = $goal->getRight() + 1;
            $this->repository->updateByRight($newLeft, \null, $leftRightDiff);
            $this->repository->updateByLeft($newLeft, \null, $leftRightDiff);

            $move = $newLeft - $current->getLeft();
            $this->repository->updateByLeft($current->getLeft(), $current->getRight(), $move);
            $this->repository->updateByRight($current->getLeft(), $current->getRight(), $move);
            
            $this->repository->updateByLeft($current->getLeft(), \null, $diffBack);
            $this->repository->updateByRight($current->getRight(), \null, $diffBack);
            
            $this->repository->updateById($current->getId(), $goal->getParent());
            $this->repository->commitTransaction();
        }

        /**
         * Goal before current
         */
        elseif ($goal->getRight() < $current->getLeft()) {
            $this->repository->beginTransaction();
            
            $newLeft = $goal->getRight() + 1;
            $this->repository->updateByLeft($newLeft, \null, $leftRightDiff);
            $this->repository->updateByRight($newLeft, \null, $leftRightDiff);

            $movedLeft = $current->getLeft() + $leftRightDiff;
            $movedRight = $current->getRight() + $leftRightDiff;
            $move = ($movedLeft - $newLeft) * (-1);
            $this->repository->updateByLeft($movedLeft, $movedRight, $move);
            $this->repository->updateByRight($movedLeft, $movedRight, $move);
            
            $this->repository->updateByLeft($movedLeft, \null, $diffBack);
            $this->repository->updateByRight($movedLeft, \null, $diffBack);
            
            $this->repository->updateById($current->getId(), $goal->getParent());
            $this->repository->commitTransaction();
        }
        
        /**
         * Current inside/under goal
         */
        elseif ($goal->getLeft() < $current->getLeft() && $current->getRight() < $goal->getRight()) {
            $this->repository->beginTransaction();
            
            $newLeft = $goal->getRight() + 1;
            $this->repository->updateByLeft($newLeft, \null, $leftRightDiff);
            $this->repository->updateByRight($newLeft, \null, $leftRightDiff);

            $move = $newLeft - $current->getLeft();
            $this->repository->updateByLeft($current->getLeft(), $current->getRight(), $move);
            $this->repository->updateByRight($current->getLeft(), $current->getRight(), $move);
            
            $afterCurrent = $current->getRight() + 1;
            $this->repository->updateByLeft($afterCurrent, \null, $diffBack);
            $this->repository->updateByRight($afterCurrent, \null, $diffBack);
            
            $this->repository->updateById($current->getId(), $goal->getParent());
            $this->repository->commitTransaction();
        }

        else {
            throw new \Sakura\Exceptions\RuntimeException;
        }
    }

    /**
     * @throws Exceptions\BadArgumentException
     * @throws Exceptions\RuntimeException
     */
    public function moveBranchAsFirstChild(INode $current, INode $goal): void
    {
        if (self::isRoot($current))
        {
            throw new Exceptions\BadArgumentException("The whole tree cannot be moved!");
        }

        $this->checkCurrenthWithGoal($current, $goal);
        $leftRightDiff = self::getRightLeftDifference($current->getLeft(), $current->getRight());
        $diffBack = $leftRightDiff * (-1);

        /**
         * Current before goal
         */
        if ($current->getRight() < $goal->getLeft()) {
            $this->repository->beginTransaction();
            
            $newLeft = $goal->getLeft() + 1;
            $this->repository->updateByRight($newLeft, \null, $leftRightDiff);
            $this->repository->updateByLeft($newLeft, \null, $leftRightDiff);
            
            $move = self::getMovement($goal->getLeft(), $current->getLeft());
            $this->repository->updateByLeft($current->getLeft(), $current->getRight(), $move);
            $this->repository->updateByRight($current->getLeft(), $current->getRight(), $move);
            
            $this->repository->updateByLeft($current->getLeft(), \null, $diffBack);
            $this->repository->updateByRight($current->getRight(), \null, $diffBack);
            
            $this->repository->updateById($current->getId(), $goal->getId());
            $this->repository->commitTransaction();
        }

        /**
         * Current after goal
         */
        elseif ($goal->getRight() < $current->getLeft()) {
            $this->repository->beginTransaction();
            
            $newLeft = $goal->getLeft() + 1;
            $this->repository->updateByLeft($newLeft, \null, $leftRightDiff);
            $this->repository->updateByRight($newLeft, \null, $leftRightDiff);

            $movedLeft = $current->getLeft() + $leftRightDiff;
            $movedRight = $current->getRight() + $leftRightDiff;
            $move = ($movedLeft - $newLeft) * (-1);
            $this->repository->updateByLeft($movedLeft, $movedRight, $move);
            $this->repository->updateByRight($movedLeft, $movedRight, $move);
            
            $this->repository->updateByLeft($movedLeft, \null, $diffBack);
            $this->repository->updateByRight($movedLeft, \null, $diffBack);
            
            $this->repository->updateById($current->getId(), $goal->getId());
            $this->repository->commitTransaction();
        }

        /**
         * Current inside/under goal
         */
        elseif ($goal->getLeft() < $current->getLeft() && $current->getRight() < $goal->getRight()) {
            $this->repository->beginTransaction();
            
            $newLeft = $goal->getLeft() + 1;
            $this->repository->updateByLeft($newLeft, \null, $leftRightDiff);
            $this->repository->updateByRight($newLeft, \null, $leftRightDiff);

            $movedLeft = $current->getLeft() + $leftRightDiff;
            $movedRight = $current->getRight() + $leftRightDiff;
            $move = ($movedLeft - $newLeft) * (-1);
            $this->repository->updateByLeft($movedLeft, $movedRight, $move);
            $this->repository->updateByRight($movedLeft, $movedRight, $move);
            
            $this->repository->updateByLeft($movedLeft, \null, $diffBack);
            $this->repository->updateByRight($movedLeft, \null, $diffBack);
            
            $this->repository->updateById($current->getId(), $goal->getId());
            $this->repository->commitTransaction();
        }

        else {
            throw new \Sakura\Exceptions\RuntimeException;
        }
    }

    private function checkCurrenthWithGoal(INode $current, INode $goal): void
    {
        $currentLeft = $current->getLeft();
        $currentRight = $current->getRight();
        $goalLeft = $goal->getLeft();
        $goalRight = $goal->getRight();

        if (
            ($currentLeft < $goalLeft && $goalRight < $currentRight)
            || ($currentLeft === $goalLeft)
        ) {
            throw new Exceptions\BadArgumentException("Goal destination can not be in same branch!");
        }

        if (
            ($goalLeft < $currentLeft && $currentRight < $goalLeft)
            || ($currentLeft < $goalLeft && $goalLeft < $currentRight)
            || ($goalRight < $currentLeft  && $currentRight < $goalRight)
            || ($currentLeft < $goalRight && $goalRight < $currentRight)
        ) {
            throw new Exceptions\RuntimeException("Current and/or goal nodes seem not valid.");
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
