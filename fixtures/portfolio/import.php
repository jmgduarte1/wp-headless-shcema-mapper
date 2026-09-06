<?php
// Local, manual importer. Never register this file as a WordPress endpoint.
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    exit;
}
require '/var/www/html/wp-load.php';
$slugs = ['expertise', 'projects', 'experience', 'certifications'];
$before = [];
foreach ($slugs as $slug) {
    $post = get_page_by_path($slug, OBJECT, 'page');
    $before[$slug] = $post ? ['ID' => $post->ID, 'post_title' => $post->post_title, 'post_content' => $post->post_content, 'post_status' => $post->post_status] : null;
}
if (($argv[1] ?? '') !== '--apply') {
    foreach ($before as $slug => $post) {
        echo $slug . ': ' . ($post ? $post['ID'] . ' ' . $post['post_status'] . ' (' . strlen($post['post_content']) . ' bytes)' : 'not created') . PHP_EOL;
    }
    exit;
}
file_put_contents(__DIR__ . '/backup-' . gmdate('Ymd-His') . '.json', json_encode($before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
foreach (['projects', 'experience', 'certifications'] as $slug) {
    $content = file_get_contents(__DIR__ . '/' . $slug . '.html');
    $id = wp_insert_post(wp_slash([
        'ID' => $before[$slug]['ID'] ?? 0,
        'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug,
        'post_title' => ucfirst($slug), 'post_content' => $content,
    ]), true);
    if (is_wp_error($id)) {
        throw new RuntimeException($id->get_error_message());
    }
    echo $slug . ': published ' . $id . PHP_EOL;
}
$expertise = get_page_by_path('expertise', OBJECT, 'page');
if ($expertise) {
    $addition = json_decode(file_get_contents(__DIR__ . '/expertise-additions.json'), true, 512, JSON_THROW_ON_ERROR);
    $content = $expertise->post_content;
    $replacements = array_values($addition['panels']);
    $emptyCount = preg_match_all('/<!-- wp:paragraph -->\s*<p><\/p>\s*<!-- \/wp:paragraph -->/', $content);
    if ($emptyCount === 3) {
        $i = 0;
        $content = preg_replace_callback('/<!-- wp:paragraph -->\s*<p><\/p>\s*<!-- \/wp:paragraph -->/', function () use (&$i, $replacements) { return $replacements[$i++]; }, $content);
    } elseif ($emptyCount !== 0) {
        throw new RuntimeException('Unexpected empty Expertise blocks; review manually.');
    }
    $content = str_replace('Backend \\u0026amp; APIs', 'Backend \\u0026 APIs', $content);
    if (!str_contains($content, 'portfolio-cta')) {
        $content .= $addition['cta'];
    }
    $result = wp_update_post(wp_slash(['ID' => $expertise->ID, 'post_content' => $content]), true);
    if (is_wp_error($result)) {
        throw new RuntimeException($result->get_error_message());
    }
    echo 'expertise: completed ' . $result . PHP_EOL;
}
