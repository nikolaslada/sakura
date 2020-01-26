<?php
/**
 * This file is a part of the Sakura project <https://linuxclan.com/sakura-php>.
 * Copyright (c) 2015 - 2020 Nikolas Lada <https://nikolaslada.cz>.
 */

declare(strict_types=1);

namespace Sakura\Traversal;


final class Node implements INode
{

    /** @var int */
    private $id;
    
    /** @var int */
    private $left;
    
    /** @var int */
    private $right;
    
    /** @var null|int */
    private $parent;
    
    
    public function __construct(int $id, int $left, int $right, ?int $parent)
    {
        $this->id = $id;
        $this->left = $left;
        $this->right = $right;
        $this->parent = $parent;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function getRight(): int
    {
        return $this->right;
    }

    public function getParent(): ?int
    {
        return $this->parent;
    }

}
