<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema\Link;

final readonly class ExternalLink implements LinkModel
{
    /**
     * @param list<string> $rel
     */
    public function __construct(
        public string $url,
        public ?string $target = null,
        public array $rel = [],
    ) {
    }

    public function type(): string
    {
        return 'external';
    }
}
