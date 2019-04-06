<?php

declare(strict_types=1);

namespace Sakura\Traversal;

use Sakura\ITable;

final class Table implements ITable {

    /** @var string */
    private $name;
    
    /** @var string */
    private $idColumn;
    
    /** @var string */
    private $leftColumn;
    
    /** @var string */
    private $rightColumn;
    
    /** @var string */
    private $parentColumn;
    
    
    public function __construct(string $name, string $idColumn, string $leftColumn, string $rightColumn, string $parentColumn) {
      $this->name = $name;
      $this->idColumn = $idColumn;
      $this->leftColumn = $leftColumn;
      $this->rightColumn = $rightColumn;
      $this->parentColumn = $parentColumn;
    }

    public function getName(): string {
      return $this->name;
    }

    public function getIdColumn(): string {
      return $this->idColumn;
    }

    public function getLeftColumn(): string {
      return $this->leftColumn;
    }

    public function getRightColumn(): string {
      return $this->rightColumn;
    }

    public function getParentColumn(): string {
      return $this->parentColumn;
    }

}
