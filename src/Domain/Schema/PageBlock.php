<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class PageBlock
{
    /**
     * @param list<PageBlock> $children
     */
    public function __construct(
        public string $id,
        public string $type,
        public object $data,
        public ?BlockStyle $style = null,
        public string $element = 'section',
        public array $children = [],
    ) {
    }
}
