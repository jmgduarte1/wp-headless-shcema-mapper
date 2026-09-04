<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Mapping;

use HeadlessAngular\Schema\Domain\Schema\BasicBlockData;
use HeadlessAngular\Schema\Domain\Schema\BlockStyle;
use HeadlessAngular\Schema\Domain\Schema\BlockType;
use HeadlessAngular\Schema\Domain\Schema\FeaturedCardsData;
use HeadlessAngular\Schema\Domain\Schema\FormData;
use HeadlessAngular\Schema\Domain\Schema\InteractiveBlockData;
use HeadlessAngular\Schema\Domain\Schema\TabsData;
use HeadlessAngular\Schema\Domain\Schema\TimelineData;
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
        'core/html',
        'core/quote',
        'core/buttons',
        'core/button',
        'core/image',
        'core/spacer',
        'core/separator',
        'core/list',
        'core/list-item',
        'core/details',
        'core/accordion',
        'core/accordion-item',
        'core/accordion-panel',
        'headless-angular/featured-cards',
        'headless-angular/timeline',
        'headless-angular/tooltip',
        'core/gallery',
        'core/tabs',
        'wpforms/form-selector',
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
            'core/group' => $this->mapContainer($block, 'section', $this->groupLayout($block), $index),
            'core/columns' => $this->mapContainer($block, 'div', 'columns', $index),
            'core/column' => $this->mapContainer($block, 'div', 'column', $index),
            'core/buttons' => $this->mapContainer($block, 'div', 'buttons', $index),
            'core/cover' => $this->mapCover($block, $index),
            'core/heading' => $this->mapText($block, $this->headingElement($block), $index),
            'core/paragraph' => $this->mapText($block, 'p', $index),
            'core/html' => $this->mapCustomHtml($block, $index),
            'core/quote' => $this->mapContainer($block, 'blockquote', 'quote', $index),
            'core/button' => $this->mapButton($block, $index),
            'core/image' => $this->mapImageBlock($block, $index),
            'core/spacer' => $this->mapSpacer($block, $index),
            'core/separator' => $this->mapSeparator($block, $index),
            'core/list' => $this->mapContainer($block, 'ul', 'list', $index),
            'core/list-item' => $this->mapListItem($block, $index),
            'core/details' => $this->mapDetails($block, $index),
            'core/accordion' => $this->mapAccordion($block, $index),
            'core/accordion-item' => $this->mapAccordionItem($block, $index),
            'core/accordion-panel' => $this->mapContainer($block, 'div', 'accordion-panel', $index),
            'headless-angular/featured-cards' => $this->mapFeaturedCards($block, $index),
            'headless-angular/timeline' => $this->mapTimeline($block, $index),
            'headless-angular/tooltip' => $this->mapTooltip($block, $index),
            'core/gallery' => $this->mapGallery($block, $index),
            'core/tabs' => $this->mapTabs($block, $index),
            'wpforms/form-selector' => $this->mapForm($block, $index),
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
        $style = $this->styleFromAttrs($attrs, $layout);

        if ($layout === 'columns') {
            $innerBlocks = $this->listOfBlocks($block['innerBlocks'] ?? []);
            $columnCount = count($innerBlocks);
            if ($columnCount > 0) {
                $attributes['columns'] = $columnCount;
                $attributes['columnCount'] = $columnCount;
            }
        }

        if ($layout === 'grid') {
            $layoutAttrs = is_array($attrs['layout'] ?? null) ? $attrs['layout'] : [];
            $columnCount = isset($layoutAttrs['columnCount']) && is_int($layoutAttrs['columnCount'])
                ? $layoutAttrs['columnCount']
                : count($this->listOfBlocks($block['innerBlocks'] ?? []));
            $mobileColumnCount = $this->nestedInt($attrs, ['style', '@mobile', 'layout', 'columnCount']);
            $tabletColumnCount = $this->nestedInt($attrs, ['style', '@tablet', 'layout', 'columnCount']);

            if ($columnCount > 0) {
                $properties = $style !== null ? $style->properties : [];
                $desktopColumns = sprintf('repeat(%d, minmax(0, 1fr))', $columnCount);
                $mobileColumns = sprintf('repeat(%d, minmax(0, 1fr))', $mobileColumnCount ?? $columnCount);
                $tabletColumns = sprintf('repeat(%d, minmax(0, 1fr))', $tabletColumnCount ?? $mobileColumnCount ?? $columnCount);
                $properties['gridTemplateColumns'] = $mobileColumnCount !== null || $tabletColumnCount !== null
                    ? ['mobile' => $mobileColumns, 'tablet' => $tabletColumns, 'desktop' => $desktopColumns]
                    : $desktopColumns;
                $style = new BlockStyle($style !== null ? $style->variant : null, $properties);
            }
        }

        return new PageBlock(
            id: $this->blockId($block, $layout, $index),
            type: BlockType::CONTAINER,
            data: new BasicBlockData(
                layout: $layout,
                customCss: $this->customCss($attrs['style']['css'] ?? null),
                attributes: $attributes,
            ),
            style: $style,
            element: $element,
            children: $this->mapChildren($block),
        );
    }

    /** @param array<string, mixed> $block */
    private function groupLayout(array $block): string
    {
        $attrs = $this->attrs($block);
        $layout = $attrs['layout'] ?? null;

        return is_array($layout) && ($layout['type'] ?? null) === 'grid' ? 'grid' : 'group';
    }

    /** @param array<string, mixed> $block */
    private function mapSeparator(array $block, int $index = 0): PageBlock
    {
        $attrs = $this->attrs($block);
        $style = $this->styleFromAttrs($attrs, 'separator');
        $properties = $style === null ? [] : $style->properties;
        $separatorColorValue = $properties['color'] ?? $properties['backgroundColor'] ?? 'currentColor';
        $separatorColor = is_string($separatorColorValue) || is_int($separatorColorValue) || is_float($separatorColorValue)
            ? (string) $separatorColorValue
            : 'currentColor';
        $properties['color'] ??= $separatorColor;
        $properties['borderTop'] ??= '0px solid ' . $separatorColor;
        $properties['borderBottom'] ??= '1px solid ' . $separatorColor;

        return new PageBlock(
            id: $this->blockId($block, 'separator', $index),
            type: BlockType::SEPARATOR,
            data: new BasicBlockData(layout: 'separator'),
            style: new BlockStyle(properties: $properties),
            element: 'hr',
        );
    }

    /**
     * Gutenberg list items keep their text in innerHTML rather than innerBlocks.
     * Preserve it in the normalized block so the Angular list is not reduced to empty bullets.
     *
     * @param array<string, mixed> $block
     */
    private function mapListItem(array $block, int $index = 0): PageBlock
    {
        $attrs = $this->attrs($block);
        $text = $this->blockText($block);

        if ($text === null) {
            throw new InvalidArgumentException('List item requires content.');
        }

        return new PageBlock(
            id: $this->blockId($block, 'list-item', $index),
            type: BlockType::CONTAINER,
            data: new BasicBlockData(
                text: $text,
                html: $this->blockHtml($block, 'li'),
                layout: 'list-item',
                customCss: $this->customCss($attrs['style']['css'] ?? null),
            ),
            style: $this->styleFromAttrs($attrs, 'list-item'),
            element: 'li',
        );
    }

    /**
     * @param array<string, mixed> $block
     */
    private function mapFeaturedCards(array $block, int $index = 0): PageBlock
    {
        $attrs = $this->attrs($block);
        $rawCards = is_array($attrs['cards'] ?? null) ? $attrs['cards'] : [];
        $cards = [];

        foreach ($rawCards as $cardIndex => $rawCard) {
            if (!is_array($rawCard)) {
                continue;
            }

            $title = $this->optionalString($rawCard, 'title');
            $text = $this->optionalString($rawCard, 'text');
            if ($title === null || $text === null) {
                continue;
            }

            $tags = [];
            if (is_array($rawCard['tags'] ?? null)) {
                foreach ($rawCard['tags'] as $tag) {
                    if (is_string($tag) && trim($tag) !== '') {
                        $tags[] = trim(strip_tags($tag));
                    }
                }
            }

            $card = [
                'id' => $this->optionalString($rawCard, 'id') ?? 'card-' . $cardIndex,
                'title' => $title,
                'tags' => $tags,
                'text' => $text,
            ];

            $icon = $this->optionalString($rawCard, 'icon');
            if ($icon !== null && preg_match('/^[A-Za-z0-9_-]+$/', $icon)) {
                $card['icon'] = $icon;
            }

            if (is_array($rawCard['image'] ?? null)) {
                $image = $rawCard['image'];
                $url = $this->optionalString($image, 'url');
                if ($url !== null) {
                    $card['image'] = [
                        'src' => $this->safeUrl($url),
                        'alt' => $this->optionalString($image, 'alt') ?? '',
                    ];
                }
            }

            if (isset($rawCard['style']) && is_array($rawCard['style'])) {
                $style = $this->styleFromAttrs(['style' => $rawCard['style']]);
                if ($style !== null) {
                    $card['style'] = $style->properties;
                }
            }

            $cards[] = $card;
        }

        if ($cards === []) {
            throw new InvalidArgumentException('Featured cards requires at least one valid card.');
        }

        return new PageBlock(
            id: $this->blockId($block, 'featured-cards', $index),
            type: BlockType::FEATURED_CARDS,
            data: new FeaturedCardsData($cards),
            style: $this->styleFromAttrs($attrs),
            element: 'section',
        );
    }

    /** @param array<string, mixed> $block */
    private function mapTimeline(array $block, int $index = 0): PageBlock
    {
        $attrs = $this->attrs($block); $periods = [];
        foreach (is_array($attrs['periods'] ?? null) ? $attrs['periods'] : [] as $periodIndex => $raw) {
            if (!is_array($raw)) continue;
            $start=$this->optionalString($raw,'start'); $end=$this->optionalString($raw,'end'); $title=$this->optionalString($raw,'title'); $text=$this->optionalString($raw,'text');
            if ($start===null || $end===null || $title===null || $text===null) continue;
            $tags=[]; foreach (is_array($raw['tags']??null)?$raw['tags']:[] as $tag) if(is_string($tag)&&trim($tag)!=='') $tags[]=trim(strip_tags($tag));
            $periods[]=['id'=>$this->optionalString($raw,'id')??'period-'.$periodIndex,'start'=>$start,'end'=>$end,'title'=>$title,'text'=>$text,'tags'=>$tags];
            if(is_array($raw['style']??null)){ $style=$this->styleFromAttrs(['style'=>$raw['style']]); if($style!==null) $periods[array_key_last($periods)]['style']=$style->properties; }
        }
        if($periods===[]) throw new InvalidArgumentException('Timeline requires at least one valid period.');
        $position=$this->optionalString($attrs,'linkPosition')??'end'; if(!in_array($position,['start','center','end'],true)) $position='end';
        return new PageBlock(id:$this->blockId($block,'timeline',$index),type:BlockType::TIMELINE,data:new TimelineData($periods,$this->optionalString($attrs,'eyebrow') ?? 'Experience',$this->optionalString($attrs,'title') ?? 'Recent leadership and delivery',$this->optionalString($attrs,'linkLabel'),$this->optionalString($attrs,'linkUrl'),$position),style:$this->styleFromAttrs($attrs),element:'section');
    }

    /** @param array<string, mixed> $block */
    private function mapTooltip(array $block, int $index = 0): PageBlock
    {
        $attrs = $this->attrs($block);
        $label = $this->optionalString($attrs, 'label');
        $content = $this->optionalString($attrs, 'content');
        if ($label === null || $content === null) {
            throw new InvalidArgumentException('Tooltip requires label and content.');
        }
        return new PageBlock(
            id: $this->blockId($block, 'tooltip', $index),
            type: BlockType::TOOLTIP,
            data: new InteractiveBlockData(['label' => $label, 'content' => $content]),
            style: $this->styleFromAttrs($attrs),
            element: 'span',
        );
    }

    /** @param array<string, mixed> $block */
    private function mapForm(array $block, int $index = 0): PageBlock
    {
        $attrs = $this->attrs($block);
        $formId = absint((string) ($attrs['formId'] ?? $attrs['form_id'] ?? '0'));

        if ($formId < 1 || !function_exists('wpforms')) {
            throw new InvalidArgumentException('WPForms block requires an active form.');
        }

        $formHandler = wpforms()->obj('form');
        $formData = $formHandler ? $formHandler->get($formId, ['content_only' => true]) : false;

        if (!is_array($formData) || !is_array($formData['fields'] ?? null)) {
            throw new InvalidArgumentException('WPForms form could not be loaded.');
        }

        $allowedTypes = ['text', 'textarea', 'email', 'number', 'select', 'radio', 'checkbox', 'name'];
        $fields = [];

        foreach ($formData['fields'] as $field) {
            if (!is_array($field) || !isset($field['id'], $field['type']) || !in_array($field['type'], $allowedTypes, true)) {
                continue;
            }

            $fieldId = absint((string) $field['id']);
            if ($fieldId < 1) {
                continue;
            }

            $normalized = [
                'name' => (string) $fieldId,
                'type' => $field['type'] === 'name' ? 'name' : $field['type'],
                'label' => $this->sanitizeFormText($field['label'] ?? 'Field'),
                'required' => !empty($field['required']),
            ];

            foreach (['placeholder', 'description'] as $source) {
                $value = $this->sanitizeFormText($field[$source] ?? '');
                if ($value !== '') {
                    $normalized[$source === 'description' ? 'hint' : $source] = $value;
                }
            }

            if (in_array($field['type'], ['select', 'radio', 'checkbox'], true) && is_array($field['choices'] ?? null)) {
                $options = [];
                foreach ($field['choices'] as $choice) {
                    if (!is_array($choice)) {
                        continue;
                    }
                    $label = $this->sanitizeFormText($choice['label'] ?? '');
                    $value = $this->sanitizeFormText($choice['value'] ?? $label);
                    if ($label !== '' && $value !== '') {
                        $options[] = ['label' => $label, 'value' => $value];
                    }
                }
                if ($options !== []) {
                    $normalized['options'] = $options;
                }
            }

            $fields[] = $normalized;
        }

        if ($fields === []) {
            throw new InvalidArgumentException('WPForms form has no supported fields.');
        }

        $settings = is_array($formData['settings'] ?? null) ? $formData['settings'] : [];
        $submitLabel = $this->sanitizeFormText($settings['submit_text'] ?? 'Submit') ?: 'Submit';
        $successMessage = $this->formConfirmationMessage($settings) ?: 'Your form has been submitted successfully.';

        return new PageBlock(
            id: $this->blockId($block, 'form', $index),
            type: BlockType::FORM,
            data: new FormData(
                formId: (string) $formId,
                fields: $fields,
                submit: [
                    'label' => $submitLabel,
                    // Use the nonce action expected by WPForms' native processor.
                    'nonce' => function_exists('wp_create_nonce') ? wp_create_nonce('wpforms::form_' . $formId) : '',
                ],
                successMessage: $successMessage,
                failureMessage: 'The form could not be submitted. Please review the fields and try again.',
            ),
            style: $this->styleFromAttrs($attrs),
            element: 'form',
        );
    }

    private function sanitizeFormText(mixed $value): string
    {
        return trim(wp_strip_all_tags((string) $value));
    }

    /** @param array<string, mixed> $settings */
    private function formConfirmationMessage(array $settings): string
    {
        $message = $settings['confirmation_message'] ?? '';
        $confirmations = is_array($settings['confirmations'] ?? null) ? $settings['confirmations'] : [];
        foreach ($confirmations as $confirmation) {
            if (is_array($confirmation) && ($confirmation['type'] ?? '') === 'message' && isset($confirmation['message'])) {
                $message = $confirmation['message'];
            }
        }
        return $this->sanitizeFormText($message);
    }

    /** @param array<string, mixed> $block */
    private function mapTabs(array $block, int $index = 0): PageBlock
    {
        $attrs = $this->attrs($block);
        $panels = [];
        foreach ($this->listOfBlocks($block['innerBlocks'] ?? []) as $container) {
            if (($container['blockName'] ?? null) !== 'core/tab-panels') {
                continue;
            }

            foreach ($this->listOfBlocks($container['innerBlocks'] ?? []) as $panelIndex => $panel) {
                if (($panel['blockName'] ?? null) !== 'core/tab-panel') {
                    continue;
                }
                $attrs = $this->attrs($panel);
                $label = $this->optionalString($attrs, 'label') ?? 'Tab ' . ($panelIndex + 1);
                $panels[] = [
                    'id' => $this->blockId($panel, 'tab-panel', $panelIndex),
                    'label' => $label,
                    'blocks' => $this->mapNestedBasicBlocks($panel['innerBlocks'] ?? []),
                ];
            }
        }

        if ($panels === []) {
            throw new InvalidArgumentException('Tabs requires at least one tab panel.');
        }

        return new PageBlock(
            id: $this->blockId($block, 'tabs', $index),
            type: BlockType::TABS,
            data: new TabsData($panels),
            style: $this->styleFromAttrs($attrs),
            element: 'section',
        );
    }

    /**
     * Map nested Gutenberg content while preserving the same basic-block rules
     * used by the page builder when a wrapper is not directly supported.
     *
     * @param array<mixed> $rawBlocks
     * @return list<PageBlock>
     */
    private function mapNestedBasicBlocks(array $rawBlocks): array
    {
        $mapped = [];
        foreach ($rawBlocks as $rawBlock) {
            if (!is_array($rawBlock) || ($rawBlock['blockName'] ?? null) === null) {
                continue;
            }
            try {
                $mapped[] = $this->map($rawBlock);
            } catch (InvalidArgumentException) {
                $mapped = array_merge($mapped, $this->mapNestedBasicBlocks($rawBlock['innerBlocks'] ?? []));
            }
        }
        return $mapped;
    }

    /** @param array<string, mixed> $block */
    private function mapGallery(array $block, int $index = 0): PageBlock
    {
        $images = [];
        foreach ($this->listOfBlocks($block['innerBlocks'] ?? []) as $imageIndex => $innerBlock) {
            if (($innerBlock['blockName'] ?? null) !== 'core/image') {
                continue;
            }
            $attrs = $this->attrs($innerBlock);
            $src = $this->optionalString($attrs, 'url') ?? $this->htmlAttribute($innerBlock, 'img', 'src');
            if ($src === null) {
                continue;
            }
            $images[] = [
                'id' => $this->blockId($innerBlock, 'gallery-image', $imageIndex),
                'src' => $this->safeUrl($src),
                'alt' => $this->optionalString($attrs, 'alt') ?? $this->htmlAttribute($innerBlock, 'img', 'alt') ?? '',
                'caption' => $this->optionalString($attrs, 'caption'),
            ];
        }
        if ($images === []) {
            throw new InvalidArgumentException('Gallery requires at least one image.');
        }
        $attrs = $this->attrs($block);
        return new PageBlock(
            id: $this->blockId($block, 'gallery', $index),
            type: BlockType::GALLERY,
            data: new InteractiveBlockData([
                'images' => $images,
                'columns' => max(1, min((int) ($attrs['columns'] ?? 3), 6)),
            ]),
            style: $this->styleFromAttrs($attrs),
            element: 'section',
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
                customCss: $this->customCss($attrs['style']['css'] ?? null),
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
                html: $this->blockHtml($block, $element),
                customCss: $this->customCss($attrs['style']['css'] ?? null),
                attributes: $this->attributes($attrs, ['level', 'fontSize', 'align', 'className']),
            ),
            style: $this->styleFromAttrs($attrs),
            element: $element,
        );
    }

    /**
     * Map Gutenberg's native Custom HTML block while keeping its markup safe.
     * This is also used for fragment anchors such as <a id="expertise"></a>.
     *
     * @param array<string, mixed> $block
     */
    private function mapCustomHtml(array $block, int $index = 0): PageBlock
    {
        $attrs = $this->attrs($block);
        $html = $this->customHtml($block);

        if ($html === null) {
            throw new InvalidArgumentException('Custom HTML block requires content.');
        }

        $attributes = $this->attributes($attrs, ['align', 'className']);
        $fragmentId = $this->customHtmlFragmentId($html);
        if ($fragmentId !== null) {
            $attributes['id'] = $fragmentId;
        }

        return new PageBlock(
            id: $this->blockId($block, 'html', $index),
            type: BlockType::CONTAINER,
            data: new BasicBlockData(
                html: $html,
                layout: 'html',
                customCss: $this->customCss($attrs['style']['css'] ?? null),
                attributes: $attributes,
            ),
            style: $this->styleFromAttrs($attrs),
            element: 'div',
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

        if ($text === null) {
            throw new InvalidArgumentException('Button block requires text.');
        }

        return new PageBlock(
            id: $this->blockId($block, 'button', $index),
            type: BlockType::LINK,
            data: new BasicBlockData(
                text: $text,
                href: $href !== null ? $this->safeHref($href) : null,
                target: $this->optionalString($attrs, 'linkTarget'),
                rel: $this->optionalString($attrs, 'rel'),
                layout: 'button',
                customCss: $this->customCss($attrs['style']['css'] ?? null),
                attributes: $this->attributes($attrs, ['className']),
            ),
            style: $this->buttonStyle($attrs),
            element: $href !== null ? 'a' : 'button',
        );
    }

    /**
     * Convert WordPress button style variations into portable schema properties.
     *
     * @param array<string, mixed> $attrs
     */
    private function buttonStyle(array $attrs): ?BlockStyle
    {
        $style = $this->styleFromAttrs($attrs);
        $className = $this->optionalString($attrs, 'className') ?? '';

        if (!str_contains($className, 'is-style-outline')) {
            return $style;
        }

        $properties = $style !== null ? $style->properties : [];
        $properties['backgroundColor'] = 'transparent';
        $properties['borderWidth'] = '1px';
        $properties['borderStyle'] = 'solid';

        if (!isset($properties['borderColor']) && isset($properties['color']) && is_string($properties['color'])) {
            $properties['borderColor'] = $properties['color'];
        }

        return new BlockStyle($style?->variant, $properties);
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

        $style = $this->styleFromAttrs($attrs);
        $properties = $style !== null ? $style->properties : [];
        $aspectRatio = $this->optionalString($attrs, 'aspectRatio');
        $scale = $this->optionalString($attrs, 'scale');

        if ($aspectRatio !== null) {
            $properties['aspectRatio'] = $aspectRatio;
        }

        if ($scale !== null && in_array($scale, ['cover', 'contain'], true)) {
            $properties['objectFit'] = $scale;
        }

        return new PageBlock(
            id: $this->blockId($block, 'image', $index),
            type: BlockType::IMAGE,
            data: new BasicBlockData(
                src: $this->safeUrl($src),
                srcSet: $this->optionalString($attrs, 'srcSet') ?? $this->htmlAttribute($block, 'img', 'srcset'),
                loading: $this->safeLoading($this->optionalString($attrs, 'loading') ?? $this->htmlAttribute($block, 'img', 'loading')),
                mimeType: $this->optionalString($attrs, 'mimeType'),
                caption: $this->optionalString($attrs, 'caption'),
                alt: $this->optionalString($attrs, 'alt') ?? $this->htmlAttribute($block, 'img', 'alt') ?? '',
                customCss: $this->customCss($attrs['style']['css'] ?? null),
                attributes: $attributes,
            ),
            style: $properties !== [] ? new BlockStyle($style?->variant, $properties) : null,
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
                customCss: $this->customCss($attrs['style']['css'] ?? null),
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
                customCss: $this->customCss($attrs['style']['css'] ?? null),
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
        $items = [];
        foreach ($this->listOfBlocks($block['innerBlocks'] ?? []) as $itemIndex => $innerBlock) {
            if (($innerBlock['blockName'] ?? null) !== 'core/accordion-item') {
                continue;
            }
            $summary = $this->optionalString($this->attrs($innerBlock), 'title')
                ?? $this->htmlClassText($innerBlock, 'wp-block-accordion-heading__toggle-title')
                ?? $this->firstDescendantClassText($innerBlock, 'wp-block-accordion-heading__toggle-title')
                ?? $this->htmlElementText($innerBlock, 'button');
            if ($summary === null) {
                continue;
            }
            $itemAttrs = $this->attrs($innerBlock);
            $items[] = [
                'id' => $this->blockId($innerBlock, 'accordion-item', $itemIndex),
                'title' => $summary,
                'expanded' => $this->optionalBool($itemAttrs, 'openByDefault') ?? false,
                'blocks' => $this->mapAccordionPanelChildren($innerBlock),
            ];
        }
        if ($items === []) {
            throw new InvalidArgumentException('Accordion requires at least one valid item.');
        }
        $attrs = $this->attrs($block);
        return new PageBlock(
            id: $this->blockId($block, 'accordion', $index),
            type: BlockType::ACCORDION,
            data: new InteractiveBlockData([
                'items' => $items,
                'allowMultiple' => $this->optionalBool($attrs, 'multiple') ?? true,
            ]),
            style: $this->styleFromAttrs($attrs),
            element: 'section',
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

        // Gutenberg column widths are percentages. Image width/height attributes
        // are intrinsic dimensions and must remain HTML attributes, not CSS.
        if ($layout === 'column' && isset($attrs['width'])) {
            if (is_string($attrs['width']) && trim($attrs['width']) !== '') {
                $properties['width'] = [
                    'mobile' => '100%',
                    'tablet' => '100%',
                    'desktop' => trim($attrs['width']),
                ];
            } elseif (is_int($attrs['width']) || is_float($attrs['width'])) {
                $properties['width'] = [
                    'mobile' => '100%',
                    'tablet' => '100%',
                    'desktop' => $attrs['width'] . '%',
                ];
            }
        }

        $dimensionWidth = $this->nestedResponsiveValue($attrs, ['style', 'dimensions', 'width']);

        if ($dimensionWidth !== null) {
            $properties['width'] = $dimensionWidth;
        }

        $dimensionHeight = $this->nestedResponsiveValue($attrs, ['style', 'dimensions', 'height']);

        if ($dimensionHeight !== null) {
            $properties['height'] = $dimensionHeight;
        }

        $minHeight = $this->nestedResponsiveValue($attrs, ['style', 'dimensions', 'minHeight']);

        if ($minHeight !== null) {
            $properties['minHeight'] = $minHeight;
        }

        $background = $this->nestedResponsiveValue($attrs, ['style', 'color', 'gradient']);
        $backgroundColor = $this->nestedResponsiveValue($attrs, ['style', 'color', 'background']);
        $presetGradient = $this->optionalString($attrs, 'gradient');
        $presetBackgroundColor = $this->optionalString($attrs, 'backgroundColor');
        $textColor = $this->nestedResponsiveValue($attrs, ['style', 'color', 'text']);
        $presetTextColor = $this->optionalString($attrs, 'textColor');

        if ($background !== null) {
            $properties['background'] = $background;
        } elseif ($presetGradient !== null) {
            $properties['background'] = $this->presetStyleValue('gradient', $presetGradient);
        } elseif ($backgroundColor !== null) {
            $properties['backgroundColor'] = $backgroundColor;
        } elseif ($presetBackgroundColor !== null) {
            $properties['backgroundColor'] = $this->presetStyleValue('color', $presetBackgroundColor);
        }

        if ($textColor !== null) {
            $properties['color'] = $textColor;
        } elseif ($presetTextColor !== null) {
            $properties['color'] = $this->presetStyleValue('color', $presetTextColor);
        }

        // Typography
        $fontSize = $this->nestedResponsiveValue($attrs, ['style', 'typography', 'fontSize']);
        $presetFontSize = $this->optionalString($attrs, 'fontSize');
        $fontFamily = $this->nestedResponsiveValue($attrs, ['style', 'typography', 'fontFamily']);
        $presetFontFamily = $this->optionalString($attrs, 'fontFamily');
        $fontWeight = $this->nestedResponsiveValue($attrs, ['style', 'typography', 'fontWeight']) ?? $this->optionalString($attrs, 'fontWeight');
        $letterSpacing = $this->nestedResponsiveValue($attrs, ['style', 'typography', 'letterSpacing']) ?? $this->optionalString($attrs, 'letterSpacing');
        $textTransform = $this->nestedResponsiveValue($attrs, ['style', 'typography', 'textTransform']) ?? $this->optionalString($attrs, 'textTransform');
        $lineHeight = $this->nestedResponsiveValue($attrs, ['style', 'typography', 'lineHeight']) ?? $this->optionalString($attrs, 'lineHeight');
        $fontStyle = $this->nestedResponsiveValue($attrs, ['style', 'typography', 'fontStyle']) ?? $this->optionalString($attrs, 'fontStyle');
        $textDecoration = $this->nestedResponsiveValue($attrs, ['style', 'typography', 'textDecoration']) ?? $this->optionalString($attrs, 'textDecoration');
        $textAlign = $this->nestedResponsiveValue($attrs, ['style', 'typography', 'textAlign']) ?? $this->optionalString($attrs, 'textAlign');

        if ($fontSize !== null) {
            $properties['fontSize'] = $fontSize;
        } elseif ($presetFontSize !== null) {
            $properties['fontSize'] = $this->presetStyleValue('font-size', $presetFontSize);
        }

        if ($fontFamily !== null) {
            $properties['fontFamily'] = $fontFamily;
        } elseif ($presetFontFamily !== null) {
            $properties['fontFamily'] = $this->presetStyleValue('font-family', $presetFontFamily);
        }

        if ($fontWeight !== null) {
            $properties['fontWeight'] = $this->normalizeResponsiveStyleValue($fontWeight);
        }
        if ($letterSpacing !== null) {
            $properties['letterSpacing'] = $this->normalizeResponsiveStyleValue($letterSpacing);
        }
        if ($textTransform !== null) {
            $properties['textTransform'] = $this->normalizeResponsiveStyleValue($textTransform);
        }
        if ($lineHeight !== null) {
            $properties['lineHeight'] = $this->normalizeResponsiveStyleValue($lineHeight);
        }
        if ($fontStyle !== null) {
            $properties['fontStyle'] = $this->normalizeResponsiveStyleValue($fontStyle);
        }
        if ($textDecoration !== null) {
            $properties['textDecoration'] = $this->normalizeResponsiveStyleValue($textDecoration);
        }
        if ($textAlign !== null) {
            $properties['textAlign'] = $this->normalizeResponsiveStyleValue($textAlign);
        }

        // Gutenberg stores block shadows under style.shadow or as a preset slug.
        $boxShadow = $this->nestedResponsiveValue($attrs, ['style', 'shadow']);
        $presetShadow = $this->optionalString($attrs, 'shadow');

        if ($boxShadow !== null) {
            $properties['boxShadow'] = $boxShadow;
        } elseif ($presetShadow !== null) {
            $properties['boxShadow'] = $this->presetStyleValue('shadow', $presetShadow);
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
        } elseif ($borderColor !== null || $presetBorderColor !== null || $borderWidth !== null) {
            $properties['borderStyle'] = 'solid';
        }

        $verticalAlignment = $this->optionalString($attrs, 'verticalAlignment');

        if ($layout === 'columns') {
            if (!isset($properties['gap'])) {
                $properties['gap'] = 'var(--wp--preset--spacing--50)';
            }
        }

        if ($layout === 'grid') {
            $properties['display'] = 'grid';
            $properties['gap'] ??= 'var(--wp--style--block-gap, 1.2rem)';
        }

        if (($layout === 'details' || $layout === 'accordion-item') && !isset($properties['margin'])) {
            $properties['margin'] = ['bottom' => 'var(--wp--preset--spacing--30)'];
        }

        if ($layout === 'column' && $verticalAlignment !== null) {
            $properties['alignSelf'] = $this->flexAlignment($verticalAlignment) ?? $verticalAlignment;
        }

        foreach ($this->customCssProperties($attrs['style']['css'] ?? null) as $property => $value) {
            if (!isset($properties[$property])) {
                $properties[$property] = $value;
            }
        }

        return $properties !== [] ? new BlockStyle(properties: $properties) : null;
    }

    /**
     * Extract the portable, safe subset of Gutenberg's custom CSS attribute.
     *
     * @return array<string, string|int|float>
     */
    private function customCssProperties(mixed $css): array
    {
        if (!is_string($css)) {
            return [];
        }

        $allowed = [
            'display' => 'display',
            'flex-direction' => 'flexDirection',
            'flex-wrap' => 'flexWrap',
            'justify-content' => 'justifyContent',
            'align-items' => 'alignItems',
            'text-align' => 'textAlign',
            'border-radius' => 'borderRadius',
            'outline' => 'outline',
            'outline-offset' => 'outlineOffset',
            'position' => 'position',
            'box-shadow' => 'boxShadow',
        ];
        $properties = [];

        preg_match_all('/([a-z-]+)\s*:\s*([^;{}]+)/i', $css, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $cssProperty = strtolower(trim($match[1]));
            $value = trim($match[2]);

            if (!isset($allowed[$cssProperty]) || $value === '' || preg_match('/url\s*\(|expression\s*\(|[<>;]/i', $value)) {
                continue;
            }

            $properties[$allowed[$cssProperty]] = $this->normalizeWordPressStyleValue($value);
        }

        return $properties;
    }

    /**
     * Preserve Gutenberg custom CSS as a sanitized, block-local rule set.
     *
     * @return string|null
     */
    private function customCss(mixed $css): ?string
    {
        if (!is_string($css) || trim($css) === '') {
            return null;
        }

        if (preg_match('/(?:@import|url\s*\(|expression\s*\(|javascript\s*:|behavior\s*:|-moz-binding)/i', $css)) {
            return null;
        }

        preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $rules, PREG_SET_ORDER);
        $safeRules = [];

        foreach ($rules as $rule) {
            $selector = trim($rule[1]);
            $declarations = [];

            if ($selector === '' || str_contains($selector, '@') || !preg_match('/^[A-Za-z0-9_.,:#\-\s>+~*\[\]=()"\']+$/', $selector)) {
                continue;
            }

            preg_match_all('/([a-z][a-z0-9-]*)\s*:\s*([^;{}]+)/i', $rule[2], $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $property = strtolower(trim($match[1]));
                $value = trim($match[2]);

                if ($value === '' || preg_match('/[<>]|url\s*\(|expression\s*\(|javascript\s*:/i', $value)) {
                    continue;
                }

                $declarations[] = $property . ': ' . $value . ';';
            }

            if ($declarations !== []) {
                $safeRules[] = $selector . '{' . implode('', $declarations) . '}';
            }
        }

        return $safeRules !== [] ? implode('', $safeRules) : null;
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

        $anchor = $this->optionalString($attrs, 'anchor');
        if ($anchor !== null && preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $anchor)) {
            $attributes['id'] = $anchor;
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
     * Return a small, safe inline HTML subset for headings and paragraphs.
     *
     * @param array<string, mixed> $block
     */
    private function blockHtml(array $block, string $element): ?string
    {
        if (!isset($block['innerHTML']) || !is_string($block['innerHTML'])) {
            return null;
        }

        $html = trim($block['innerHTML']);
        $pattern = '/^<' . preg_quote($element, '/') . '\\b[^>]*>(.*?)<\\/' . preg_quote($element, '/') . '>$/is';

        if (preg_match($pattern, $html, $matches)) {
            $html = trim($matches[1]);
        }

        if (function_exists('wp_kses')) {
            $html = wp_kses($html, [
                'a' => ['href' => true, 'target' => true, 'rel' => true],
                'br' => [],
                'code' => [],
                'em' => [],
                'i' => [],
                'mark' => [],
                's' => [],
                'span' => ['class' => true],
                'strong' => [],
                'u' => [],
            ]);
        } else {
            $html = strip_tags($html, '<a><br><code><em><i><mark><s><span><strong><u>');
        }

        return trim($html) !== '' ? trim($html) : null;
    }

    /**
     * Return a safe subset of a native Gutenberg Custom HTML block.
     * IDs are explicitly preserved because they are used by menu fragment links.
     *
     * @param array<string, mixed> $block
     */
    private function customHtml(array $block): ?string
    {
        if (!isset($block['innerHTML']) || !is_string($block['innerHTML'])) {
            return null;
        }

        $html = trim($block['innerHTML']);

        if (function_exists('wp_kses')) {
            $html = wp_kses($html, [
                'a' => [
                    'id' => true,
                    'class' => true,
                    'href' => true,
                    'target' => true,
                    'rel' => true,
                    'title' => true,
                    'aria-label' => true,
                ],
                'br' => [],
                'div' => ['id' => true, 'class' => true],
                'em' => [],
                'i' => [],
                'mark' => [],
                'p' => ['id' => true, 'class' => true],
                's' => [],
                'span' => ['id' => true, 'class' => true],
                'strong' => [],
                'u' => [],
            ]);
        } else {
            $html = strip_tags($html, '<a><br><div><em><i><mark><p><s><span><strong><u>');
        }

        return trim($html) !== '' ? trim($html) : null;
    }

    private function customHtmlFragmentId(string $html): ?string
    {
        if (!preg_match('/<a\b[^>]*\bid\s*=\s*(["\'])([A-Za-z][A-Za-z0-9_-]*)\1[^>]*>/i', $html, $matches)) {
            return null;
        }

        return $matches[2];
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

    private function safeLoading(?string $loading): ?string
    {
        return $loading !== null && in_array($loading, ['lazy', 'eager'], true) ? $loading : null;
    }

    private function safeHref(string $href): string
    {
        if (str_starts_with($href, '#') || (str_starts_with($href, '/') && !str_starts_with($href, '//'))) {
            return $href;
        }

        if (preg_match('/^(mailto|tel):[^\\s]+$/i', $href)) {
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

    /**
     * @param array<string, mixed> $values
     * @param list<string> $path
     */
    private function nestedInt(array $values, array $path): ?int
    {
        $current = $values;

        foreach ($path as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return is_int($current) ? $current : null;
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
     * @param string|int|float|array<string, string|int|float> $value
     * @return string|int|float|array<string, string|int|float>
     */
    private function normalizeResponsiveStyleValue(string|int|float|array $value): string|int|float|array
    {
        if (!is_array($value)) {
            return $this->normalizeWordPressStyleValue($value);
        }

        $normalized = [];
        foreach ($value as $key => $entry) {
            if (is_string($key) && (is_string($entry) || is_int($entry) || is_float($entry))) {
                $normalized[$key] = $this->normalizeWordPressStyleValue($entry);
            }
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $values
     * @param list<string> $path
     * @return string|int|float|array<string, string|int|float>|null
     */
    private function nestedResponsiveValue(array $values, array $path): string|int|float|array|null
    {
        $current = $values;

        foreach ($path as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        if (is_string($current) || is_int($current) || is_float($current)) {
            return $this->normalizeWordPressStyleValue($current);
        }

        if (!is_array($current)) {
            return null;
        }

        $result = [];
        foreach ($current as $key => $value) {
            if (is_string($key) && (is_string($value) || is_int($value) || is_float($value))) {
                $result[$key] = $this->normalizeWordPressStyleValue($value);
            }
        }

        return $result !== [] ? $result : null;
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
