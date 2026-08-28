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
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
    }

    public function registerBlocks(): void
    {
        register_block_type(HEADLESS_ANGULAR_SCHEMA_PATH . 'blocks/hero');
    }

    public function registerRestRoutes(): void
    {
        (new Rest\PageController())->registerRoutes();
    }
}
