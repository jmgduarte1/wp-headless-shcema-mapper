<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Tests\Unit;

use HeadlessAngular\Schema\Domain\Schema\BasicBlockData;
use HeadlessAngular\Schema\Domain\Schema\BlockType;
use HeadlessAngular\Schema\Mapping\BasicBlockMapper;
use PHPUnit\Framework\TestCase;

final class BasicBlockMapperTest extends TestCase
{
    public function testMapsColumnsAndColumnBlocks(): void
    {
        $mapper = new BasicBlockMapper();
        $block = $mapper->map([
            'blockName' => 'core/columns',
            'attrs' => [
                'isStackedOnMobile' => false,
                'verticalAlignment' => 'center',
                'style' => [
                    'spacing' => [
                        'blockGap' => 'var:preset|spacing|50',
                    ],
                ],
            ],
            'innerBlocks' => [
                [
                    'blockName' => 'core/column',
                    'attrs' => [
                        'width' => '33.33%',
                        'verticalAlignment' => 'top',
                    ],
                    'innerBlocks' => [
                        [
                            'blockName' => 'core/paragraph',
                            'attrs' => ['content' => 'Col 1'],
                        ],
                    ],
                ],
                [
                    'blockName' => 'core/column',
                    'attrs' => [
                        'width' => 33.33,
                        'verticalAlignment' => 'center',
                    ],
                    'innerBlocks' => [
                        [
                            'blockName' => 'core/paragraph',
                            'attrs' => ['content' => 'Col 2'],
                        ],
                    ],
                ],
                [
                    'blockName' => 'core/column',
                    'attrs' => [],
                    'innerBlocks' => [
                        [
                            'blockName' => 'core/paragraph',
                            'attrs' => ['content' => 'Col 3'],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertSame(BlockType::CONTAINER, $block->type);
        self::assertInstanceOf(BasicBlockData::class, $block->data);
        self::assertSame('columns', $block->data->layout);
        self::assertSame(3, $block->data->attributes['columnCount']);
        self::assertSame(3, $block->data->attributes['columns']);
        self::assertFalse($block->data->attributes['isStackedOnMobile']);
        self::assertSame('var(--wp--preset--spacing--50)', $block->style?->properties['gap']);
        self::assertArrayNotHasKey('alignItems', $block->style?->properties ?? []);
        self::assertCount(3, $block->children);

        // Child 1
        $col1 = $block->children[0];
        self::assertSame('column', $col1->data->layout);
        self::assertSame('33.33%', $col1->style?->properties['width']);
        self::assertSame('flex-start', $col1->style?->properties['alignSelf']);

        // Child 2
        $col2 = $block->children[1];
        self::assertSame('column', $col2->data->layout);
        self::assertSame('33.33%', $col2->style?->properties['width']);

        // Child 3
        $col3 = $block->children[2];
        self::assertSame('column', $col3->data->layout);
        self::assertArrayNotHasKey('width', $col3->style?->properties ?? []);

        self::assertSame('center', $col2->style?->properties['alignSelf']);
    }

    public function testMapsTypographyAndBorderStyles(): void
    {
        $mapper = new BasicBlockMapper();
        $block = $mapper->map([
            'blockName' => 'core/paragraph',
            'attrs' => [
                'content' => 'Pill Badge Text',
                'fontFamily' => 'roboto',
                'backgroundColor' => 'accent-5',
                'textColor' => 'contrast',
                'style' => [
                    'typography' => [
                        'fontWeight' => '600',
                        'letterSpacing' => '1px',
                        'textTransform' => 'uppercase',
                    ],
                    'border' => [
                        'radius' => '9999px',
                        'width' => '1px',
                        'color' => '#007cba',
                    ],
                    'spacing' => [
                        'padding' => [
                            'top' => '8px',
                            'bottom' => '8px',
                            'left' => '24px',
                            'right' => '24px',
                        ],
                    ],
                ],
            ],
        ]);

        self::assertSame(BlockType::TEXT, $block->type);
        self::assertNotNull($block->style);
        self::assertSame('var(--wp--preset--font-family--roboto)', $block->style->properties['fontFamily']);
        self::assertSame('var(--wp--preset--color--accent-5)', $block->style->properties['backgroundColor']);
        self::assertSame('var(--wp--preset--color--contrast)', $block->style->properties['color']);
        self::assertSame('600', $block->style->properties['fontWeight']);
        self::assertSame('1px', $block->style->properties['letterSpacing']);
        self::assertSame('uppercase', $block->style->properties['textTransform']);
        self::assertSame('9999px', $block->style->properties['borderRadius']);
        self::assertSame('1px', $block->style->properties['borderWidth']);
        self::assertSame('#007cba', $block->style->properties['borderColor']);
        self::assertSame('solid', $block->style->properties['borderStyle']);
        self::assertSame([
            'top' => '8px',
            'bottom' => '8px',
            'left' => '24px',
            'right' => '24px',
        ], $block->style->properties['padding']);
    }

    public function testMapsRichTextAndButtons(): void
    {
        $mapper = new BasicBlockMapper();

        $paragraph = $mapper->map([
            'blockName' => 'core/paragraph',
            'attrs' => [],
            'innerHTML' => '<p><strong>Important</strong> text</p>',
        ]);
        $button = $mapper->map([
            'blockName' => 'core/button',
            'attrs' => [
                'url' => '/contact',
                'className' => 'wp-element-button',
            ],
            'innerHTML' => '<a class="wp-element-button" href="/contact">Contact us</a>',
        ]);

        self::assertSame('<strong>Important</strong> text', $paragraph->data->html);
        self::assertSame('Contact us', $button->data->text);
        self::assertSame('/contact', $button->data->href);
        self::assertSame('button', $button->data->layout);
        self::assertSame('a', $button->element);

        $visualButton = $mapper->map([
            'blockName' => 'core/button',
            'attrs' => [],
            'innerHTML' => '<a class="wp-element-button">View Projects</a>',
        ]);

        self::assertSame('View Projects', $visualButton->data->text);
        self::assertNull($visualButton->data->href);
        self::assertSame('button', $visualButton->element);

        $outlineButton = $mapper->map([
            'blockName' => 'core/button',
            'attrs' => [
                'className' => 'is-style-outline',
                'style' => [
                    'color' => [
                        'text' => '#395b60',
                    ],
                ],
            ],
            'innerHTML' => '<a class="wp-element-button">View Projects</a>',
        ]);

        self::assertSame('transparent', $outlineButton->style?->properties['backgroundColor']);
        self::assertSame('#395b60', $outlineButton->style?->properties['borderColor']);
        self::assertSame('1px', $outlineButton->style?->properties['borderWidth']);
        self::assertSame('solid', $outlineButton->style?->properties['borderStyle']);

        $image = $mapper->map([
            'blockName' => 'core/image',
            'attrs' => [
                'url' => 'https://example.com/portrait.webp',
                'style' => [
                    'css' => ".hero__portrait-frame {\n    border-radius: 0.85rem;\n    outline: 2px solid #006c7a;\n    outline-offset: 8px;\n    position: relative;\n}",
                ],
            ],
        ]);

        self::assertSame('0.85rem', $image->style?->properties['borderRadius']);
        self::assertSame('2px solid #006c7a', $image->style?->properties['outline']);
        self::assertSame('8px', $image->style?->properties['outlineOffset']);
        self::assertSame('relative', $image->style?->properties['position']);
    }

    public function testKeepsImageDimensionsAsAttributesWithoutCreatingCssWidth(): void
    {
        $mapper = new BasicBlockMapper();
        $image = $mapper->map([
            'blockName' => 'core/image',
            'attrs' => [
                'url' => 'https://example.com/image.webp',
                'alt' => 'Example image',
                'width' => 400,
                'height' => 400,
            ],
        ]);

        self::assertSame(400, $image->data->attributes['width']);
        self::assertSame(400, $image->data->attributes['height']);
        self::assertArrayNotHasKey('width', $image->style?->properties ?? []);
    }
}
