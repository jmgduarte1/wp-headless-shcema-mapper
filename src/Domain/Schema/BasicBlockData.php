<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class BasicBlockData
{
    /**
     * @param array<string, string|int|float|bool|array<string, string|int|float|bool>> $attributes
     */
    public function __construct(
        public ?string $text = null,
        public ?string $html = null,
        public ?string $src = null,
        public ?string $srcSet = null,
        public ?string $loading = null,
        public ?string $mimeType = null,
        public ?string $caption = null,
        public ?string $alt = null,
        public ?string $href = null,
        public ?string $target = null,
        public ?string $rel = null,
        public ?string $summary = null,
        public ?bool $open = null,
        public ?string $layout = null,
        public ?string $customCss = null,
        public array $attributes = [],
    ) {
    }
}
