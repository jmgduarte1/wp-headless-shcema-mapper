<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Mapping;

use HeadlessAngular\Schema\Domain\Schema\BlockType;
use HeadlessAngular\Schema\Domain\Schema\BlockStyle;
use HeadlessAngular\Schema\Domain\Schema\HeroAction;
use HeadlessAngular\Schema\Domain\Schema\HeroActionVariant;
use HeadlessAngular\Schema\Domain\Schema\HeroAlignment;
use HeadlessAngular\Schema\Domain\Schema\HeroBlockData;
use HeadlessAngular\Schema\Domain\Schema\HeroContentWidth;
use HeadlessAngular\Schema\Domain\Schema\HeroLayout;
use HeadlessAngular\Schema\Domain\Schema\HeroMedia;
use HeadlessAngular\Schema\Domain\Schema\HeroMediaPlacement;
use HeadlessAngular\Schema\Domain\Schema\Link\AnchorLink;
use HeadlessAngular\Schema\Domain\Schema\Link\ExternalLink;
use HeadlessAngular\Schema\Domain\Schema\Link\InternalLink;
use HeadlessAngular\Schema\Domain\Schema\Link\LinkModel;
use HeadlessAngular\Schema\Domain\Schema\MediaAsset;
use HeadlessAngular\Schema\Domain\Schema\PageBlock;
use InvalidArgumentException;

final class HeroBlockMapper implements BlockMapper
{
    /**
     * @param array<string, mixed> $block
     */
    public function supports(array $block): bool
    {
        return in_array($block['blockName'] ?? null, ['headless-angular/hero', 'core/group', 'core/columns', 'core/cover'], true);
    }

    /**
     * @param array<string, mixed> $block
     */
    public function map(array $block): PageBlock
    {
        if (in_array($block['blockName'] ?? null, ['core/group', 'core/columns'], true)) {
            return $this->mapNativeHeroContainer($block);
        }

        if (($block['blockName'] ?? null) === 'core/cover') {
            return $this->mapCoreCover($block);
        }

        $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
        $title = $this->optionalString($attrs, 'title') ?? '';

        if ($title === '') {
            throw new InvalidArgumentException('Hero title is required.');
        }

        $id = $this->optionalString($attrs, 'id') ?? 'hero-' . substr(md5($title), 0, 8);

        return new PageBlock(
            id: $id,
            type: BlockType::HERO,
            data: new HeroBlockData(
                title: $title,
                eyebrow: $this->optionalString($attrs, 'eyebrow'),
                subtitle: $this->optionalString($attrs, 'subtitle'),
                media: $this->mapMedia($attrs['media'] ?? null),
                actions: $this->mapActions($attrs['actions'] ?? []),
                layout: $this->mapLayout($attrs['layout'] ?? null),
            ),
            style: $this->mapStyle($attrs['style'] ?? null),
            element: 'section',
        );
    }

    /**
     * @param array<string, mixed> $block
     */
    private function mapNativeHeroContainer(array $block): PageBlock
    {
        $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
        $innerBlocks = $this->listOfBlocks($block['innerBlocks'] ?? []);
        $coverBlock = $this->firstDescendantBlock($innerBlocks, 'core/cover');
        $headingBlock = $this->firstDescendantBlock($innerBlocks, 'core/heading');
        $title = $headingBlock !== null ? $this->blockText($headingBlock) : null;

        if ($coverBlock === null || $title === null) {
            throw new InvalidArgumentException('Native Hero requires an image cover and heading.');
        }

        $coverAttrs = is_array($coverBlock['attrs'] ?? null) ? $coverBlock['attrs'] : [];
        $textColumn = $this->firstColumnContaining($innerBlocks, 'core/heading');
        $mediaColumn = $this->firstColumnContaining($innerBlocks, 'core/cover');
        $mediaFirst = $this->firstBlockIndex($innerBlocks, 'core/cover') <= $this->firstBlockIndex($innerBlocks, 'core/heading');

        return new PageBlock(
            id: $this->optionalString($attrs, 'anchor') ?? 'hero-' . substr(md5($title), 0, 8),
            type: BlockType::HERO,
            data: new HeroBlockData(
                title: $title,
                eyebrow: null,
                subtitle: $this->combinedParagraphText($innerBlocks),
                media: $this->mapCoreCoverMedia($coverAttrs, $mediaFirst ? HeroMediaPlacement::Start : HeroMediaPlacement::End),
                actions: $this->mapCoreButtons($innerBlocks),
                layout: $this->mapNativeHeroLayout($attrs),
            ),
            style: $this->mapNativeHeroStyle(
                containerAttrs: $attrs,
                coverAttrs: $coverAttrs,
                mediaColumnAttrs: $this->blockAttrs($mediaColumn),
                textColumnAttrs: $this->blockAttrs($textColumn),
            ),
            element: 'section',
        );
    }

