<?php

declare(strict_types=1);

namespace Sakura\Order;

interface IRepository
{

    public function addData(array $data): int;

    public function getNodeById(int $id): ?INode;

    public function getNodeByOrder(int $order): ?INode;

    public function getEndOrder(int $startOrder, int $startDepth): int;

    public function getNodeListByParent(int $parent): NodeList;

    public function getBranch(int $fromOrder, int $toOrder, ?int $maxDepth): NodeList;

    public function getDepthById(int $id): int;

    public function getNumberOfChilds(int $nodeId): int;

    public function updateParentByIdList(array $whereIdList, ?int $setParent): int;

    public function updateByOrder(int $fromOrder, ?int $toOrder, int $orderMovement, int $depthMovement): int;

    public function beginTransaction(): void;

    public function commitTransaction(): void;

    public function rollbackTransaction(): void;

    public function delete(int $id): int;

}
