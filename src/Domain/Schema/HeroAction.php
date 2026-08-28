<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

use HeadlessAngular\Schema\Domain\Schema\Link\LinkModel;

final readonly class HeroAction
{
    public function __construct(
        public string $id,
        public string $label,
        public LinkModel $link,
        public ?HeroActionVariant $variant = null,
        public ?string $accessibleLabel = null,
    ) {
    }
}