    /**
     * @param array<string, mixed> $block
     */
    private function mapCoreCover(array $block): PageBlock
    {
        $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
        $innerBlocks = $this->listOfBlocks($block['innerBlocks'] ?? []);
        $title = $this->firstInnerBlockText($innerBlocks, 'core/heading');

        if ($title === null) {
            throw new InvalidArgumentException('Hero title is required.');
        }

        return new PageBlock(
            id: $this->optionalString($attrs, 'anchor') ?? 'hero-' . substr(md5($title), 0, 8),
            type: BlockType::HERO,
            data: new HeroBlockData(
                title: $title,
                eyebrow: null,
                subtitle: $this->firstInnerBlockText($innerBlocks, 'core/paragraph'),
                media: $this->mapCoreCoverMedia($attrs),
                actions: $this->mapCoreButtons($innerBlocks),
                layout: $this->mapCoreCoverLayout($attrs),
            ),
            style: $this->mapCoreCoverStyle($attrs),
            element: 'section',
        );
    }

    /**
     * @param array<string, mixed> $attrs
     */
    private function mapCoreCoverMedia(
        array $attrs,
        HeroMediaPlacement $placement = HeroMediaPlacement::Background,
    ): ?HeroMedia {
        $src = $this->optionalString($attrs, 'url');

        if ($src === null) {
            return null;
        }

        if (!filter_var($src, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Hero media image src must be a valid URL.');
        }

        $alt = $this->optionalString($attrs, 'alt');

        return new HeroMedia(
            image: new MediaAsset(
                src: $src,
                alt: $alt,
                decorative: $alt === null,
                width: null,
                height: null,
            ),
            placement: $placement,
        );
    }

    /**
     * @param list<array<string, mixed>> $innerBlocks
     *
     * @return list<HeroAction>
     */
    private function mapCoreButtons(array $innerBlocks): array
    {
        $buttons = [];

        foreach ($innerBlocks as $innerBlock) {
            if (!is_array($innerBlock)) {
                continue;
            }

            if (($innerBlock['blockName'] ?? null) === 'core/button') {
                $button = $this->mapCoreButton($innerBlock);

                if ($button instanceof HeroAction) {
                    $buttons[] = $button;
                }
            }

            if (($innerBlock['blockName'] ?? null) === 'core/buttons') {
                $nestedBlocks = $this->listOfBlocks($innerBlock['innerBlocks'] ?? []);
                $buttons = array_merge($buttons, $this->mapCoreButtons($nestedBlocks));
            }
        }

        return $buttons;
    }

    /**
     * @param array<string, mixed> $block
     */
    private function mapCoreButton(array $block): ?HeroAction
    {
        $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
        $label = $this->optionalString($attrs, 'text');
        $url = $this->optionalString($attrs, 'url');

        if ($label === null || $url === null) {
            return null;
        }

        return new HeroAction(
            id: 'action-' . substr(md5($label . $url), 0, 8),
            label: $label,
            link: $this->mapCoreButtonLink($url, $attrs),
            variant: HeroActionVariant::Primary,
        );
    }

    /**
     * @param array<string, mixed> $attrs
     */
    private function mapCoreButtonLink(string $url, array $attrs): LinkModel
    {
        if (str_starts_with($url, '#')) {
            return new AnchorLink($this->safeAnchor(substr($url, 1)));
        }

        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return new InternalLink($this->safeInternalPath($url));
        }

        return new ExternalLink(
            url: $this->safeExternalUrl($url),
            target: $this->safeTarget($this->optionalString($attrs, 'linkTarget')),
            rel: $this->safeRelFromString($this->optionalString($attrs, 'rel')),
        );
    }

