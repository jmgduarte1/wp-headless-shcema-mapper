<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Mapping;

use HeadlessAngular\Schema\Domain\Schema\BasicBlockData;
use HeadlessAngular\Schema\Domain\Schema\BlockStyle;
use HeadlessAngular\Schema\Domain\Schema\BlockType;
use HeadlessAngular\Schema\Domain\Schema\PageBlock;
use InvalidArgumentException;

final class BasicBlockMapper implements BlockMapper
{
    private const SUPPORTED_BLOCKS = [
        'core/group',
        'core/columns',
        'core/column',
        'core/cover',
        'core/heading',
        'core/paragraph',
        'core/buttons',
        'core/button',
        'core/image',
        'core/spacer',
        'core/details',
        'core/accordion',
        'core/accordion-item',
        'core/accordion-panel',
    ];

    /**
     * @param array<string, mixed> $block
     */
    public function supports(array $block): bool
    {
        return in_array($block['blockName'] ?? null, self::SUPPORTED_BLOCKS, true);
    }

    /**
     * @param array<string, mixed> $block
     */
    public function map(array $block, int $index = 0): PageBlock
    {
        return match ($block['blockName'] ?? null) {
            'core/group' => $this->mapContainer($block, 'section', 'group', $index),
            'core/columns' => $this->mapContainer($block, 'div', 'columns', $index),
            'core/column' => $this->mapContainer($block, 'div', 'column', $index),
            'core/buttons' => $this->mapContainer($block, 'div', 'buttons', $index),
            'core/cover' => $this->mapCover($block, $index),
            'core/heading' => $this->mapText($block, $this->headingElement($block), $index),
            'core/paragraph' => $this->mapText($block, 'p', $index),
            'core/button' => $this->mapButton($block, $index),
            'core/image' => $this->mapImageBlock($block, $index),
            'core/spacer' => $this->mapSpacer($block, $index),
            'core/details' => $this->mapDetails($block, $index),
            'core/accordion' => $this->mapAccordion($block, $index),
            'core/accordion-item' => $this->mapAccordionItem($block, $index),
            'core/accordion-panel' => $this->mapContainer($block, 'div', 'accordion-panel', $index),
            default => throw new InvalidArgumentException('Unsupported basic Gutenberg block.'),
        };
    }

    /**
     * @param array<string, mixed> $block
     */
    private function mapContainer(array $block, string $element, string $layout, int $index = 0): PageBlock
    {
        $attrs = $this->attrs($block);
        $attributeKeys = ['align', 'className', 'isStackedOnMobile', 'verticalAlignment'];
        $attributes = $this->attributes($attrs, $attributeKeys);

        if ($layout === 'columns') {
            $innerBlocks = $this->listOfBlocks($block['innerBlocks'] ?? []);
            $columnCount = count($innerBlocks);
            if ($columnCount > 0) {
                $attributes['columns'] = $columnCount;
                $attributes['columnCount'] = $columnCount;
            }
        }

        return new PageBlock(
            id: $this->blockId($block, $layout, $index),
            type: BlockType::CONTAINER,
            data: new BasicBlockData(
                layout: $layout,
                attributes: $attributes,
            ),
            style: $this->styleFromAttrs($attrs, $layout),
            element: $element,
            children: $this->mapChildren($block),
        );
    }

    /**
     * @param array<string, mixed> $block
     */
    private function mapCover(array $block, int $index = 0): PageBlock
    {
        $attrs = $this->attrs($block);
        $children = [];
        $src = $this->optionalString($attrs, 'url');

        if ($src !== null) {
            $children[] = new PageBlock(
                id: $this->blockId($block, 'cover-image', $index),
                type: BlockType::IMAGE,
                data: new BasicBlockData(
                    src: $this->safeUrl($src),
                    alt: $this->optionalString($attrs, 'alt') ?? '',
                ),
                style: $this->coverImageStyle($attrs),
                element: 'img',
            );
        }

        return new PageBlock(
            id: $this->blockId($block, 'cover', $index),
            type: BlockType::CONTAINER,
            data: new BasicBlockData(
                layout: 'cover',
                attributes: $this->attributes($attrs, ['align', 'className']),
            ),
            style: $this->coverStyle($attrs),
            element: 'div',
            children: array_merge($children, $this->mapChildren($block)),
        );
    }

