<?php

declare(strict_types=1);

namespace Sakura\Order;

use Sakura\ITable;

final class Table implements ITable {

    /** @var string */
    private $name;
    
    /** @var string */
    private $idColumn;
    
    /** @var string */
    private $depthColumn;
    
    /** @var string */
    private $orderColumn;
    
    /** @var string */
    private $parentColumn;
    
    
    public function __construct(string $name, string $idColumn, string $depthColumn, string $orderColumn, string $parentColumn)
    {
        $this->name = $name;
        $this->idColumn = $idColumn;
        $this->depthColumn = $depthColumn;
        $this->orderColumn = $orderColumn;
        $this->parentColumn = $parentColumn;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIdColumn(): string
    {
        return $this->idColumn;
    }

    public function getDepthColumn(): string
    {
        return $this->depthColumn;
    }

    public function getOrderColumn(): string
    {
        return $this->orderColumn;
    }

    public function getParentColumn(): string
    {
        return $this->parentColumn;
    }

}
