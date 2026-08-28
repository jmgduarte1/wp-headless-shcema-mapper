<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class HeroLayout
{
    public function __construct(
        public ?HeroAlignment $contentAlignment = null,
        public ?HeroAlignment $verticalAlignment = null,
        public ?HeroContentWidth $contentWidth = null,
    ) {
    }
}
