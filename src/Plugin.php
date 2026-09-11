<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema;

final class Plugin
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function init(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->registerHooks();
        }

        return self::$instance;
    }

    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    private function registerHooks(): void
    {
        add_action('init', [$this, 'registerBlocks']);
        add_filter('register_block_type_args', [$this, 'extendGroupBlock'], 10, 2);
        add_filter('register_block_type_args', [$this, 'extendTabsBlock'], 10, 2);
        add_action('enqueue_block_editor_assets', [$this, 'enqueueEditorAssets']);
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function extendTabsBlock(array $args, string $blockType): array
    {
        if ($blockType === 'core/tabs') {
            $args['attributes'] = array_merge($args['attributes'] ?? [], [
                'headlessTabsOrientation' => ['type' => 'string', 'default' => 'horizontal'],
                'headlessTabsTitle' => ['type' => 'string', 'default' => ''],
            ]);
        }

        if ($blockType === 'core/tab-panel') {
            $args['attributes'] = array_merge($args['attributes'] ?? [], [
                'headlessTabIcon' => ['type' => 'string', 'default' => ''],
            ]);
        }

        return $args;
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function extendGroupBlock(array $args, string $blockType): array
    {
        if ($blockType !== 'core/group') {
            return $args;
        }

        $args['attributes'] = array_merge($args['attributes'] ?? [], [
            'headlessSliderEnabled' => ['type' => 'boolean', 'default' => false],
            'headlessMinColumnWidth' => ['type' => 'number', 'default' => 280],
            'headlessSliderNavigation' => ['type' => 'boolean', 'default' => true],
            'headlessSliderPagination' => ['type' => 'boolean', 'default' => true],
            'headlessSliderLoop' => ['type' => 'boolean', 'default' => false],
            'headlessSliderAutoplay' => ['type' => 'boolean', 'default' => false],
            'headlessSliderAutoplayDelay' => ['type' => 'number', 'default' => 5000],
        ]);

        return $args;
    }

    public function enqueueEditorAssets(): void
    {
        wp_enqueue_script(
            'headless-angular-grid-editor',
            HEADLESS_ANGULAR_SCHEMA_URL . 'blocks/responsive-grid-editor/index.js',
            ['wp-block-editor', 'wp-blocks', 'wp-components', 'wp-compose', 'wp-data', 'wp-dom-ready', 'wp-element', 'wp-hooks'],
            HEADLESS_ANGULAR_SCHEMA_VERSION,
            true,
        );
    }

    public function registerBlocks(): void
    {
        register_block_type(HEADLESS_ANGULAR_SCHEMA_PATH . 'blocks/hero');
        register_block_type(HEADLESS_ANGULAR_SCHEMA_PATH . 'blocks/featured-cards');
        register_block_type(HEADLESS_ANGULAR_SCHEMA_PATH . 'blocks/timeline');
        register_block_type(HEADLESS_ANGULAR_SCHEMA_PATH . 'blocks/tooltip');
    }

    public function registerRestRoutes(): void
    {
        (new Rest\PageController())->registerRoutes();
        (new Rest\NavigationController())->registerRoutes();
        (new Rest\FormController())->registerRoutes();
    }
}