    /**
     * @param array<string, mixed> $attrs
     */
    private function mapCoreCoverLayout(array $attrs): ?HeroLayout
    {
        $contentPosition = $this->optionalString($attrs, 'contentPosition');
        $contentAlignment = null;
        $verticalAlignment = null;

        if ($contentPosition !== null) {
            [$vertical, $horizontal] = array_pad(explode(' ', $contentPosition, 2), 2, null);
            $contentAlignment = $this->alignmentFromCorePosition($horizontal);
            $verticalAlignment = $this->alignmentFromCorePosition($vertical);
        }

        $align = $this->optionalString($attrs, 'align');
        $contentWidth = in_array($align, ['wide', 'full'], true)
            ? HeroContentWidth::Wide
            : null;

        $layout = new HeroLayout(
            contentAlignment: $contentAlignment,
            verticalAlignment: $verticalAlignment,
            contentWidth: $contentWidth,
        );

        return $layout->contentAlignment !== null || $layout->verticalAlignment !== null || $layout->contentWidth !== null
            ? $layout
            : null;
    }

    /**
     * @param array<string, mixed> $attrs
     */
    private function mapNativeHeroLayout(array $attrs): HeroLayout
    {
        $align = $this->optionalString($attrs, 'align');

        return new HeroLayout(
            contentAlignment: HeroAlignment::Start,
            verticalAlignment: HeroAlignment::Center,
            contentWidth: $align === 'full' ? HeroContentWidth::Full : HeroContentWidth::Wide,
        );
    }

