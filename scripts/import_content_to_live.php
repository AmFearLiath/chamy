<?php

declare(strict_types=1);

/**
 * Importiert die Mock-Marketing-Content-Entries in die Live-Datenbank.
 * Bestehende Einträge mit gleicher UUID werden aktualisiert.
 */

use Chamy\Core\Bootstrap;

require_once __DIR__ . '/../vendor/autoload.php';

$kernel = Bootstrap::init(dirname(__DIR__));
$db     = $kernel->db();
$prefix = $db->getPrefix();

$entries = require dirname(__DIR__) . '/data/mock/content_entries.php';

echo "Importing " . count($entries) . " content entries into live database...\n";

$inserted = 0;
$updated  = 0;

foreach ($entries as $entry) {
    $uuid = $entry['uuid'];
    $existing = $db->fetchOne("SELECT id FROM {$prefix}content_entries WHERE uuid = :uuid", ['uuid' => $uuid]);

    if ($existing) {
        $db->getPdo()->prepare("
            UPDATE {$prefix}content_entries
            SET content_type = :ct, locale = :locale, status = :status, version = :version,
                data = :data, created_by = :cb, updated_by = :ub,
                created_at = :ca, updated_at = :ua
            WHERE uuid = :uuid
        ")->execute([
            'ct'     => $entry['content_type'],
            'locale' => $entry['locale'],
            'status' => $entry['status'],
            'version'=> $entry['version'],
            'data'   => $entry['data'],
            'cb'     => $entry['created_by'],
            'ub'     => $entry['updated_by'],
            'ca'     => $entry['created_at'],
            'ua'     => $entry['updated_at'],
            'uuid'   => $uuid,
        ]);
        echo "  Updated [{$entry['id']}] {$entry['content_type']}: {$uuid}\n";
        $updated++;
    } else {
        $db->getPdo()->prepare("
            INSERT INTO {$prefix}content_entries
                (uuid, content_type, locale, status, version, data, created_by, updated_by, created_at, updated_at)
            VALUES
                (:uuid, :ct, :locale, :status, :version, :data, :cb, :ub, :ca, :ua)
        ")->execute([
            'uuid'   => $uuid,
            'ct'     => $entry['content_type'],
            'locale' => $entry['locale'],
            'status' => $entry['status'],
            'version'=> $entry['version'],
            'data'   => $entry['data'],
            'cb'     => $entry['created_by'],
            'ub'     => $entry['updated_by'],
            'ca'     => $entry['created_at'],
            'ua'     => $entry['updated_at'],
        ]);
        echo "  Inserted [{$entry['id']}] {$entry['content_type']}: {$uuid}\n";
        $inserted++;
    }
}

echo "\nDone: {$inserted} inserted, {$updated} updated.\n";
