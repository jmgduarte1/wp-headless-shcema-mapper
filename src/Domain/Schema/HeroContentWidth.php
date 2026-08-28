<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

enum HeroContentWidth: string
{
    case Narrow = 'narrow';
    case Medium = 'medium';
    case Wide = 'wide';
    case Full = 'full';
}
