<?php

declare(strict_types=1);

namespace Sakura\Recursive;

interface INode
{

    public function getId(): int;

    public function getParent(): ?int;

}
