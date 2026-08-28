<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

enum HeroAlignment: string
{
    case Start = 'start';
    case Center = 'center';
    case End = 'end';
}
