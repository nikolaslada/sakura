<?php
/**
 * This file is a part of the Sakura project <https://linuxclan.com/sakura-php>.
 * Copyright (c) 2015 - 2020 Nikolas Lada <https://nikolaslada.cz>.
 */

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
