<?php

declare(strict_types=1);

namespace Sakura\Traversal;

interface INode
{

    public function getId(): int;

    public function getLeft(): int;

    public function getRight(): int;

    public function getParent(): int;

}
