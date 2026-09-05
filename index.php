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
    logLine('❌ API_ID یا API_HASH در فایل config.php تنظیم نشده است.');
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
        // تنظیمات API قبل از ساخت شیء اصلی
        $settings = new \danog\MadelineProto\Settings();
        $settings->getAppInfo()
            ->setApiId((int)$config['api_id'])
            ->setApiHash($config['api_hash']);

        $MadelineProto = new \danog\MadelineProto\API($config['session'], $settings);

        // شروع به کار ربات
        $MadelineProto->start();

        $me = $MadelineProto->getSelf();
        $name = $me['first_name'] ?? 'Unknown';
        $username = isset($me['username']) ? '@' . $me['username'] : 'بدون یوزرنیم';

        logLine("✅ ورود موفق: {$name} {$username}");
        logLine("🕐 ساعت فعال شد.");

        while (true) {
            try {
                $now = new DateTimeImmutable(
                    'now',
                    new DateTimeZone($config['timezone'])
                );

                // ساخت عکس ساعت
                makeClockImage(
                    $now,
                    $config['face'],
                    $config['generated']
                );

                // --- بخش حذف عکس قبلی ---
                $photos = $MadelineProto->photos->getUserPhotos(['user_id' => 'me', 'offset' => 0, 'limit' => 1]);
                if (isset($photos['photos']) && !empty($photos['photos'])) {
                    $oldPhoto = $photos['photos'][0];
                    $MadelineProto->photos->deletePhotos(['id' => [$oldPhoto]]);
                }
                // -----------------------

                // آپلود عکس جدید
                $MadelineProto->photos->uploadProfilePhoto(
                    file: $config['generated']
                );

                logLine(
                    '✅ عکس قدیمی حذف و پروفایل جدید ست شد: ' .
                    $now->format('H:i:s')
                );

                sleep($config['interval']);
            } catch (\Throwable $e) {
                logLine('❌ خطای حلقه: ' . $e->getMessage());
                sleep(30);
            }
        }
    } catch (\Throwable $e) {
        logLine('💥 خطای اصلی: ' . $e->getMessage());
        logLine('🔄 تلاش مجدد بعد از 30 ثانیه...');
        sleep(30);
    }
}
