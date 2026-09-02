<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class OpenGraphMetadata
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $url = null,
        public ?string $type = null,
        public ?MediaAsset $image = null,
    ) {
    }
}
