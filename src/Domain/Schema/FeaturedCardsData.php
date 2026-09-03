<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class FeaturedCardsData
{
    /**
     * @param list<array<string, mixed>> $cards
     */
    public function __construct(public array $cards)
    {
    }
}
