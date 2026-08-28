<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class HeroBlockData
{
    /**
     * @param list<HeroAction> $actions
     */
    public function __construct(
        public string $title,
        public ?string $eyebrow = null,
        public ?string $subtitle = null,
        public ?HeroMedia $media = null,
        public array $actions = [],
        public ?HeroLayout $layout = null,
    ) {
    }
}
