<?php

declare(strict_types=1);

namespace Sakura\Level;

use Sakura\INode;
use Sakura\Exceptions;

final class Node implements INode
{

    /** @var int */
    private $id;
    
    /** @var array */
    private $levels;
    
    
    public function __construct(int $id, array $levels) {
        $this->id = $id;
        $this->levels = $levels;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLevels(): array
    {
        return $this->levels;
    }

    /**
     * @throws Exceptions\RuntimeException
     */
    public function getLevelByColumn(string $column): int
    {
        if (!isset($this->levels[$column]))
        {
            throw new Exceptions\RuntimeException("There is no column: $column", 500);
        }
        
        return $this->levels[$column];
    }

    /**
     * @throws Exceptions\RuntimeException
     */
    public function getLevelListByColumns(string ...$columns): array
    {
        $levels = [];
        
        foreach ($columns as $column)
        {
            $levels[$column] = $this->getLevelByColumn($column);
        }
        
        return $levels;
    }

}
