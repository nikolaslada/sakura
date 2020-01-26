<?php
/**
 * This file is a part of the Sakura project <https://linuxclan.com/sakura-php>.
 * Copyright (c) 2015 - 2020 Nikolas Lada <https://nikolaslada.cz>.
 */

declare(strict_types=1);

namespace Sakura\Order;

interface INode
{

    public function getId(): int;

    public function getDepth(): int;

    public function getOrder(): int;

    public function getParent(): ?int;

}
