<?php
/**
 * This file is a part of the Sakura project <https://linuxclan.com/sakura-php>.
 * Copyright (c) 2015 - 2020 Nikolas Lada <https://nikolaslada.cz>.
 */

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
