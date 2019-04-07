<?php

declare(strict_types=1);

namespace Sakura\Level;

use Sakura\ITable;

final class Table implements ITable
{

    /** @var string */
    private $name;
    
    /** @var string */
    private $idColumn;
    
    /** @var int */
    private $numberOfLevels;
    
    /** @var string */
    private $levelColumnName;
    
    
    public function __construct(string $name, string $idColumn, int $numberOfLevels, string $levelColumnName) {
        $this->name = $name;
        $this->idColumn = $idColumn;
        $this->numberOfLevels = $numberOfLevels;
        $this->levelColumnName = $levelColumnName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIdColumn(): string
    {
        return $this->idColumn;
    }

    public function getNumberOfLevels(): int
    {
        return $this->numberOfLevels;
    }

    public function getLevelColumnName(): string
    {
        return $this->levelColumnName;
    }

    public function getSelectLevelColumns(int $fromLevel): array
    {
        $columns = [];
        
        for ($i = $fromLevel; $i <= $this->numberOfLevels; $i++)
        {
            $columns[] = $this->levelColumnName . $i;
        }
        
        return $columns;
    }

    /**
     * @throws \Sakura\Exceptions\RuntimeException
     */
    public function getWhereLevelColumns(Node $currentNode, int $upToLevel): array
    {
        $columns = [];
        
        for ($i = 1; $i <= $upToLevel; $i++)
        {
            $columnName = $this->levelColumnName . $i;
            $columns[$columnName] = $currentNode->getLevelByColumn($columnName);
        }
        
        return $columns;
    }

}
