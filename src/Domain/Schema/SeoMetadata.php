<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class SeoMetadata
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $canonical = null,
        public ?RobotsMetadata $robots = null,
        public ?OpenGraphMetadata $openGraph = null,
        public ?SocialCardMetadata $twitter = null,
    ) {
    }
}
