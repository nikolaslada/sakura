<?php

declare(strict_types=1);

namespace Sakura\Order;

final class Factory
{

    public function createNodeList(array $list): NodeList
    {
        return new NodeList($list);
    }

}
