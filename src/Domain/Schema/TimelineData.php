<?php
declare(strict_types=1);
namespace HeadlessAngular\Schema\Domain\Schema;
final readonly class TimelineData
{
    /** @param list<array<string, mixed>> $periods */
    public function __construct(public array $periods, public ?string $eyebrow = null, public ?string $title = null, public ?string $linkLabel = null, public ?string $linkUrl = null, public string $linkPosition = 'end') {}
}