    private function alignmentFromCorePosition(?string $position): ?HeroAlignment
    {
        return match ($position) {
            'left', 'top' => HeroAlignment::Start,
            'center' => HeroAlignment::Center,
            'right', 'bottom' => HeroAlignment::End,
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $attrs
     */
    private function mapCoreCoverStyle(array $attrs): ?BlockStyle
    {
        $properties = [];

        if (isset($attrs['minHeight']) && (is_int($attrs['minHeight']) || is_float($attrs['minHeight']))) {
            $unit = $this->optionalString($attrs, 'minHeightUnit') ?? 'px';
            $properties['minHeight'] = $attrs['minHeight'] . $unit;
        }

        return $properties !== []
            ? new BlockStyle('primary', $properties)
            : null;
    }

    /**
     * @param array<string, mixed> $containerAttrs
     * @param array<string, mixed> $coverAttrs
     * @param array<string, mixed> $mediaColumnAttrs
     * @param array<string, mixed> $textColumnAttrs
     */
    private function mapNativeHeroStyle(
        array $containerAttrs,
        array $coverAttrs,
        array $mediaColumnAttrs,
        array $textColumnAttrs,
    ): BlockStyle {
        $properties = [];

        $margin = $this->spacingValue($containerAttrs, ['style', 'spacing', 'margin']);
        if ($margin !== []) {
            $properties['margin'] = $margin;
        }

        $padding = $this->spacingValue($textColumnAttrs, ['style', 'spacing', 'padding']);
        if ($padding !== []) {
            $properties['contentPadding'] = $padding;
        }

        $gap = $this->spacingValue($containerAttrs, ['style', 'spacing', 'blockGap']);
        if ($gap !== []) {
            $properties['gap'] = $gap;
        }

        $mediaWidth = $this->optionalString($mediaColumnAttrs, 'width');
        if ($mediaWidth !== null) {
            $properties['mediaWidth'] = $mediaWidth;
        }

        $aspectRatio = $this->nestedString($coverAttrs, ['style', 'dimensions', 'aspectRatio']);
        if ($aspectRatio !== null) {
            $properties['mediaAspectRatio'] = $aspectRatio;
        }

        $overlayColor = $this->optionalString($coverAttrs, 'customOverlayColor');
        if ($overlayColor !== null) {
            $properties['overlayColor'] = $overlayColor;
        }

        if (isset($coverAttrs['dimRatio']) && (is_int($coverAttrs['dimRatio']) || is_float($coverAttrs['dimRatio']))) {
            $properties['overlayOpacity'] = $coverAttrs['dimRatio'] / 100;
        }

        return new BlockStyle('media-split', $properties);
    }

    /**
     * @param list<array<string, mixed>> $innerBlocks
     */
    private function firstInnerBlockText(array $innerBlocks, string $blockName): ?string
    {
        foreach ($innerBlocks as $innerBlock) {
            if (!is_array($innerBlock)) {
                continue;
            }

            if (($innerBlock['blockName'] ?? null) === $blockName) {
                $content = $this->blockText($innerBlock);

                if ($content !== null) {
                    return $content;
                }
            }

            $nestedBlocks = $this->listOfBlocks($innerBlock['innerBlocks'] ?? []);
            $nestedContent = $this->firstInnerBlockText($nestedBlocks, $blockName);

            if ($nestedContent !== null) {
                return $nestedContent;
            }
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $innerBlocks
     */
    private function combinedParagraphText(array $innerBlocks): ?string
    {
        $paragraphs = $this->descendantTexts($innerBlocks, 'core/paragraph');

        return $paragraphs !== [] ? implode("\n\n", $paragraphs) : null;
    }

    /**
     * @param list<array<string, mixed>> $innerBlocks
     *
     * @return list<string>
     */
    private function descendantTexts(array $innerBlocks, string $blockName): array
    {
        $texts = [];

        foreach ($innerBlocks as $innerBlock) {
            if (($innerBlock['blockName'] ?? null) === $blockName) {
                $text = $this->blockText($innerBlock);

                if ($text !== null) {
                    $texts[] = $text;
                }
            }

            $texts = array_merge($texts, $this->descendantTexts(
                $this->listOfBlocks($innerBlock['innerBlocks'] ?? []),
                $blockName,
            ));
        }

        return $texts;
    }

    /**
     * @param list<array<string, mixed>> $innerBlocks
     *
     * @return array<string, mixed>|null
     */
    private function firstDescendantBlock(array $innerBlocks, string $blockName): ?array
    {
        foreach ($innerBlocks as $innerBlock) {
            if (($innerBlock['blockName'] ?? null) === $blockName) {
                return $innerBlock;
            }

            $nestedBlock = $this->firstDescendantBlock($this->listOfBlocks($innerBlock['innerBlocks'] ?? []), $blockName);

            if ($nestedBlock !== null) {
                return $nestedBlock;
            }
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $innerBlocks
     *
     * @return array<string, mixed>|null
     */
    private function firstColumnContaining(array $innerBlocks, string $blockName): ?array
    {
        foreach ($innerBlocks as $innerBlock) {
            if (
                ($innerBlock['blockName'] ?? null) === 'core/column'
                && $this->firstDescendantBlock($this->listOfBlocks($innerBlock['innerBlocks'] ?? []), $blockName) !== null
            ) {
                return $innerBlock;
            }

            $nestedColumn = $this->firstColumnContaining($this->listOfBlocks($innerBlock['innerBlocks'] ?? []), $blockName);

            if ($nestedColumn !== null) {
                return $nestedColumn;
            }
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $innerBlocks
     */
    private function firstBlockIndex(array $innerBlocks, string $blockName): int
    {
        $index = 0;

        foreach ($this->flattenBlocks($innerBlocks) as $block) {
            if (($block['blockName'] ?? null) === $blockName) {
                return $index;
            }

            $index++;
        }

        return PHP_INT_MAX;
    }

    /**
     * @param list<array<string, mixed>> $innerBlocks
     *
     * @return list<array<string, mixed>>
     */
    private function flattenBlocks(array $innerBlocks): array
    {
        $blocks = [];

        foreach ($innerBlocks as $innerBlock) {
            $blocks[] = $innerBlock;
            $blocks = array_merge($blocks, $this->flattenBlocks($this->listOfBlocks($innerBlock['innerBlocks'] ?? [])));
        }

        return $blocks;
    }

    /**
     * @param array<string, mixed>|null $block
     *
     * @return array<string, mixed>
     */
    private function blockAttrs(?array $block): array
    {
        return is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
    }

    /**
     * @param array<string, mixed> $block
     */
    private function blockText(array $block): ?string
    {
        $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
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
     * @param array<string, mixed> $values
     * @param list<string> $path
     *
     * @return array<string, string|int|float>
     */
    private function spacingValue(array $values, array $path): array
    {
        $value = $this->nestedValue($values, $path);

        if (!is_array($value)) {
            return [];
        }

        $spacing = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && (is_string($item) || is_int($item) || is_float($item))) {
                $spacing[$key] = $this->normalizeWordPressStyleValue($item);
            }
        }

        return $spacing;
    }

    /**
     * @param array<string, mixed> $values
     * @param list<string> $path
     */
    private function nestedString(array $values, array $path): ?string
    {
        $value = $this->nestedValue($values, $path);

        return is_string($value) ? (string) $this->normalizeWordPressStyleValue($value) : null;
    }

    /**
     * @param array<string, mixed> $values
     * @param list<string> $path
     */
    private function nestedValue(array $values, array $path): mixed
    {
        $current = $values;

        foreach ($path as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
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

    private function mapMedia(mixed $value): ?HeroMedia
    {
        if ($value === null || $value === []) {
            return null;
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException('Hero media must be an object.');
        }

        $placementValue = $this->stringValue($value, 'placement');
        $placement = HeroMediaPlacement::tryFrom($placementValue);

        if (!$placement instanceof HeroMediaPlacement) {
            throw new InvalidArgumentException('Hero media placement is invalid.');
        }

        $image = $value['image'] ?? null;

        if (!is_array($image)) {
            throw new InvalidArgumentException('Hero media image is required.');
        }

        $src = $this->stringValue($image, 'src');

        if (!filter_var($src, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Hero media image src must be a valid URL.');
        }

        $alt = $this->optionalString($image, 'alt');
        $decorative = isset($image['decorative']) && $image['decorative'] === true;

        if (!$decorative && $alt === null) {
            throw new InvalidArgumentException('Hero media image requires alt text or decorative=true.');
        }

        return new HeroMedia(
            image: new MediaAsset(
                src: $src,
                alt: $alt,
                decorative: $decorative,
                width: $this->optionalPositiveInt($image, 'width'),
                height: $this->optionalPositiveInt($image, 'height'),
            ),
            placement: $placement,
        );
    }

    /**
     * @return list<HeroAction>
     */
    private function mapActions(mixed $value): array
    {
        if ($value === null || $value === []) {
            return [];
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException('Hero actions must be an array.');
        }

        $actions = [];

        foreach ($value as $action) {
            if (!is_array($action)) {
                throw new InvalidArgumentException('Hero action must be an object.');
            }

            $variant = null;
            $variantValue = $this->optionalString($action, 'variant');

            if ($variantValue !== null) {
                $variant = HeroActionVariant::tryFrom($variantValue);

                if (!$variant instanceof HeroActionVariant) {
                    throw new InvalidArgumentException('Hero action variant is invalid.');
                }
            }

            $actions[] = new HeroAction(
                id: $this->stringValue($action, 'id'),
                label: $this->stringValue($action, 'label'),
                link: $this->mapLink($action['link'] ?? null),
                variant: $variant,
                accessibleLabel: $this->optionalString($action, 'accessibleLabel'),
            );
        }

        return $actions;
    }

    private function mapLink(mixed $value): LinkModel
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException('Hero action link is required.');
        }

        return match ($this->stringValue($value, 'type')) {
            'internal' => new InternalLink($this->safeInternalPath($this->stringValue($value, 'path'))),
            'external' => new ExternalLink(
                url: $this->safeExternalUrl($this->stringValue($value, 'url')),
                target: $this->safeTarget($this->optionalString($value, 'target')),
                rel: $this->safeRel($value['rel'] ?? []),
            ),
            'anchor' => new AnchorLink($this->safeAnchor($this->stringValue($value, 'anchor'))),
            default => throw new InvalidArgumentException('Hero action link type is invalid.'),
        };
    }

    private function mapLayout(mixed $value): ?HeroLayout
    {
        if ($value === null || $value === []) {
            return null;
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException('Hero layout must be an object.');
        }

        $layout = new HeroLayout(
            contentAlignment: $this->optionalEnum($value, 'contentAlignment', HeroAlignment::class),
            verticalAlignment: $this->optionalEnum($value, 'verticalAlignment', HeroAlignment::class),
            contentWidth: $this->optionalEnum($value, 'contentWidth', HeroContentWidth::class),
        );

        return $layout->contentAlignment !== null || $layout->verticalAlignment !== null || $layout->contentWidth !== null
            ? $layout
            : null;
    }

    private function mapStyle(mixed $value): ?BlockStyle
    {
        if ($value === null || $value === []) {
            return null;
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException('Hero style must be an object.');
        }

        $properties = [];
        $rawProperties = $value['properties'] ?? [];
        $allowedProperties = [
            'minHeight',
            'padding',
            'backgroundColor',
            'color',
            'fontFamily',
            'fontSize',
            'letterSpacing',
            'margin',
            'contentPadding',
            'gap',
            'mediaWidth',
            'mediaAspectRatio',
            'overlayColor',
            'overlayOpacity',
        ];

        if (!is_array($rawProperties)) {
            throw new InvalidArgumentException('Hero style properties must be an object.');
        }

        foreach ($rawProperties as $property => $propertyValue) {
            if (!is_string($property) || !in_array($property, $allowedProperties, true)) {
                throw new InvalidArgumentException('Hero style property is not allowed.');
            }

            $properties[$property] = $this->safeStyleValue($propertyValue);
        }

        $variant = $this->optionalString($value, 'variant');

        return $variant !== null || $properties !== []
            ? new BlockStyle($variant, $properties)
            : null;
    }

    /**
     * @template T of \BackedEnum
     *
     * @param array<string, mixed> $values
     * @param class-string<T> $enumClass
     *
     * @return T|null
     */
    private function optionalEnum(array $values, string $key, string $enumClass): ?\BackedEnum
    {
        $value = $this->optionalString($values, $key);

        if ($value === null) {
            return null;
        }

        $enum = $enumClass::tryFrom($value);

        if (!$enum instanceof $enumClass) {
            throw new InvalidArgumentException('Hero enum value is invalid.');
        }

        return $enum;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function stringValue(array $values, string $key): string
    {
        $value = $this->optionalString($values, $key);

        if ($value === null) {
            throw new InvalidArgumentException('Required hero value is missing.');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function optionalPositiveInt(array $values, string $key): ?int
    {
        if (!isset($values[$key])) {
            return null;
        }

        if (!is_int($values[$key]) || $values[$key] <= 0) {
            throw new InvalidArgumentException('Hero numeric value must be a positive integer.');
        }

        return $values[$key];
    }

    private function safeInternalPath(string $path): string
    {
        if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
            throw new InvalidArgumentException('Internal links must start with a single slash.');
        }

        return $path;
    }

    private function safeExternalUrl(string $url): string
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('External links must use http or https URLs.');
        }

        return $url;
    }

    private function safeTarget(?string $target): ?string
    {
        if ($target === null || in_array($target, ['_self', '_blank'], true)) {
            return $target;
        }

        throw new InvalidArgumentException('External link target is invalid.');
    }

    /**
     * @return list<string>
     */
    private function safeRel(mixed $value): array
    {
        if ($value === null || $value === []) {
            return [];
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException('External link rel must be an array.');
        }

        $rel = [];

        foreach ($value as $item) {
            if (!is_string($item) || !preg_match('/^[a-z0-9_-]+$/i', $item)) {
                throw new InvalidArgumentException('External link rel value is invalid.');
            }

            $rel[] = strtolower($item);
        }

        return array_values(array_unique($rel));
    }

    /**
     * @return list<string>
     */
    private function safeRelFromString(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        return $this->safeRel(preg_split('/\s+/', $value) ?: []);
    }

    private function safeAnchor(string $anchor): string
    {
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $anchor)) {
            throw new InvalidArgumentException('Anchor link value is invalid.');
        }

        return $anchor;
    }

    /**
     * @return string|int|float|array<string, string|int|float>
     */
    private function safeStyleValue(mixed $value): string|int|float|array
    {
        if (is_string($value)) {
            $trimmed = trim($value);

            if (
                $trimmed === ''
                || preg_match('/(?:expression|javascript:|url\s*\(|<|>|;)/i', $trimmed)
            ) {
                throw new InvalidArgumentException('Hero style value is unsafe.');
            }

            return $trimmed;
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_array($value)) {
            $allowedBreakpoints = ['mobile', 'tablet', 'desktop'];
            $safeValues = [];

            foreach ($value as $breakpoint => $breakpointValue) {
                if (!is_string($breakpoint) || !in_array($breakpoint, $allowedBreakpoints, true)) {
                    throw new InvalidArgumentException('Hero responsive style breakpoint is invalid.');
                }

                $safeValues[$breakpoint] = $this->safeScalarStyleValue($breakpointValue);
            }

            return $safeValues;
        }

        throw new InvalidArgumentException('Hero style value is invalid.');
    }

    private function safeScalarStyleValue(mixed $value): string|int|float
    {
        if (is_string($value)) {
            $trimmed = trim($value);

            if (
                $trimmed === ''
                || preg_match('/(?:expression|javascript:|url\s*\(|<|>|;)/i', $trimmed)
            ) {
                throw new InvalidArgumentException('Hero style value is unsafe.');
            }

            return $trimmed;
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        throw new InvalidArgumentException('Hero responsive style values must be scalar.');
    }
}
