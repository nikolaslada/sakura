<?php
/**
 * This file is a part of the Sakura project <https://linuxclan.com/sakura-php>.
 * Copyright (c) 2015 - 2020 Nikolas Lada <https://nikolaslada.cz>.
 */

declare(strict_types=1);

namespace Sakura\Order;

final class Factory
{

    public function createNodeList(array $list): NodeList
    {
        return new NodeList($list);
    }

}
