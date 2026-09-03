<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Rest;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class NavigationController
{
    private const NAMESPACE = 'headless-renderer/v1';

    public function registerRoutes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/menus/(?P<location>[a-zA-Z0-9_-]+)',
            [
                'methods' => 'GET',
                'callback' => [$this, 'show'],
                'permission_callback' => '__return_true',
                'args' => [
                    'location' => [
                        'required' => true,
                        'sanitize_callback' => 'sanitize_key',
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
        $location = (string) $request->get_param('location');
        $locale = (string) ($request->get_param('locale') ?: get_locale());
        $locations = get_nav_menu_locations();
        $menuId = $locations[$location] ?? 0;

        if ($menuId === 0) {
            if ($location === 'primary') {
                $items = $this->blockNavigation();

                if ($items !== null) {
                    $payload = [
                        'schemaVersion' => '1.0',
                        'location' => $location,
                        'items' => $items,
                    ];
                    $payload = apply_filters('headless_angular_schema_navigation_response', $payload, $location, $locale);

                    return new WP_REST_Response($payload, 200);
                }
            }

            return new WP_Error('headless_schema_menu_not_found', 'Menu not found.', ['status' => 404]);
        }

        $items = wp_get_nav_menu_items($menuId);

        if (!is_array($items)) {
            return new WP_Error('headless_schema_menu_not_found', 'Menu not found.', ['status' => 404]);
        }

        $payload = [
            'schemaVersion' => '1.0',
            'location' => $location,
            'items' => $this->tree($items),
        ];
        $payload = apply_filters('headless_angular_schema_navigation_response', $payload, $location, $locale);

        return new WP_REST_Response($payload, 200);
    }

    /**
     * Resolve the published block navigation used by block themes.
     *
     * @return list<array<string, mixed>>|null
     */
    private function blockNavigation(): ?array
    {
        $navigations = get_posts([
            'post_type' => 'wp_navigation',
            'post_status' => 'publish',
            'numberposts' => 1,
        ]);

        if (!isset($navigations[0]) || !is_object($navigations[0])) {
            return null;
        }

        $items = [];
        foreach (parse_blocks((string) $navigations[0]->post_content) as $block) {
            $items = array_merge($items, $this->mapNavigationBlock($block));
        }

        return $items !== [] ? $items : null;
    }

    /**
     * @param array<string, mixed> $block
     * @return list<array<string, mixed>>
     */
    private function mapNavigationBlock(array $block): array
    {
        $blockName = $block['blockName'] ?? null;

        if ($blockName === 'core/page-list') {
            return $this->pageListItems();
        }

        if ($blockName !== 'core/navigation-link') {
            $items = [];
            foreach ($block['innerBlocks'] ?? [] as $innerBlock) {
                if (is_array($innerBlock)) {
                    $items = array_merge($items, $this->mapNavigationBlock($innerBlock));
                }
            }

            return $items;
        }

        $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
        $label = is_string($attrs['label'] ?? null) ? $attrs['label'] : '';
        $url = is_string($attrs['url'] ?? null) ? $attrs['url'] : '';

        if ($label === '' || $url === '') {
            return [];
        }

        $children = [];
        foreach ($block['innerBlocks'] ?? [] as $innerBlock) {
            if (is_array($innerBlock)) {
                $children = array_merge($children, $this->mapNavigationBlock($innerBlock));
            }
        }

        return [[
            'id' => 'block-' . substr(md5($label . '|' . $url), 0, 12),
            'label' => wp_strip_all_tags($label),
            'link' => $this->link($url),
            'children' => $children,
        ]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pageListItems(): array
    {
        $pages = get_pages([
            'post_status' => 'publish',
            'sort_column' => 'menu_order,post_title',
        ]) ?: [];
        $nodes = [];

        foreach ($pages as $page) {
            if (!is_object($page)) {
                continue;
            }

            $id = (int) $page->ID;
            $nodes[$id] = [
                'id' => 'page-' . $id,
                'label' => wp_strip_all_tags((string) $page->post_title),
                'link' => $this->link((string) get_permalink($id)),
                'parentId' => (int) $page->post_parent,
                'children' => [],
            ];
        }

        return $this->treeNodes($nodes);
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     * @return list<array<string, mixed>>
     */
    private function treeNodes(array $nodes): array
    {
        $roots = [];

        foreach ($nodes as $id => &$node) {
            $parentId = (int) $node['parentId'];
            unset($node['parentId']);

            if ($parentId !== 0 && isset($nodes[$parentId])) {
                $nodes[$parentId]['children'][] =& $node;
            } else {
                $roots[] =& $node;
            }
        }
        unset($node);

        return $roots;
    }

    /**
     * @param array<int, object> $items
     * @return list<array<string, mixed>>
     */
    private function tree(array $items): array
    {
        $nodes = [];

        foreach ($items as $item) {
            if (!is_object($item) || !isset($item->ID, $item->title, $item->url)) {
                continue;
            }

            $id = (int) $item->ID;
            $nodes[$id] = [
                'id' => (string) $id,
                'label' => wp_strip_all_tags((string) $item->title),
                'link' => $this->link((string) $item->url),
                'parentId' => isset($item->menu_item_parent) ? (string) $item->menu_item_parent : '0',
                'children' => [],
            ];
        }

        $roots = [];

        foreach ($nodes as $id => &$node) {
            $parentId = (string) $node['parentId'];
            unset($node['parentId']);

            if ($parentId !== '0' && isset($nodes[(int) $parentId])) {
                $nodes[(int) $parentId]['children'][] =& $node;
            } else {
                $roots[] =& $node;
            }
        }
        unset($node);

        return $roots;
    }

    /**
     * @return array<string, mixed>
     */
    private function link(string $url): array
    {
        if (str_starts_with($url, 'mailto:')) {
            return ['type' => 'email', 'address' => substr($url, 7)];
        }

        if (str_starts_with($url, 'tel:')) {
            return ['type' => 'telephone', 'number' => substr($url, 4)];
        }

        if (str_starts_with($url, '#')) {
            return ['type' => 'anchor', 'anchor' => substr($url, 1)];
        }

        $parts = parse_url($url);
        $home = parse_url((string) home_url('/'));

        if (($parts['host'] ?? null) === ($home['host'] ?? null)) {
            return ['type' => 'internal', 'path' => ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '')];
        }

        return ['type' => 'external', 'url' => esc_url_raw($url), 'target' => '_blank', 'rel' => ['noopener', 'noreferrer']];
    }
}
