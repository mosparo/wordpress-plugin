#!/bin/env php
<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Symfony\Component\Finder\Finder;

$vendorDir = realpath(__DIR__ . '/../vendor/');
$targetDir = realpath(__DIR__ . '/../vendor-prefixed');

$filesystem = new Filesystem(new Local('/'));
$finder = new Finder();
$finder->files()->in($vendorDir)->followLinks()->exclude(['vendor'])->name('/^.*licen.e.*/i');

foreach ($finder as $file) {
    $filePath = $file->getPathname();
    $targetFilePath = str_replace($vendorDir, $targetDir, $filePath);

    if (!file_exists(dirname($targetFilePath))) {
        // Skip, if the directory does not exist.
        continue;
    }

    if ($filesystem->has($targetFilePath)) {
        continue;
    }

    if (!$filesystem->has(dirname($targetFilePath))) {
        continue;
    }

    $filesystem->copy(
        $filePath,
        $targetFilePath
    );
}

