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

        if ($current instanceof Branch) {
            return $current;
        } else {
            throw new \RuntimeException('current value should be Recursive\Branch and it is not!');
        }
    }

    public function getRootNode(): INode
    {
        return $this->root;
    }

}
