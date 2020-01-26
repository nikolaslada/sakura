<?php
/**
 * This file is a part of the Sakura project <https://linuxclan.com/sakura-php>.
 * Copyright (c) 2015 - 2020 Nikolas Lada <https://nikolaslada.cz>.
 */

declare(strict_types=1);

namespace Sakura\Recursive;

interface IBranch
{

    public function getRootNode(): INode;

    public function existsNode(int $nodeId): bool;

}
