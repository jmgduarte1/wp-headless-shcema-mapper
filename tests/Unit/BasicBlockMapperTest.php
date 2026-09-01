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
        self::assertSame('center', $block->style?->properties['alignItems']);
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
}
