<?php

// Sigurohon nga ang storage ug cache pathways moadto sa read-writeable /tmp folder sa Vercel
$_ENV['APP_STORAGE'] = '/tmp/storage';

if (!is_dir('/tmp/storage/framework/views')) {
    mkdir('/tmp/storage/framework/views', 0755, true);
}
if (!is_dir('/tmp/storage/framework/cache')) {
    mkdir('/tmp/storage/framework/cache', 0755, true);
}
if (!is_dir('/tmp/storage/framework/sessions')) {
    mkdir('/tmp/storage/framework/sessions', 0755, true);
}
if (!is_dir('/tmp/storage/logs')) {
    mkdir('/tmp/storage/logs', 0755, true);
}

require __DIR__ . '/../public/index.php';