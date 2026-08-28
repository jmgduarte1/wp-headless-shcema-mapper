<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Builder;

use HeadlessAngular\Schema\Domain\Schema\PageDefinition;
use HeadlessAngular\Schema\Domain\Schema\PageSchema;
use HeadlessAngular\Schema\Domain\Schema\PageStatus;
use HeadlessAngular\Schema\Mapping\BlockMapperRegistry;

final readonly class PageSchemaBuilder
{
    public function __construct(
        private BlockMapperRegistry $mapperRegistry,
    ) {
    }

    public function build(\WP_Post $post, string $locale): PageSchema
    {
        $blocks = [];

        foreach (parse_blocks($post->post_content) as $block) {
            if (!is_array($block) || ($block['blockName'] ?? null) === null) {
                continue;
            }

            $blocks[] = $this->mapperRegistry->map($block);
        }

        return new PageSchema(
            locale: $locale,
            page: new PageDefinition(
                id: (string) $post->ID,
                slug: $post->post_name,
                title: get_the_title($post),
                status: PageStatus::Published,
                blocks: $blocks,
            ),
        );
    }
}
