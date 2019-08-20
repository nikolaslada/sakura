<?php

declare(strict_types=1);

namespace Sakura\Recursive;

final class Branch extends \ArrayIterator implements IBranch
{

    /** @var INode */
    private $root;


    public function __construct(INode $root, array $list)
    {
        $this->root = $root;
        parent::__construct($list);
    }

    /**
     * @throws \RuntimeException
     */
    public function current(): Branch
    {
        $current = parent::current();

        if ($current instanceof Branch)
        {
            return $current;
        } else {
            throw new \RuntimeException;
        }
    }

    public function getRootNode(): INode
    {
        return $this->root;
    }

    public function existsNode(int $nodeId): bool
    {
        if ($this->root->getId() === $nodeId) {
            return true;
        }

        return self::searchInBranch($this, $nodeId);
    }
    
    private static function searchInBranch(Branch $branch, int $nodeId): bool
    {
        while($branch->valid()) {
            $subBranch = $branch->current();
            $node = $subBranch->getRootNode();

            if ($node->getId() === $nodeId) {
                $branch->rewind();
                return true;
            }

            $result = self::searchInBranch($subBranch, $nodeId);

            if ($result) {
                $branch->rewind();
                return $result;
            } else {
                $branch->next();
            }
        }

        $branch->rewind();
        return false;
    }

}
