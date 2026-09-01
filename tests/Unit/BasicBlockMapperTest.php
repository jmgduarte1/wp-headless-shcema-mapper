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
}

