<?php
/**
 * This file is a part of the Sakura project <https://linuxclan.com/sakura-php>.
 * Copyright (c) 2015 - 2020 Nikolas Lada <https://nikolaslada.cz>.
 */

declare(strict_types=1);

namespace Sakura\Traversal;

final class NodeList extends \ArrayIterator
{

    public function getById(int $id): ?INode
    {
        if (isset($this[$id])) {
            $node = $this[$id];
            /* @var $node INode */
            if ($node->getId() !== $id) {
                throw new \Sakura\Exceptions\RuntimeException("Id of Node is not same as key: $id");
            }

            return $node;
        } else {
            return \null;
        }
    }

}