    /**
     * @param array<string, mixed> $block
     */
    private function mapText(array $block, string $element, int $index = 0): PageBlock
    {
        $text = $this->blockText($block);

        if ($text === null) {
            throw new InvalidArgumentException('Text block requires content.');
        }

        $attrs = $this->attrs($block);

        return new PageBlock(
            id: $this->blockId($block, $element, $index),
            type: BlockType::TEXT,
            data: new BasicBlockData(
                text: $text,
                attributes: $this->attributes($attrs, ['level', 'fontSize', 'align', 'className']),
            ),
            style: $this->styleFromAttrs($attrs),
            element: $element,
        );
    }

    /**
     * @param array<string, mixed> $block
     */
    private function mapButton(array $block, int $index = 0): PageBlock
    {
        $attrs = $this->attrs($block);
        $text = $this->optionalString($attrs, 'text') ?? $this->blockText($block);
        $href = $this->optionalString($attrs, 'url');

        if ($text === null || $href === null) {
            throw new InvalidArgumentException('Button block requires text and URL.');
        }

        return new PageBlock(
            id: $this->blockId($block, 'button', $index),
            type: BlockType::LINK,
            data: new BasicBlockData(
                text: $text,
                href: $this->safeHref($href),
                target: $this->optionalString($attrs, 'linkTarget'),
                rel: $this->optionalString($attrs, 'rel'),
                layout: 'button',
                attributes: $this->attributes($attrs, ['className']),
            ),
            style: $this->styleFromAttrs($attrs),
            element: 'a',
        );
    }

    /**
     * @param array<string, mixed> $block
     */
    private function mapImageBlock(array $block, int $index = 0): PageBlock
    {
        $attrs = $this->attrs($block);
        $src = $this->optionalString($attrs, 'url') ?? $this->htmlAttribute($block, 'img', 'src');

        if ($src === null) {
            throw new InvalidArgumentException('Image block requires URL.');
        }

        $attributes = $this->attributes($attrs, ['id', 'width', 'height', 'className']);

        foreach (['width', 'height'] as $dimension) {
            if (!isset($attributes[$dimension])) {
                $value = $this->htmlAttribute($block, 'img', $dimension);

                if ($value !== null && ctype_digit($value)) {
                    $attributes[$dimension] = (int) $value;
                }
            }
        }

        return new PageBlock(
            id: $this->blockId($block, 'image', $index),
            type: BlockType::IMAGE,
            data: new BasicBlockData(
                src: $this->safeUrl($src),
                alt: $this->optionalString($attrs, 'alt') ?? $this->htmlAttribute($block, 'img', 'alt') ?? '',
                attributes: $attributes,
            ),
            style: $this->styleFromAttrs($attrs),
            element: 'img',
        );
    }

    /**
     * @param array<string, mixed> $block
     */
    private function mapSpacer(array $block, int $index = 0): PageBlock
    {
        $attrs = $this->attrs($block);

        return new PageBlock(
            id: $this->blockId($block, 'spacer', $index),
            type: BlockType::SPACER,
            data: new BasicBlockData(layout: 'spacer'),
            style: new BlockStyle(properties: [
                'height' => $this->normalizeWordPressStyleValue($this->optionalString($attrs, 'height') ?? '1rem'),
            ]),
            element: 'div',
        );
    }

    /**
     * @param array<string, mixed> $block
     */
    private function mapDetails(array $block, int $index = 0): PageBlock
    {
        $attrs = $this->attrs($block);
        $summary = $this->optionalString($attrs, 'summary') ?? $this->htmlElementText($block, 'summary');

        if ($summary === null) {
            throw new InvalidArgumentException('Details block requires a summary.');
        }

        return new PageBlock(
            id: $this->blockId($block, 'details', $index),
            type: BlockType::DETAILS,
            data: new BasicBlockData(
                summary: $summary,
                open: $this->optionalBool($attrs, 'showContent'),
                layout: 'details',
                attributes: $this->attributes($attrs, ['className']),
            ),
            style: $this->styleFromAttrs($attrs, 'details'),
            element: 'details',
            children: $this->mapChildren($block),
        );
    }

