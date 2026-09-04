<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class TabsData
{
    /**
     * @param list<array{id: string, label: string, blocks: list<PageBlock>}> $tabs
     */
    public function __construct(
        public array $tabs,
        public int $activeIndex = 0,
    ) {
    }
}
