<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Mapping;

use HeadlessAngular\Schema\Domain\Schema\PageBlock;
use InvalidArgumentException;

final class BlockMapperRegistry
{
    /**
     * @param list<BlockMapper> $mappers
     */
    public function __construct(
        private readonly array $mappers = [],
    ) {
    }

    /**
     * @param array<string, mixed> $block
     */
    public function map(array $block): PageBlock
    {
        foreach ($this->mappers as $mapper) {
            if ($mapper->supports($block)) {
                return $mapper->map($block);
            }
        }

        throw new InvalidArgumentException('Unsupported Gutenberg block.');
    }
}
