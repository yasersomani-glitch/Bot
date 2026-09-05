<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

if (!is_dir('/var/data/telegram-clock')) {
    @mkdir('/var/data/telegram-clock', 0775, true);
}

if (!file_exists(__DIR__ . '/madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', __DIR__ . '/madeline.php');
}

require __DIR__ . '/madeline.php';
require __DIR__ . '/clock.php';

$config = require __DIR__ . '/config.php';

function logLine(string $message): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    echo $line;
    error_log(trim($line));
}

if ($config['api_id'] <= 0 || $config['api_hash'] === '') {
    logLine('❌ API_ID یا API_HASH تنظیم نشده است.');
    exit(1);
}

if (!is_file($config['face'])) {
    logLine('❌ clock_face.jpg پیدا نشد.');
    exit(1);
}

$sessionDir = dirname($config['session']);
if (!is_dir($sessionDir) && !mkdir($sessionDir, 0775, true) && !is_dir($sessionDir)) {
    logLine("❌ ساخت پوشه session ناموفق بود: {$sessionDir}");
    exit(1);
}

logLine('🚀 Telegram Clock PHP Userbot starting...');

while (true) {
    try {
        $MadelineProto = new \danog\MadelineProto\API($config['session']);

        $settings = new \danog\MadelineProto\Settings();
        $settings->getAppInfo()
            ->setApiId($config['api_id'])
            ->setApiHash($config['api_hash']);

        $MadelineProto->updateSettings($settings);

        // اگر session قبلاً ساخته شده باشد، بدون ورود دوباره وصل می‌شود.
        $MadelineProto->start();

        $me = $MadelineProto->getSelf();
        $name = $me['first_name'] ?? 'Unknown';
        $username = isset($me['username']) ? '@' . $me['username'] : 'بدون یوزرنیم';

        logLine("✅ ورود موفق: {$name} {$username}");
        logLine("🕐 ساعت آنالوگ فعال شد.");
        logLine("⏱️ فاصله آپدیت: {$config['interval']} ثانیه");

        while (true) {
            try {
                $now = new DateTimeImmutable(
                    'now',
                    new DateTimeZone($config['timezone'])
                );

                makeClockImage(
                    $now,
                    $config['face'],
                    $config['generated']
                );

                // photos.uploadProfilePhoto را MadelineProto مستقیماً پشتیبانی می‌کند.
                $MadelineProto->photos->uploadProfilePhoto(
                    file: $config['generated']
                );

                logLine(
                    '✅ پروفایل بروزرسانی شد: ' .
                    $now->format('Y-m-d H:i:s')
                );

                sleep($config['interval']);
            } catch (\Throwable $e) {
                logLine(
                    '❌ خطای داخل حلقه: ' .
                    get_class($e) . ' - ' . $e->getMessage()
                );

                // در صورت خطای موقت، برنامه نمی‌میرد.
                sleep(30);
            }
        }
    } catch (\Throwable $e) {
        logLine(
            '💥 خطای اصلی: ' .
            get_class($e) . ' - ' . $e->getMessage()
        );
        logLine('🔄 تلاش مجدد بعد از 30 ثانیه...');
        sleep(30);
    }
}
