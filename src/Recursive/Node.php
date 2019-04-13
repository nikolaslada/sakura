<?php

declare(strict_types=1);

namespace Sakura\Recursive;

final class Node implements INode
{

    /** @var int */
    private $id;
    
    /** @var null|int */
    private $parent;
    
    
    public function __construct(int $id, ?int $parent)
    {
        $this->id = $id;
        $this->parent = $parent;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getParent(): ?int
    {
        return $this->parent;
    }

}
