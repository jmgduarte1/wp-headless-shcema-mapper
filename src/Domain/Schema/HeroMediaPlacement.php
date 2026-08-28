<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

enum HeroMediaPlacement: string
{
    case Background = 'background';
    case Start = 'start';
    case End = 'end';
}
