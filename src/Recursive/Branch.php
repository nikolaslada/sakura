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

    public function getRootNode(): INode
    {
        return $this->root;
    }

}
