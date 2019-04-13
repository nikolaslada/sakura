<?php

declare(strict_types=1);

namespace Sakura\Order;

interface INode
{

    public function getId(): int;

    public function getDepth(): int;

    public function getOrder(): int;

    public function getParent(): ?int;

}
