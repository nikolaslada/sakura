<?php

declare(strict_types=1);

namespace Sakura\Order;

use Sakura\INode;

final class Node implements INode
{

    /** @var int */
    private $id;
    
    /** @var int */
    private $order;
    
    /** @var int */
    private $parent;
    
    
    public function __construct(int $id, int $order, int $parent) {
      $this->id = $id;
      $this->order = $order;
      $this->parent = $parent;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function getParent(): int
    {
        return $this->parent;
    }

}
