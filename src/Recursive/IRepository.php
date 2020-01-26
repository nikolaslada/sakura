<?php
/**
 * This file is a part of the Sakura project <https://linuxclan.com/sakura-php>.
 * Copyright (c) 2015 - 2020 Nikolas Lada <https://nikolaslada.cz>.
 */

declare(strict_types=1);

namespace Sakura\Recursive;

interface IRepository
{

    /**
     * @throws Exceptions\RuntimeException
     */
    public function addData(array $data): int;

    public function getNodeListByParent(int $id): NodeList;

    public function getParentById(int $id): ?int;

    public function getNodeById(int $id): ?INode;

    public function getNumberOfChilds(int $nodeId): int;

    public function getIdListByParent(int $parent): array;

    /**
     * @throws Exceptions\NoRootException
     */
    public function getRoot(): INode;

    public function updateParentByIdList(array $whereIdList, ?int $setParent): int;

    public function beginTransaction(): void;

    public function commitTransaction(): void;

    public function delete(int $id): void;

}
