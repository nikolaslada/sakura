<?php

declare(strict_types=1);

namespace Sakura;

interface ITree
{

    public function getNode(int $id): INode;

    public function getParent(INode $node): INode;

    public function getRoot(): INode;

    public function getPath(INode $node): INodeList;

    public function getDepth(INode $node): int;

    public function getNumberOfChilds(INode $node): int;

    public function getBranch(INode $node, int $maxDepth): IBranch;

    /**
     * @return int Id of a new node.
     */
    public function createNodeAsFirstChild(array $data, INode $parentNode): int;

    /**
     * @return int Id of a new node.
     */
    public function createNodeAfter(array $data, INode $previousNode): int;

    public function removeNode(int $id): void;

    public function moveBranchAsFirstChild(INode $branchNode, INode $parent): void;

    public function moveBranchAfter(INode $branchNode, INode $previousNode): void;

}
