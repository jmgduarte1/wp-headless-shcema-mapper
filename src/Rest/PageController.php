<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Rest;

use HeadlessAngular\Schema\Builder\PageSchemaBuilder;
use HeadlessAngular\Schema\Mapping\BasicBlockMapper;
use HeadlessAngular\Schema\Mapping\BlockMapperRegistry;
use HeadlessAngular\Schema\Mapping\HeroBlockMapper;
use HeadlessAngular\Schema\Serialization\V1PageSchemaSerializer;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class PageController
{
    private const NAMESPACE = 'headless-renderer/v1';

    public function registerRoutes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/pages/(?P<slug>[a-zA-Z0-9\-_/]+)',
            [
                'methods' => 'GET',
                'callback' => [$this, 'show'],
                'permission_callback' => '__return_true',
                'args' => [
                    'slug' => [
                        'required' => true,
                        'sanitize_callback' => 'sanitize_title',
                    ],
                    'locale' => [
                        'required' => false,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        );
    }

    public function show(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $slug = (string) $request->get_param('slug');
        $locale = (string) ($request->get_param('locale') ?: get_locale());

        if ($slug === '') {
            return new WP_Error('headless_schema_invalid_slug', 'Invalid page slug.', ['status' => 400]);
        }

        $post = get_page_by_path($slug, OBJECT, 'page');

        if (!$post instanceof \WP_Post || $post->post_status !== 'publish') {
            return new WP_Error('headless_schema_page_not_found', 'Page not found.', ['status' => 404]);
        }

        try {
            $builder = new PageSchemaBuilder(
                new BlockMapperRegistry([
                    new BasicBlockMapper(),
                    new HeroBlockMapper(),
                ]),
            );
            $serializer = new V1PageSchemaSerializer();

            $payload = $serializer->serialize($builder->build($post, $locale));
            $payload = apply_filters('headless_angular_schema_page_response', $payload, $post, $locale);

            return new WP_REST_Response($payload, 200);
        } catch (Throwable) {
            return new WP_Error('headless_schema_normalization_failed', 'Page schema could not be generated.', ['status' => 500]);
        }
    }
}
