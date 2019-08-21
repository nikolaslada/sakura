<?php

declare(strict_types=1);

namespace Sakura\Recursive;

interface IRepository
{

    /**
     * @throws Exceptions\RuntimeException
     */
    public function addData(array $data): int;

    public function getNodesByParent(int $id): array;

    public function getParentById(int $id): ?int;

    public function getNodeById(int $id): ?INode;

    public function getNumberOfChilds(int $nodeId): int;

    public function getIdsByParent(int $parent): array;

    /**
     * @throws Exceptions\NoRootException
     */
    public function getRoot(): INode;

    public function updateParentByIdList(array $whereIdList, ?int $setParent): int;

    public function beginTransaction(): void;

    public function commitTransaction(): void;

    public function delete(int $id): void;

}
