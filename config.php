<?php

// ==========================================
// تنظیمات ربات مادر
// ==========================================

define('BOT_TOKEN', 'TOKEN_ROBOT_MOTHER');
define('ADMIN_ID', 123456789);

// آدرس کامل bot.php روی سرور
// حتماً HTTPS باشد
define('BASE_URL', 'https://YOUR-DOMAIN.com/bot.php');

// دیتابیس SQLite
define('DB_FILE', __DIR__ . '/bot_factory.sqlite');

// منطقه زمانی
date_default_timezone_set('Asia/Kabul');

// زمان اتصال Telegram API
define('API_TIMEOUT', 30);

// اگر نمی‌خواهی Secret Webhook استفاده کنی خالی بگذار
define('WEBHOOK_SECRET', '');