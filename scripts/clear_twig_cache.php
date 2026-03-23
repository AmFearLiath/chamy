<?php
$dir = __DIR__ . '/../storage/cache/twig';
if (!is_dir($dir)) {
    echo "Twig cache directory not found: $dir\n";
    exit(1);
}
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
$deleted = 0;
foreach ($it as $file) {
    if ($file->isFile()) {
        @unlink($file->getPathname());
        $deleted++;
    }
}
echo "Cleared twig cache files: $deleted\n";
return 0;
