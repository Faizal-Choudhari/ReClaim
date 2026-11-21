<?php
header('Content-Type: text/plain');

echo "PHP Upload Settings\n";
echo "-------------------\n";
echo "Upload max size: " . ini_get('upload_max_filesize') . "\n";
echo "POST max size: " . ini_get('post_max_size') . "\n";
echo "Memory limit: " . ini_get('memory_limit') . "\n";
echo "Loaded config: " . php_ini_loaded_file() . "\n";