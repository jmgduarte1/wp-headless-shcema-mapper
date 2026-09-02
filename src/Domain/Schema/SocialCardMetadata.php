<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class SocialCardMetadata
{
    public function __construct(
        public string $card = 'summary',
        public ?string $title = null,
        public ?string $description = null,
        public ?MediaAsset $image = null,
    ) {
    }
}
