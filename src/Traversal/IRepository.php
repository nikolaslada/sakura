<?php

declare(strict_types=1);

namespace Sakura\Traversal;

interface IRepository
{

    public function addData(array $data): int;

    public function getNodeById(int $id): INode;

    public function getNodeByLeft(int $left): INode;

    public function getNodesByParent(int $parent): NodeList;

    public function getBranch(int $left, int $right): NodeList;

    public function getPath(INode $id): NodeList;

    public function getNumberOfChilds(int $nodeId): int;

    public function updateByIdList(array $whereIdList, ?int $setParent);

    public function updateByLeftRight(int $fromLeft, ?int $toLeft, int $fromRight, ?int $toRight, int $leftMovement, int $rightMovement);

    public function beginTransaction();

    public function commitTransaction();

    public function delete(int $id);

}
