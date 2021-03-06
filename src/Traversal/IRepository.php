<?php
/**
 * This file is a part of the Sakura project <https://linuxclan.com/sakura-php>.
 * Copyright (c) 2015 - 2020 Nikolas Lada <https://nikolaslada.cz>.
 */

declare(strict_types=1);

namespace Sakura\Traversal;

interface IRepository
{

    public function addData(array $data): int;

    public function getNodeById(int $id): ?INode;

    public function getNodeByLeft(int $left): ?INode;

    public function getNodeListByParent(int $parent): NodeList;

    public function getBranch(INode $node): NodeList;

    public function getPath(INode $node, bool $isAscending): NodeList;

    public function getNumberOfChilds(int $id): int;

    public function updateById(int $whereId, ?int $setParent): int;

    public function updateByParent(int $whereParent, ?int $setParent): int;

    public function updateByLeft(int $from, ?int $to, int $movement);

    public function updateByRight(int $from, ?int $to, int $movement);

    public function beginTransaction(): void;

    public function commitTransaction(): void;

    public function delete(int $id): void;

}
