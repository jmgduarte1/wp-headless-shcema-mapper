<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class HeroMedia
{
    public function __construct(
        public MediaAsset $image,
        public HeroMediaPlacement $placement,
    ) {
    }
}
