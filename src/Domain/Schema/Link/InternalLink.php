<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema\Link;

final readonly class InternalLink implements LinkModel
{
    public function __construct(
        public string $path,
    ) {
    }

    public function type(): string
    {
        return 'internal';
    }
}
