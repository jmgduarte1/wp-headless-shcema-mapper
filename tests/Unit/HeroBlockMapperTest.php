<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Tests\Unit;

use HeadlessAngular\Schema\Domain\Schema\HeroBlockData;
use HeadlessAngular\Schema\Mapping\HeroBlockMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class HeroBlockMapperTest extends TestCase
{
    public function testMapsCompleteHeroBlock(): void
    {
        $mapper = new HeroBlockMapper();
        $block = $mapper->map([
            'blockName' => 'headless-angular/hero',
            'attrs' => [
                'id' => 'hero-main',
                'eyebrow' => 'Welcome',
                'title' => 'Software Engineer',
                'subtitle' => 'Building maintainable digital experiences',
                'media' => [
                    'placement' => 'end',
                    'image' => [
                        'src' => 'https://cms.example.com/hero.webp',
                        'alt' => 'Software engineer',
                        'width' => 800,
                        'height' => 800,
                    ],
                ],
                'actions' => [
                    [
                        'id' => 'view-work',
                        'label' => 'View work',
                        'variant' => 'primary',
                        'link' => [
                            'type' => 'internal',
                            'path' => '/work',
                        ],
                    ],
                ],
                'layout' => [
                    'contentAlignment' => 'start',
                    'verticalAlignment' => 'center',
                    'contentWidth' => 'wide',
                ],
                'style' => [
                    'variant' => 'primary',
                    'properties' => [
                        'minHeight' => '70vh',
                        'padding' => [
                            'mobile' => '32px 20px',
                            'desktop' => '80px 64px',
                        ],
                    ],
                ],
            ],
        ]);

        self::assertSame('hero-main', $block->id);
        self::assertSame('hero', $block->type);
        self::assertInstanceOf(HeroBlockData::class, $block->data);
        self::assertSame('Software Engineer', $block->data->title);
        self::assertSame('end', $block->data->media?->placement->value);
        self::assertSame('primary', $block->data->actions[0]->variant?->value);
        self::assertSame('wide', $block->data->layout?->contentWidth?->value);
        self::assertSame('70vh', $block->style?->properties['minHeight']);
    }

    public function testMapsCoreCoverToHeroBlock(): void
    {
        $block = (new HeroBlockMapper())->map([
            'blockName' => 'core/cover',
            'attrs' => [
                'anchor' => 'home-hero',
                'url' => 'https://cms.example.com/cover.webp',
                'alt' => 'Workspace desk',
                'minHeight' => 70,
                'minHeightUnit' => 'vh',
                'contentPosition' => 'center left',
                'align' => 'wide',
            ],
            'innerBlocks' => [
                [
                    'blockName' => 'core/heading',
                    'attrs' => [
                        'content' => 'Software Engineer',
                    ],
                ],
                [
                    'blockName' => 'core/paragraph',
                    'attrs' => [
                        'content' => 'Building maintainable digital experiences',
                    ],
                ],
                [
                    'blockName' => 'core/buttons',
                    'innerBlocks' => [
                        [
                            'blockName' => 'core/button',
                            'attrs' => [
                                'text' => 'View work',
                                'url' => '/work',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertSame('home-hero', $block->id);
        self::assertSame('hero', $block->type);
        self::assertInstanceOf(HeroBlockData::class, $block->data);
        self::assertSame('Software Engineer', $block->data->title);
        self::assertSame('Building maintainable digital experiences', $block->data->subtitle);
        self::assertSame('background', $block->data->media?->placement->value);
        self::assertSame('https://cms.example.com/cover.webp', $block->data->media?->image->src);
        self::assertSame('Workspace desk', $block->data->media?->image->alt);
        self::assertSame('View work', $block->data->actions[0]->label);
        self::assertSame('/work', $block->data->actions[0]->link->path);
        self::assertSame('start', $block->data->layout?->contentAlignment?->value);
        self::assertSame('center', $block->data->layout?->verticalAlignment?->value);
        self::assertSame('wide', $block->data->layout?->contentWidth?->value);
        self::assertSame('70vh', $block->style?->properties['minHeight']);
    }

    public function testMapsNativeGroupColumnsHeroPattern(): void
    {
        $block = (new HeroBlockMapper())->map([
            'blockName' => 'core/group',
            'attrs' => [
                'metadata' => [
                    'patternName' => 'twentytwentyfive/hero-book',
                    'name' => 'Hero book',
                ],
                'align' => 'full',
                'style' => [
                    'spacing' => [
                        'margin' => [
                            'top' => '0',
                            'bottom' => '0',
                        ],
                    ],
                ],
            ],
            'innerBlocks' => [
                [
                    'blockName' => 'core/columns',
                    'attrs' => [
                        'align' => 'full',
                        'style' => [
                            'spacing' => [
                                'blockGap' => [
                                    'left' => '0',
                                ],
                            ],
                        ],
                    ],
                    'innerBlocks' => [
                        [
                            'blockName' => 'core/column',
                            'attrs' => [
                                'width' => '55%',
                            ],
                            'innerBlocks' => [
                                [
                                    'blockName' => 'core/cover',
                                    'attrs' => [
                                        'url' => 'http://localhost/wp-content/themes/twentytwentyfive/assets/images/book-image-landing.webp',
                                        'alt' => 'Image of the book',
                                        'dimRatio' => 0,
                                        'customOverlayColor' => '#6b6b6b',
                                        'style' => [
                                            'dimensions' => [
                                                'aspectRatio' => '1',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'blockName' => 'core/column',
                            'attrs' => [
                                'verticalAlignment' => 'center',
                                'style' => [
                                    'spacing' => [
                                        'padding' => [
                                            'top' => 'var:preset|spacing|60',
                                            'bottom' => 'var:preset|spacing|60',
                                            'left' => 'var:preset|spacing|60',
                                            'right' => 'var:preset|spacing|60',
                                        ],
                                    ],
                                ],
                            ],
                            'innerBlocks' => [
                                [
                                    'blockName' => 'core/heading',
                                    'attrs' => [
                                        'className' => 'has-xx-large-font-size',
                                    ],
                                    'innerHTML' => '<h2 class="wp-block-heading has-xx-large-font-size">The Stories Book</h2>',
                                ],
                                [
                                    'blockName' => 'core/paragraph',
                                    'innerHTML' => '<p>A fine collection of moments in time featuring photographs.</p>',
                                ],
                                [
                                    'blockName' => 'core/paragraph',
                                    'attrs' => [
                                        'fontSize' => 'medium',
                                    ],
                                    'innerHTML' => '<p class="has-medium-font-size">Available for pre-order now.</p>',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertSame('hero-8ef428cb', $block->id);
        self::assertSame('hero', $block->type);
        self::assertSame('section', $block->element);
        self::assertInstanceOf(HeroBlockData::class, $block->data);
        self::assertSame('The Stories Book', $block->data->title);
        self::assertSame(
            "A fine collection of moments in time featuring photographs.\n\nAvailable for pre-order now.",
            $block->data->subtitle,
        );
        self::assertSame('start', $block->data->media?->placement->value);
        self::assertSame(
            'http://localhost/wp-content/themes/twentytwentyfive/assets/images/book-image-landing.webp',
            $block->data->media?->image->src,
        );
        self::assertSame('Image of the book', $block->data->media?->image->alt);
        self::assertSame('full', $block->data->layout?->contentWidth?->value);
        self::assertSame('media-split', $block->style?->variant);
        self::assertSame('55%', $block->style?->properties['mediaWidth']);
        self::assertSame('1', $block->style?->properties['mediaAspectRatio']);
        self::assertSame('#6b6b6b', $block->style?->properties['overlayColor']);
        self::assertSame(0, $block->style?->properties['overlayOpacity']);
        self::assertSame(
            'var(--wp--preset--spacing--60)',
            $block->style?->properties['contentPadding']['top'],
        );
    }

    public function testRejectsMissingTitle(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hero title is required.');

        (new HeroBlockMapper())->map([
            'blockName' => 'headless-angular/hero',
            'attrs' => [],
        ]);
    }

    public function testRejectsInaccessibleMedia(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hero media image requires alt text or decorative=true.');

        (new HeroBlockMapper())->map([
            'blockName' => 'headless-angular/hero',
            'attrs' => [
                'title' => 'Hello',
                'media' => [
                    'placement' => 'background',
                    'image' => [
                        'src' => 'https://cms.example.com/hero.webp',
                    ],
                ],
            ],
        ]);
    }

    public function testRejectsUnsafeExternalLink(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('External links must use http or https URLs.');

        (new HeroBlockMapper())->map([
            'blockName' => 'headless-angular/hero',
            'attrs' => [
                'title' => 'Hello',
                'actions' => [
                    [
                        'id' => 'bad-link',
                        'label' => 'Bad link',
                        'link' => [
                            'type' => 'external',
                            'url' => 'javascript:alert(1)',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function testRejectsUnsafeStyleValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hero style value is unsafe.');

        (new HeroBlockMapper())->map([
            'blockName' => 'headless-angular/hero',
            'attrs' => [
                'title' => 'Hello',
                'style' => [
                    'properties' => [
                        'backgroundColor' => 'url(https://example.com/x)',
                    ],
                ],
            ],
        ]);
    }
}
