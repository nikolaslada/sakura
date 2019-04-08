<?php

declare(strict_types=1);

namespace Sakura;

interface INodeList
{

    public function getById(int $id): INode;

}
