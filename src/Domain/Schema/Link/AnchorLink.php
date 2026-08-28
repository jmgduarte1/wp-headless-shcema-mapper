<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema\Link;

final readonly class AnchorLink implements LinkModel
{
    public function __construct(
        public string $anchor,
    ) {
    }

    public function type(): string
    {
        return 'anchor';
    }
}
