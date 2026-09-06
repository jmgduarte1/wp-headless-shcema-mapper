<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class TabsData
{
    /**
     * @param list<array{id: string, label: string, icon?: string, blocks: list<PageBlock>}> $tabs
     */
    public function __construct(
        public array $tabs,
        public string $orientation = 'horizontal',
        public ?string $title = null,
        public int $activeIndex = 0,
    ) {
    }
}
