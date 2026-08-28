<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Mapping;

use HeadlessAngular\Schema\Domain\Schema\PageBlock;

interface BlockMapper
{
    /**
     * @param array<string, mixed> $block
     */
    public function supports(array $block): bool;

    /**
     * @param array<string, mixed> $block
     */
    public function map(array $block): PageBlock;
}
