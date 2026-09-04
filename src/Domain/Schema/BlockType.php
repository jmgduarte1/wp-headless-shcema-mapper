<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final class BlockType
{
    public const HERO = 'hero';
    public const CONTAINER = 'container';
    public const TEXT = 'text';
    public const IMAGE = 'image';
    public const LINK = 'link';
    public const SPACER = 'spacer';
    public const DETAILS = 'details';
    public const SEPARATOR = 'separator';
    public const FEATURED_CARDS = 'featured-cards';
    public const TIMELINE = 'timeline';
    public const GALLERY = 'gallery';
    public const TABS = 'tabs';
    public const ACCORDION = 'accordion';
    public const TOOLTIP = 'tooltip';
    public const FORM = 'form';

    private function __construct()
    {
    }
}
