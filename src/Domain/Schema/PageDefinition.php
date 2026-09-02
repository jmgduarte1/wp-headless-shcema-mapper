<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class PageDefinition
{
    /**
     * @param list<PageBlock> $blocks
     */
    public function __construct(
        public string $id,
        public string $slug,
        public string $title,
        public PageStatus $status,
        public array $blocks,
        public ?SeoMetadata $seo = null,
    ) {
    }
}
