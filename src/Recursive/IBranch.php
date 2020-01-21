<?php

declare(strict_types=1);

namespace Sakura\Recursive;

interface IBranch
{

    public function getRootNode(): INode;

    public function existsNode(int $nodeId): bool;

}