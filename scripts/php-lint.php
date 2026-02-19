<?php

declare(strict_types=1);

$directories = ['app', 'bootstrap', 'config', 'database'];

foreach ($directories as $directory) {
    if (! is_dir($directory)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $command = sprintf('php -l %s', escapeshellarg($file->getPathname()));
        passthru($command, $exitCode);

        if ($exitCode !== 0) {
            exit($exitCode);
        }
    }
}
