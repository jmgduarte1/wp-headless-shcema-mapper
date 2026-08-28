<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class PageBlock
{
    public function __construct(
        public string $id,
        public string $type,
        public object $data,
        public ?BlockStyle $style = null,
    ) {
    }
}