    /**
     * @param array<string, mixed> $block
     */
    private function mapAccordionItem(array $block, int $index = 0): PageBlock
    {
        $attrs = $this->attrs($block);
        $summary = $this->optionalString($attrs, 'title')
            ?? $this->htmlClassText($block, 'wp-block-accordion-heading__toggle-title')
            ?? $this->firstDescendantClassText($block, 'wp-block-accordion-heading__toggle-title')
            ?? $this->htmlElementText($block, 'button');

        if ($summary === null) {
            throw new InvalidArgumentException('Accordion item requires a heading.');
        }

        return new PageBlock(
            id: $this->blockId($block, 'accordion-item', $index),
            type: BlockType::DETAILS,
            data: new BasicBlockData(
                summary: $summary,
                open: $this->optionalBool($attrs, 'openByDefault'),
                layout: 'accordion-item',
                attributes: $this->attributes($attrs, ['className']),
            ),
            style: $this->styleFromAttrs($attrs, 'accordion-item'),
            element: 'details',
            children: $this->mapAccordionPanelChildren($block),
        );
    }

    /**
     * @param array<string, mixed> $block
     */
    private function mapAccordion(array $block, int $index = 0): PageBlock
    {
        foreach ($this->listOfBlocks($block['innerBlocks'] ?? []) as $innerIndex => $innerBlock) {
            if (($innerBlock['blockName'] ?? null) === 'core/accordion-item') {
                return $this->mapContainer($block, 'div', 'accordion', $index);
            }
        }

        $summary = $this->htmlClassText($block, 'wp-block-accordion-heading__toggle-title')
            ?? $this->firstDescendantClassText($block, 'wp-block-accordion-heading__toggle-title');

        if ($summary === null) {
            return $this->mapContainer($block, 'div', 'accordion', $index);
        }

        return new PageBlock(
            id: $this->blockId($block, 'accordion', $index),
            type: BlockType::DETAILS,
            data: new BasicBlockData(
                summary: $summary,
                layout: 'accordion-item',
                attributes: $this->attributes($this->attrs($block), ['className']),
            ),
            style: $this->styleFromAttrs($this->attrs($block)),
            element: 'details',
            children: $this->mapAccordionPanelChildren($block),
        );
    }

    /**
     * @param array<string, mixed> $block
     *
     * @return list<PageBlock>
     */
    private function mapAccordionPanelChildren(array $block): array
    {
        $children = [];

        foreach ($this->listOfBlocks($block['innerBlocks'] ?? []) as $index => $innerBlock) {
            $blockName = $innerBlock['blockName'] ?? null;

            if ($blockName === 'core/accordion-heading') {
                continue;
            }

            if ($blockName === 'core/accordion-panel') {
                $children = array_merge($children, $this->mapChildren($innerBlock));
                continue;
            }

            if (!$this->supports($innerBlock)) {
                $children = array_merge($children, $this->mapChildren($innerBlock));
                continue;
            }

            try {
                $children[] = $this->map($innerBlock, $index);
            } catch (InvalidArgumentException) {
                $children = array_merge($children, $this->mapChildren($innerBlock));
            }
        }

        return $children;
    }

    /**
     * @param array<string, mixed> $block
     *
     * @return list<PageBlock>
     */
    private function mapChildren(array $block): array
    {
        $children = [];

        foreach ($this->listOfBlocks($block['innerBlocks'] ?? []) as $index => $innerBlock) {
            if (!$this->supports($innerBlock)) {
                $children = array_merge($children, $this->mapChildren($innerBlock));
                continue;
            }

            try {
                $children[] = $this->map($innerBlock, $index);
            } catch (InvalidArgumentException) {
                $children = array_merge($children, $this->mapChildren($innerBlock));
            }
        }

        return $children;
    }

