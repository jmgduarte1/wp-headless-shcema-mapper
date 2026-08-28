<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class MediaAsset
{
    public function __construct(
        public string $src,
        public ?string $alt = null,
        public bool $decorative = false,
        public ?int $width = null,
        public ?int $height = null,
    ) {
    }
}
