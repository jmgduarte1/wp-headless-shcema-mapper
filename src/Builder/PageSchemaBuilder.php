<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Builder;

use HeadlessAngular\Schema\Domain\Schema\PageDefinition;
use HeadlessAngular\Schema\Domain\Schema\PageBlock;
use HeadlessAngular\Schema\Domain\Schema\PageSchema;
use HeadlessAngular\Schema\Domain\Schema\PageStatus;
use HeadlessAngular\Schema\Mapping\BlockMapperRegistry;
use InvalidArgumentException;

final readonly class PageSchemaBuilder
{
    public function __construct(
        private BlockMapperRegistry $mapperRegistry,
    ) {
    }

    public function build(\WP_Post $post, string $locale): PageSchema
    {
        $blocks = $this->mapBlocks(parse_blocks($post->post_content));

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

    /**
     * @param array<mixed> $rawBlocks
     *
     * @return list<PageBlock>
     */
    private function mapBlocks(array $rawBlocks): array
    {
        $mappedBlocks = [];

        foreach ($rawBlocks as $rawBlock) {
            if (!is_array($rawBlock) || ($rawBlock['blockName'] ?? null) === null) {
                continue;
            }

            try {
                $mappedBlocks[] = $this->mapperRegistry->map($rawBlock);
            } catch (InvalidArgumentException) {
                $innerBlocks = is_array($rawBlock['innerBlocks'] ?? null) ? $rawBlock['innerBlocks'] : [];
                $mappedBlocks = array_merge($mappedBlocks, $this->mapBlocks($innerBlocks));
            }
        }

        return $mappedBlocks;
    }
}
