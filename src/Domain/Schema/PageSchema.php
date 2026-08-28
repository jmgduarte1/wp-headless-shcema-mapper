<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class PageSchema
{
    public const VERSION = '1.0';

    public function __construct(
        public string $locale,
        public PageDefinition $page,
    ) {
    }
}
