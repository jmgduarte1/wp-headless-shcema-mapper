<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class BlockStyle
{
    /**
     * @param array<string, string|int|float|array<string, string|int|float>> $properties
     */
    public function __construct(
        public ?string $variant = null,
        public array $properties = [],
    ) {
    }
}
