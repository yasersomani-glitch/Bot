<?php
declare(strict_types=1);

/*
 * Secrets را داخل GitHub نگذار.
 * در Render از Environment Variables استفاده کن:
 * API_ID
 * API_HASH
 *
 * مثال:
 * API_ID=12345678
 * API_HASH=xxxxxxxxxxxxxxxxxxxxxxxx
 */

return [
    'api_id' => (int) (getenv('API_ID') ?: 0),
    'api_hash' => (string) (getenv('API_HASH') ?: ''),
    'timezone' => (string) (getenv('TIMEZONE') ?: 'Asia/Kabul'),
    'interval' => max(30, (int) (getenv('UPDATE_INTERVAL') ?: 60)),

    // این مسیر باید روی Persistent Disk رندر باشد.
    'session' => (string) (getenv('SESSION_PATH') ?: '/var/data/telegram-clock/clock.madeline'),

    'face' => __DIR__ . '/clock_face.jpg',
    'generated' => '/var/data/telegram-clock/current_clock.jpg',
];
