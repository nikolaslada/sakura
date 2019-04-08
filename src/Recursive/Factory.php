<?php

declare(strict_types=1);

namespace Sakura\Recursive;

final class Factory
{

    public function createBranch(): Branch
    {
        return new Branch;
    }

    public function createNodeList(array $list): NodeList
    {
        return new NodeList($list);
    }

}
