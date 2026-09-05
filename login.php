<?php
declare(strict_types=1);

if (!is_dir('/var/data/telegram-clock')) {
    @mkdir('/var/data/telegram-clock', 0775, true);
}

if (!file_exists(__DIR__ . '/madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', __DIR__ . '/madeline.php');
}

require __DIR__ . '/madeline.php';

$config = require __DIR__ . '/config.php';

if ($config['api_id'] <= 0 || $config['api_hash'] === '') {
    fwrite(STDERR, "❌ API_ID و API_HASH را در Environment Variables تنظیم کن.\n");
    exit(1);
}

$sessionDir = dirname($config['session']);
if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0775, true);
}

echo "🔐 شروع ورود به Telegram...\n";
echo "اگر اولین بار است، شماره، کد و در صورت نیاز رمز 2FA پرسیده می‌شود.\n";

$MadelineProto = new \danog\MadelineProto\API($config['session']);

$settings = new \danog\MadelineProto\Settings();
$settings->getAppInfo()
    ->setApiId($config['api_id'])
    ->setApiHash($config['api_hash']);

$MadelineProto->updateSettings($settings);
$MadelineProto->start();

echo "✅ ورود کامل شد.\n";
echo "📁 Session ذخیره شد: {$config['session']}\n";
echo "حالا سرویس worker را اجرا کن.\n";
