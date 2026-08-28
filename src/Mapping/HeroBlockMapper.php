<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Mapping;

use HeadlessAngular\Schema\Domain\Schema\BlockType;
use HeadlessAngular\Schema\Domain\Schema\HeroBlockData;
use HeadlessAngular\Schema\Domain\Schema\PageBlock;
use InvalidArgumentException;

final class HeroBlockMapper implements BlockMapper
{
    /**
     * @param array<string, mixed> $block
     */
    public function supports(array $block): bool
    {
        return ($block['blockName'] ?? null) === 'headless-angular/hero';
    }

    /**
     * @param array<string, mixed> $block
     */
    public function map(array $block): PageBlock
    {
        $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
        $title = isset($attrs['title']) && is_string($attrs['title']) ? trim($attrs['title']) : '';

        if ($title === '') {
            throw new InvalidArgumentException('Hero title is required.');
        }

        $id = isset($attrs['id']) && is_string($attrs['id']) && trim($attrs['id']) !== ''
            ? trim($attrs['id'])
            : 'hero-' . substr(md5($title), 0, 8);

        $eyebrow = isset($attrs['eyebrow']) && is_string($attrs['eyebrow']) && trim($attrs['eyebrow']) !== ''
            ? trim($attrs['eyebrow'])
            : null;

        $subtitle = isset($attrs['subtitle']) && is_string($attrs['subtitle']) && trim($attrs['subtitle']) !== ''
            ? trim($attrs['subtitle'])
            : null;

        return new PageBlock(
            id: $id,
            type: BlockType::HERO,
            data: new HeroBlockData(
                title: $title,
                eyebrow: $eyebrow,
                subtitle: $subtitle,
            ),
        );
    }
}