    /**
     * @param array<string, mixed> $attrs
     */
    private function coverStyle(array $attrs): ?BlockStyle
    {
        $properties = [];
        $aspectRatio = $this->nestedString($attrs, ['style', 'dimensions', 'aspectRatio']);

        if ($aspectRatio !== null) {
            $properties['aspectRatio'] = $aspectRatio;
        }

        $overlayColor = $this->optionalString($attrs, 'customOverlayColor');

        if ($overlayColor !== null) {
            $properties['overlayColor'] = $overlayColor;
        }

        if (isset($attrs['dimRatio']) && (is_int($attrs['dimRatio']) || is_float($attrs['dimRatio']))) {
            $properties['overlayOpacity'] = $attrs['dimRatio'] / 100;
        }

        return $properties !== [] ? new BlockStyle('cover', $properties) : $this->styleFromAttrs($attrs);
    }

    /**
     * @param array<string, mixed> $attrs
     */
    private function coverImageStyle(array $attrs): ?BlockStyle
    {
        $aspectRatio = $this->nestedString($attrs, ['style', 'dimensions', 'aspectRatio']);

        return $aspectRatio !== null
            ? new BlockStyle('cover-image', ['aspectRatio' => $aspectRatio])
            : null;
    }

    /**
     * @param array<string, mixed> $attrs
     */
    private function styleFromAttrs(array $attrs, ?string $layout = null): ?BlockStyle
    {
        $properties = [];
        $spacing = is_array($attrs['style']['spacing'] ?? null) ? $attrs['style']['spacing'] : [];

        foreach (['margin', 'padding', 'blockGap'] as $property) {
            $targetProp = $property === 'blockGap' ? 'gap' : $property;
            if (isset($spacing[$property])) {
                if (is_array($spacing[$property])) {
                    $value = $this->spacingValue($spacing[$property]);
                    if ($value !== []) {
                        $properties[$targetProp] = $value;
                    }
                } elseif (is_string($spacing[$property]) || is_int($spacing[$property]) || is_float($spacing[$property])) {
                    $properties[$targetProp] = $this->normalizeWordPressStyleValue($spacing[$property]);
                }
            }
        }

        if (isset($attrs['width'])) {
            if (is_string($attrs['width']) && trim($attrs['width']) !== '') {
                $properties['width'] = trim($attrs['width']);
            } elseif (is_int($attrs['width']) || is_float($attrs['width'])) {
                $properties['width'] = $attrs['width'] . '%';
            }
        }

        $dimensionWidth = $this->nestedString($attrs, ['style', 'dimensions', 'width']);

        if ($dimensionWidth !== null) {
            $properties['width'] = $this->normalizeWordPressStyleValue($dimensionWidth);
        }

        $dimensionHeight = $this->nestedString($attrs, ['style', 'dimensions', 'height']);

        if ($dimensionHeight !== null) {
            $properties['height'] = $this->normalizeWordPressStyleValue($dimensionHeight);
        }

        $minHeight = $this->nestedString($attrs, ['style', 'dimensions', 'minHeight']);

        if ($minHeight !== null) {
            $properties['minHeight'] = $this->normalizeWordPressStyleValue($minHeight);
        }

        $background = $this->nestedString($attrs, ['style', 'color', 'gradient']);
        $backgroundColor = $this->nestedString($attrs, ['style', 'color', 'background']);
        $presetGradient = $this->optionalString($attrs, 'gradient');
        $presetBackgroundColor = $this->optionalString($attrs, 'backgroundColor');
        $textColor = $this->nestedString($attrs, ['style', 'color', 'text']);
        $presetTextColor = $this->optionalString($attrs, 'textColor');

        if ($background !== null) {
            $properties['background'] = $this->normalizeWordPressStyleValue($background);
        } elseif ($presetGradient !== null) {
            $properties['background'] = $this->presetStyleValue('gradient', $presetGradient);
        } elseif ($backgroundColor !== null) {
            $properties['backgroundColor'] = $this->normalizeWordPressStyleValue($backgroundColor);
        } elseif ($presetBackgroundColor !== null) {
            $properties['backgroundColor'] = $this->presetStyleValue('color', $presetBackgroundColor);
        }

        if ($textColor !== null) {
            $properties['color'] = $this->normalizeWordPressStyleValue($textColor);
        } elseif ($presetTextColor !== null) {
            $properties['color'] = $this->presetStyleValue('color', $presetTextColor);
        }

        // Typography
        $fontSize = $this->nestedString($attrs, ['style', 'typography', 'fontSize']) ?? $this->optionalString($attrs, 'fontSize');
        $fontFamily = $this->nestedString($attrs, ['style', 'typography', 'fontFamily']);
        $presetFontFamily = $this->optionalString($attrs, 'fontFamily');
        $fontWeight = $this->nestedString($attrs, ['style', 'typography', 'fontWeight']) ?? $this->optionalString($attrs, 'fontWeight');
        $letterSpacing = $this->nestedString($attrs, ['style', 'typography', 'letterSpacing']) ?? $this->optionalString($attrs, 'letterSpacing');
        $textTransform = $this->nestedString($attrs, ['style', 'typography', 'textTransform']) ?? $this->optionalString($attrs, 'textTransform');
        $lineHeight = $this->nestedString($attrs, ['style', 'typography', 'lineHeight']) ?? $this->optionalString($attrs, 'lineHeight');
        $fontStyle = $this->nestedString($attrs, ['style', 'typography', 'fontStyle']) ?? $this->optionalString($attrs, 'fontStyle');
        $textDecoration = $this->nestedString($attrs, ['style', 'typography', 'textDecoration']) ?? $this->optionalString($attrs, 'textDecoration');

        if ($fontSize !== null) {
            $properties['fontSize'] = $this->normalizeWordPressStyleValue($fontSize);
        } elseif ($presetFontSize !== null) {
            $properties['fontSize'] = $this->presetStyleValue('font-size', $presetFontSize);
        }

        if ($fontFamily !== null) {
            $properties['fontFamily'] = $this->normalizeWordPressStyleValue($fontFamily);
        } elseif ($presetFontFamily !== null) {
            $properties['fontFamily'] = $this->presetStyleValue('font-family', $presetFontFamily);
        }

        if ($fontWeight !== null) {
            $properties['fontWeight'] = $this->normalizeWordPressStyleValue($fontWeight);
        }
        if ($letterSpacing !== null) {
            $properties['letterSpacing'] = $this->normalizeWordPressStyleValue($letterSpacing);
        }
        if ($textTransform !== null) {
            $properties['textTransform'] = $this->normalizeWordPressStyleValue($textTransform);
        }
        if ($lineHeight !== null) {
            $properties['lineHeight'] = $this->normalizeWordPressStyleValue($lineHeight);
        }
        if ($fontStyle !== null) {
            $properties['fontStyle'] = $this->normalizeWordPressStyleValue($fontStyle);
        }
        if ($textDecoration !== null) {
            $properties['textDecoration'] = $this->normalizeWordPressStyleValue($textDecoration);
        }

        // Borders
        $radius = $attrs['style']['border']['radius'] ?? null;
        if (is_array($radius)) {
            $properties['borderRadius'] = $this->spacingValue($radius);
        } elseif (is_string($radius) || is_int($radius) || is_float($radius)) {
            $properties['borderRadius'] = $this->normalizeWordPressStyleValue($radius);
        }

        $borderColor = $this->nestedString($attrs, ['style', 'border', 'color']);
        $presetBorderColor = $this->optionalString($attrs, 'borderColor');
        $borderWidth = $this->nestedString($attrs, ['style', 'border', 'width']);
        $borderStyle = $this->nestedString($attrs, ['style', 'border', 'style']);

        if ($borderColor !== null) {
            $properties['borderColor'] = $this->normalizeWordPressStyleValue($borderColor);
        } elseif ($presetBorderColor !== null) {
            $properties['borderColor'] = $this->presetStyleValue('color', $presetBorderColor);
        }

        if ($borderWidth !== null) {
            $properties['borderWidth'] = $this->normalizeWordPressStyleValue($borderWidth);
        }

        if ($borderStyle !== null) {
            $properties['borderStyle'] = $this->normalizeWordPressStyleValue($borderStyle);
        } elseif (($borderColor !== null || $presetBorderColor !== null || $borderWidth !== null) && !isset($properties['borderStyle'])) {
            $properties['borderStyle'] = 'solid';
        }

        $verticalAlignment = $this->optionalString($attrs, 'verticalAlignment');

        if ($layout === 'columns') {
            if ($verticalAlignment !== null) {
                $properties['alignItems'] = $this->flexAlignment($verticalAlignment) ?? $verticalAlignment;
            }
            if (!isset($properties['gap'])) {
                $properties['gap'] = 'var(--wp--preset--spacing--50)';
            }
        }

        if (($layout === 'details' || $layout === 'accordion-item') && !isset($properties['margin'])) {
            $properties['margin'] = ['bottom' => 'var(--wp--preset--spacing--30)'];
        }

        if ($layout === 'column' && $verticalAlignment !== null) {
            $properties['alignSelf'] = $this->flexAlignment($verticalAlignment) ?? $verticalAlignment;
        }

        return $properties !== [] ? new BlockStyle(properties: $properties) : null;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, string|int|float>
     */
    private function spacingValue(array $values): array
    {
        $spacing = [];

        foreach ($values as $key => $value) {
            if (is_string($key) && (is_string($value) || is_int($value) || is_float($value))) {
                $spacing[$key] = $this->normalizeWordPressStyleValue($value);
            }
        }

        return $spacing;
    }

    /**
     * @param array<string, mixed> $attrs
     * @param list<string> $keys
     *
     * @return array<string, string|int|float|bool|array<string, string|int|float|bool>>
     */
    private function attributes(array $attrs, array $keys): array
    {
        $attributes = [];

        foreach ($keys as $key) {
            if (isset($attrs[$key]) && (is_string($attrs[$key]) || is_int($attrs[$key]) || is_float($attrs[$key]) || is_bool($attrs[$key]))) {
                $attributes[$key] = $attrs[$key];
            }
        }

        return $attributes;
    }

    /**
     * @param array<string, mixed> $block
     *
     * @return array<string, mixed>
     */
    private function attrs(array $block): array
    {
        return is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
    }

    /**
     * @param array<string, mixed> $block
     */
    private function headingElement(array $block): string
    {
        $attrs = $this->attrs($block);
        $level = isset($attrs['level']) && is_int($attrs['level']) ? $attrs['level'] : 2;
        $level = max(1, min(6, $level));

        return 'h' . $level;
    }

    /**
     * @param array<string, mixed> $block
     */
    private function blockText(array $block): ?string
    {
        $attrs = $this->attrs($block);
        $content = $this->optionalString($attrs, 'content');

        if ($content !== null) {
            return $content;
        }

        if (isset($block['innerHTML']) && is_string($block['innerHTML'])) {
            $content = trim(strip_tags($block['innerHTML']));

            return $content !== '' ? html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $block
     */
    private function blockId(array $block, string $fallback, int $index = 0): string
    {
        $attrs = $this->attrs($block);
        $anchor = $this->optionalString($attrs, 'anchor');

        if ($anchor !== null) {
            return $anchor;
        }

        $innerBlocks = $block['innerBlocks'] ?? [];
        $source = ($block['blockName'] ?? $fallback) . serialize($attrs) . ($block['innerHTML'] ?? '') . serialize($innerBlocks) . ($index > 0 ? ":{$index}" : '');

        return $fallback . '-' . substr(md5((string) $source), 0, 8);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function optionalString(array $values, string $key): ?string
    {
        if (!isset($values[$key]) || !is_string($values[$key])) {
            return null;
        }

        $value = trim(strip_tags($values[$key]));

        return $value !== '' ? $value : null;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function optionalBool(array $values, string $key): ?bool
    {
        return isset($values[$key]) && is_bool($values[$key]) ? $values[$key] : null;
    }

    private function safeHref(string $href): string
    {
        if (str_starts_with($href, '#') || (str_starts_with($href, '/') && !str_starts_with($href, '//'))) {
            return $href;
        }

        return $this->safeUrl($href);
    }

    private function safeUrl(string $url): string
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('URL must use http or https.');
        }

        return $url;
    }

    /**
     * @param array<string, mixed> $values
     * @param list<string> $path
     */
    private function nestedString(array $values, array $path): ?string
    {
        $current = $values;

        foreach ($path as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return is_string($current) ? $current : null;
    }

    private function normalizeWordPressStyleValue(string|int|float $value): string|int|float
    {
        if (!is_string($value)) {
            return $value;
        }

        if (str_starts_with($value, 'var:preset|')) {
            return 'var(--wp--preset--' . str_replace('|', '--', substr($value, strlen('var:preset|'))) . ')';
        }

        return $value;
    }

    private function presetStyleValue(string $preset, string $slug): string
    {
        return 'var(--wp--preset--' . $preset . '--' . $slug . ')';
    }

    /**
     * @param array<string, mixed> $block
     */
    private function htmlElementText(array $block, string $element): ?string
    {
        if (!isset($block['innerHTML']) || !is_string($block['innerHTML'])) {
            return null;
        }

        if (!preg_match('/<' . preg_quote($element, '/') . '\b[^>]*>(.*?)<\/' . preg_quote($element, '/') . '>/is', $block['innerHTML'], $matches)) {
            return null;
        }

        $text = trim(strip_tags($matches[1]));

        return $text !== '' ? html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;
    }

    /**
     * @param array<string, mixed> $block
     */
    private function htmlAttribute(array $block, string $element, string $attribute): ?string
    {
        if (!isset($block['innerHTML']) || !is_string($block['innerHTML'])) {
            return null;
        }

        $elementPattern = preg_quote($element, '/');
        $attributePattern = preg_quote($attribute, '/');

        if (!preg_match('/<' . $elementPattern . '\b[^>]*\s' . $attributePattern . '\s*=\s*(["\'])(.*?)\1/is', $block['innerHTML'], $matches)) {
            return null;
        }

        $value = trim(html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $value !== '' ? $value : null;
    }

    /**
     * @param array<string, mixed> $block
     */
    private function htmlClassText(array $block, string $className): ?string
    {
        if (!isset($block['innerHTML']) || !is_string($block['innerHTML'])) {
            return null;
        }

        if (!preg_match('/<[^>]*class\s*=\s*(["\'])(?=[^"\']*\b' . preg_quote($className, '/') . '\b)[^"\']*\1[^>]*>(.*?)<\/[^>]+>/is', $block['innerHTML'], $matches)) {
            return null;
        }

        $text = trim(strip_tags($matches[2]));

        return $text !== '' ? html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;
    }

    /**
     * @param array<string, mixed> $block
     */
    private function firstDescendantClassText(array $block, string $className): ?string
    {
        foreach ($this->listOfBlocks($block['innerBlocks'] ?? []) as $innerBlock) {
            $text = $this->htmlClassText($innerBlock, $className);

            if ($text !== null) {
                return $text;
            }

            $nestedText = $this->firstDescendantClassText($innerBlock, $className);

            if ($nestedText !== null) {
                return $nestedText;
            }
        }

        return null;
    }

    private function flexAlignment(?string $value): ?string
    {
        return match ($value) {
            'top', 'start' => 'flex-start',
            'center' => 'center',
            'bottom', 'end' => 'flex-end',
            'stretch' => 'stretch',
            default => null,
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listOfBlocks(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $blocks = [];

        foreach ($value as $block) {
            if (is_array($block)) {
                $blocks[] = $block;
            }
        }

        return $blocks;
    }
}
