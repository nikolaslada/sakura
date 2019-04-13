<?php

declare(strict_types=1);

namespace Sakura\Traversal;

final class Factory
{

    public function createNodeList(array $list): NodeList
    {
        return new NodeList($list);
    }

}
