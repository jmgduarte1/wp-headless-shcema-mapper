<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

enum HeroActionVariant: string
{
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Tertiary = 'tertiary';
}
