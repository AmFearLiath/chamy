<?php
declare(strict_types=1);
$basePath = dirname(__DIR__);
require_once $basePath . '/vendor/autoload.php';
$kernel = Chamy\Core\Bootstrap::init($basePath);
$db = $kernel->db();
$prefix = $db->getPrefix();

// Fix seo_title entries that contain " – Elektro Keilitz" - remove the suffix
// because the template already appends " – {{ site_name }}"
$entries = $db->fetchAll("SELECT id, data FROM {$prefix}content_entries WHERE content_type = 'page'");
foreach ($entries as $entry) {
    $data = json_decode($entry['data'], true);
    if (isset($data['seo_title'])) {
        $data['seo_title'] = str_replace(' – Elektro Keilitz', '', $data['seo_title']);
        $db->query("UPDATE {$prefix}content_entries SET data = ? WHERE id = ?", [
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $entry['id'],
        ]);
        echo "Fixed page ID {$entry['id']}: seo_title = '{$data['seo_title']}'\n";
    }
}

// Also fix the home page: seo_title should be the full title (no site_name appended by template)
// Home uses a special title block so keep it intact - just remove if double
$homeEntry = $db->fetchAll("SELECT id, data FROM {$prefix}content_entries WHERE content_type = 'page'");
echo "Done.\n";
