<?php
/**
 * This file is a part of the Sakura project <https://linuxclan.com/sakura-php>.
 * Copyright (c) 2015 - 2020 Nikolas Lada <https://nikolaslada.cz>.
 */

declare(strict_types=1);

namespace Sakura\Recursive;

use Sakura\ITable;

final class Table implements ITable {

    /** @var string */
    private $name;
    
    /** @var string */
    private $idColumn;
    
    /** @var string */
    private $parentColumn;
    
    
    public function __construct(string $name, string $idColumn, string $parentColumn)
    {
        $this->name = $name;
        $this->idColumn = $idColumn;
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

    public function getParentColumn(): string
    {
        return $this->parentColumn;
    }

}
