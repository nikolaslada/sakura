<?php
/**
 * This file is a part of the Sakura project <https://linuxclan.com/sakura-php>.
 * Copyright (c) 2015 - 2020 Nikolas Lada <https://nikolaslada.cz>.
 */

declare(strict_types=1);

namespace Sakura\Order;

final class Node implements INode
{

    /** @var int */
    private $id;
    
    /** @var int */
    private $depth;
    
    /** @var int */
    private $order;
    
    /** @var null|int */
    private $parent;
    
    
    public function __construct(int $id, int $depth, int $order, ?int $parent)
    {
        $this->id = $id;
        $this->depth = $depth;
        $this->order = $order;
        $this->parent = $parent;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDepth(): int
    {
      return $this->depth;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function getParent(): ?int
    {
        return $this->parent;
    }

}
