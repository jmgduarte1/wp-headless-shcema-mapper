<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Builder;

use HeadlessAngular\Schema\Domain\Schema\PageDefinition;
use HeadlessAngular\Schema\Domain\Schema\PageBlock;
use HeadlessAngular\Schema\Domain\Schema\PageSchema;
use HeadlessAngular\Schema\Domain\Schema\PageStatus;
use HeadlessAngular\Schema\Domain\Schema\MediaAsset;
use HeadlessAngular\Schema\Domain\Schema\OpenGraphMetadata;
use HeadlessAngular\Schema\Domain\Schema\RobotsMetadata;
use HeadlessAngular\Schema\Domain\Schema\SeoMetadata;
use HeadlessAngular\Schema\Domain\Schema\SocialCardMetadata;
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
                seo: $this->seo($post),
            ),
        );
    }

    private function seo(\WP_Post $post): SeoMetadata
    {
        $title = wp_strip_all_tags((string) get_the_title($post));
        $description = wp_strip_all_tags((string) get_the_excerpt($post));
        $canonical = (string) get_permalink($post);
        $image = $this->featuredImage($post);
        $openGraph = new OpenGraphMetadata($title, $description, $canonical, 'article', $image);

        return new SeoMetadata(
            title: $title,
            description: $description !== '' ? $description : null,
            canonical: $canonical !== '' ? $canonical : null,
            robots: new RobotsMetadata(),
            openGraph: $openGraph,
            twitter: new SocialCardMetadata(
                card: $image instanceof MediaAsset ? 'summary_large_image' : 'summary',
                title: $title,
                description: $description !== '' ? $description : null,
                image: $image,
            ),
        );
    }

    private function featuredImage(\WP_Post $post): ?MediaAsset
    {
        $attachmentId = (int) get_post_thumbnail_id($post);

        if ($attachmentId === 0) {
            return null;
        }

        $src = wp_get_attachment_image_url($attachmentId, 'full');

        if (!is_string($src) || $src === '') {
            return null;
        }

        $alt = get_post_meta($attachmentId, '_wp_attachment_image_alt', true);

        return new MediaAsset(
            src: $src,
            alt: is_string($alt) && $alt !== '' ? wp_strip_all_tags($alt) : null,
            decorative: false,
            width: (int) (wp_get_attachment_metadata($attachmentId)['width'] ?? 0) ?: null,
            height: (int) (wp_get_attachment_metadata($attachmentId)['height'] ?? 0) ?: null,
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
