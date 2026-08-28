<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Serialization;

use HeadlessAngular\Schema\Domain\Schema\BlockStyle;
use HeadlessAngular\Schema\Domain\Schema\BlockType;
use HeadlessAngular\Schema\Domain\Schema\HeroAction;
use HeadlessAngular\Schema\Domain\Schema\HeroBlockData;
use HeadlessAngular\Schema\Domain\Schema\HeroMedia;
use HeadlessAngular\Schema\Domain\Schema\Link\AnchorLink;
use HeadlessAngular\Schema\Domain\Schema\Link\ExternalLink;
use HeadlessAngular\Schema\Domain\Schema\Link\InternalLink;
use HeadlessAngular\Schema\Domain\Schema\Link\LinkModel;
use HeadlessAngular\Schema\Domain\Schema\MediaAsset;
use HeadlessAngular\Schema\Domain\Schema\PageBlock;
use HeadlessAngular\Schema\Domain\Schema\PageSchema;
use UnexpectedValueException;

final class V1PageSchemaSerializer implements PageSchemaSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(PageSchema $schema): array
    {
        return [
            'schemaVersion' => PageSchema::VERSION,
            'locale' => $schema->locale,
            'page' => [
                'id' => $schema->page->id,
                'slug' => $schema->page->slug,
                'title' => $schema->page->title,
                'status' => $schema->page->status->value,
                'blocks' => array_map(
                    fn (PageBlock $block): array => $this->serializeBlock($block),
                    $schema->page->blocks,
                ),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeBlock(PageBlock $block): array
    {
        $payload = [
            'id' => $block->id,
            'type' => $block->type,
        ];

        if ($block->type === BlockType::HERO && $block->data instanceof HeroBlockData) {
            $payload['data'] = $this->serializeHeroData($block->data);
        } else {
            throw new UnexpectedValueException('Unsupported PageSchema block data.');
        }

        if ($block->style instanceof BlockStyle) {
            $payload['style'] = $this->serializeStyle($block->style);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeHeroData(HeroBlockData $data): array
    {
        $payload = [
            'title' => $data->title,
        ];

        if ($data->eyebrow !== null) {
            $payload['eyebrow'] = $data->eyebrow;
        }

        if ($data->subtitle !== null) {
            $payload['subtitle'] = $data->subtitle;
        }

        if ($data->media instanceof HeroMedia) {
            $payload['media'] = $this->serializeHeroMedia($data->media);
        }

        if ($data->actions !== []) {
            $payload['actions'] = array_map(
                fn (HeroAction $action): array => $this->serializeHeroAction($action),
                $data->actions,
            );
        }

        if ($data->layout !== null) {
            $layout = [];

            if ($data->layout->contentAlignment !== null) {
                $layout['contentAlignment'] = $data->layout->contentAlignment->value;
            }

            if ($data->layout->verticalAlignment !== null) {
                $layout['verticalAlignment'] = $data->layout->verticalAlignment->value;
            }

            if ($data->layout->contentWidth !== null) {
                $layout['contentWidth'] = $data->layout->contentWidth->value;
            }

            if ($layout !== []) {
                $payload['layout'] = $layout;
            }
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeHeroMedia(HeroMedia $media): array
    {
        return [
            'placement' => $media->placement->value,
            'image' => $this->serializeMediaAsset($media->image),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMediaAsset(MediaAsset $asset): array
    {
        $payload = [
            'src' => $asset->src,
        ];

        if ($asset->alt !== null) {
            $payload['alt'] = $asset->alt;
        }

        if ($asset->decorative) {
            $payload['decorative'] = true;
        }

        if ($asset->width !== null) {
            $payload['width'] = $asset->width;
        }

        if ($asset->height !== null) {
            $payload['height'] = $asset->height;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeHeroAction(HeroAction $action): array
    {
        $payload = [
            'id' => $action->id,
            'label' => $action->label,
            'link' => $this->serializeLink($action->link),
        ];

        if ($action->variant !== null) {
            $payload['variant'] = $action->variant->value;
        }

        if ($action->accessibleLabel !== null) {
            $payload['accessibleLabel'] = $action->accessibleLabel;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeLink(LinkModel $link): array
    {
        if ($link instanceof InternalLink) {
            return [
                'type' => $link->type(),
                'path' => $link->path,
            ];
        }

        if ($link instanceof ExternalLink) {
            $payload = [
                'type' => $link->type(),
                'url' => $link->url,
            ];

            if ($link->target !== null) {
                $payload['target'] = $link->target;
            }

            if ($link->rel !== []) {
                $payload['rel'] = $link->rel;
            }

            return $payload;
        }

        if ($link instanceof AnchorLink) {
            return [
                'type' => $link->type(),
                'anchor' => $link->anchor,
            ];
        }

        throw new UnexpectedValueException('Unsupported link model.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeStyle(BlockStyle $style): array
    {
        $payload = [];

        if ($style->variant !== null) {
            $payload['variant'] = $style->variant;
        }

        if ($style->properties !== []) {
            $payload['properties'] = $style->properties;
        }

        return $payload;
    }
}
