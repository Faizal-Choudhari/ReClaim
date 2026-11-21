<?php
header('Content-Type: text/plain');

echo "Upload max size: " . ini_get('upload_max_filesize') . "\n";
echo "POST max size: " . ini_get('post_max_size') . "\n";
echo "Memory limit: " . ini_get('memory_limit') . "\n";
echo "Loaded config: " . (php_ini_loaded_file() ?: 'None') . "\n";
echo "Scanned config files: " . (php_ini_scanned_files() ?: 'None') . "\n";
echo "PHP_INI_SCAN_DIR: " . (getenv('PHP_INI_SCAN_DIR') ?: 'Not set') . "\n";