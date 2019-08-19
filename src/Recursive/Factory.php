<?php

declare(strict_types=1);

namespace Sakura\Recursive;

final class Factory
{

    public function createBranch(INode $node): Branch
    {
        return new Branch($node, []);
    }

    public function createNodeList(array $list): NodeList
    {
        return new NodeList($list);
    }

}
