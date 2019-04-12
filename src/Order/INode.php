<?php

declare(strict_types=1);

namespace Sakura\Order;

interface INode extends Sakura\INode
{

    public function getOrder(): int;

    public function getParent(): int;

}
