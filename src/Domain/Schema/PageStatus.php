<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

enum PageStatus: string
{
    case Published = 'published';
    case Preview = 'preview';
    case Private = 'private';
}
