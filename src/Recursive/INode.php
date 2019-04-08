<?php

declare(strict_types=1);

namespace Sakura\Recursive;

interface INode extends Sakura\INode
{

    public function getParent(): int;

}
