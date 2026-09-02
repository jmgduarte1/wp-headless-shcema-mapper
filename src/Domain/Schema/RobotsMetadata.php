<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class RobotsMetadata
{
    public function __construct(
        public bool $index = true,
        public bool $follow = true,
    ) {
    }
}
