<?php

/*
|--------------------------------------------------------------------------
| ربات ساز - نسخه اصلاح شده
|--------------------------------------------------------------------------
| PHP Telegram Bot Builder
| نسخه بازنویسی شده
|--------------------------------------------------------------------------
*/

error_reporting(0);
ini_set('display_errors', '0');
set_time_limit(0);

/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
| روی Render:
|
| BOT_TOKEN = توکن ربات مادر
| BASE_URL  = آدرس کامل سایت شما
|
| مثال:
| BOT_TOKEN=123456:ABC...
| BASE_URL=https://your-service.onrender.com
|--------------------------------------------------------------------------
*/

$BOT_TOKEN = getenv('BOT_TOKEN') ?: '';
$BASE_URL  = rtrim(getenv('BASE_URL') ?: '', '/');

/*
|--------------------------------------------------------------------------
| تنظیمات ربات
|--------------------------------------------------------------------------
*/

$channel  = getenv('CHANNEL_USERNAME') ?: '@Nim_Shab2';
$id_support = getenv('SUPPORT_USERNAME') ?: 'KHAN_Sohail_580';
$bot_id   = getenv('BOT_USERNAME') ?: 'Helper_Yaser_VIP_Bot';
$admin    = getenv('ADMIN_ID') ?: '8650091524';

define('API_KEY', $BOT_TOKEN);

if (API_KEY === '') {
    exit;
}

/*
|--------------------------------------------------------------------------
| مسیرها
|--------------------------------------------------------------------------
*/

$DATA_DIR  = __DIR__ . '/data';
$BOTS_DIR  = __DIR__ . '/bots';
$SOURCE_DIR = __DIR__ . '/source';
$CODE_DIR  = __DIR__ . '/code';

/*
|--------------------------------------------------------------------------
| ساخت پوشه‌های اصلی
|--------------------------------------------------------------------------
*/

function ensureDir($path)
{
    if (!is_dir($path)) {
        @mkdir($path, 0775, true);
    }
}

ensureDir($DATA_DIR);
ensureDir($BOTS_DIR);
ensureDir($SOURCE_DIR);
ensureDir($CODE_DIR);

/*
|--------------------------------------------------------------------------
| Telegram API
|--------------------------------------------------------------------------
*/

function yzi($method, $datas = [])
{
    $url = 'https://api.telegram.org/bot' . API_KEY . '/' . $method;

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $datas,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $res = curl_exec($ch);

    curl_close($ch);

    if ($res === false || $res === '') {
        return null;
    }

    $json = json_decode($res);

    return $json;
}

/*
|--------------------------------------------------------------------------
| ارسال پیام
|--------------------------------------------------------------------------
*/

function SendMessage(
    $chatid,
    $text,
    $parse_mode = 'HTML',
    $disable_web_page_preview = true,
    $keyboard = null
) {
    $data = [
        'chat_id' => $chatid,
        'text' => $text,
        'parse_mode' => $parse_mode,
        'disable_web_page_preview' => $disable_web_page_preview
    ];

    if ($keyboard !== null) {
        $data['reply_markup'] = $keyboard;
    }

    return yzi('sendMessage', $data);
}

/*
|--------------------------------------------------------------------------
| فوروارد
|--------------------------------------------------------------------------
*/

function ForwardMessage($to, $from, $message_id)
{
    return yzi('forwardMessage', [
        'chat_id' => $to,
        'from_chat_id' => $from,
        'message_id' => $message_id
    ]);
}

/*
|--------------------------------------------------------------------------
| حذف پوشه
|--------------------------------------------------------------------------
*/

function deleteFolder($path)
{
    if (!file_exists($path)) {
        return true;
    }

    if (is_file($path) || is_link($path)) {
        return @unlink($path);
    }

    $items = @scandir($path);

    if ($items === false) {
        return false;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        deleteFolder($path . DIRECTORY_SEPARATOR . $item);
    }

    return @rmdir($path);
}

/*
|--------------------------------------------------------------------------
| ذخیره فایل
|--------------------------------------------------------------------------
*/

function saveFile($filename, $data)
{
    $dir = dirname($filename);

    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    return @file_put_contents($filename, $data);
}

/*
|--------------------------------------------------------------------------
| خواندن فایل
|--------------------------------------------------------------------------
*/

function readFileSafe($filename, $default = '')
{
    if (!file_exists($filename)) {
        return $default;
    }

    $data = @file_get_contents($filename);

    return $data === false ? $default : trim($data);
}

/*
|--------------------------------------------------------------------------
| ساخت مسیر کاربر
|--------------------------------------------------------------------------
*/

function userDir($user_id)
{
    return __DIR__ . '/data/' . intval($user_id);
}

/*
|--------------------------------------------------------------------------
| ساخت اطلاعات اولیه کاربر
|--------------------------------------------------------------------------
*/

function initUser($user_id)
{
    $dir = userDir($user_id);

    ensureDir($dir);

    $defaults = [
        'state.txt'   => 'none',
        'command.txt' => 'none',
        'type.txt'    => 'Free',
        'create.txt'  => 'no',
        'gold.txt'    => '0',
        'bots.txt'    => '',
        'joins.txt'   => ''
    ];

    foreach ($defaults as $file => $value) {
        $path = $dir . '/' . $file;

        if (!file_exists($path)) {
            saveFile($path, $value);
        }
    }
}

/*
|--------------------------------------------------------------------------
| تنظیمات کاربر
|--------------------------------------------------------------------------
*/

function getUserValue($user_id, $file, $default = '')
{
    return readFileSafe(userDir($user_id) . '/' . $file, $default);
}

function setUserValue($user_id, $file, $value)
{
    return saveFile(userDir($user_id) . '/' . $file, $value);
}

/*
|--------------------------------------------------------------------------
| لیست خطوط
|--------------------------------------------------------------------------
*/

function getLines($file)
{
    $data = readFileSafe($file, '');

    if ($data === '') {
        return [];
    }

    $lines = preg_split('/\r\n|\r|\n/', $data);

    $result = [];

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line !== '') {
            $result[] = $line;
        }
    }

    return $result;
}

/*
|--------------------------------------------------------------------------
| افزودن به فایل لیست
|--------------------------------------------------------------------------
*/

function addLineUnique($file, $value)
{
    $value = trim($value);

    if ($value === '') {
        return false;
    }

    $lines = getLines($file);

    if (!in_array($value, $lines, true)) {
        $lines[] = $value;
        saveFile($file, implode("\n", $lines) . "\n");
    }

    return true;
}

/*
|--------------------------------------------------------------------------
| مالک ربات
|--------------------------------------------------------------------------
*/

function getBotOwner($username)
{
    return readFileSafe(
        __DIR__ . '/bots/' . $username . '/data/my_id.txt',
        ''
    );
}

/*
|--------------------------------------------------------------------------
| بررسی اینکه ربات متعلق به کاربر است
|--------------------------------------------------------------------------
*/

function userOwnsBot($user_id, $username)
{
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);

    if ($username === '') {
        return false;
    }

    $owner = getBotOwner($username);

    return (string)$owner === (string)$user_id;
}

/*
|--------------------------------------------------------------------------
| بررسی توکن
|--------------------------------------------------------------------------
*/

function getBotInfo($token)
{
    if (!preg_match('/^\d+:[A-Za-z0-9_-]+$/', trim($token))) {
        return null;
    }

    $url = 'https://api.telegram.org/bot' . trim($token) . '/getMe';

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($ch);

    curl_close($ch);

    if (!$response) {
        return null;
    }

    $result = json_decode($response, true);

    if (
        !is_array($result) ||
        empty($result['ok']) ||
        empty($result['result']['username'])
    ) {
        return null;
    }

    return $result['result'];
}

/*
|--------------------------------------------------------------------------
| ساخت Webhook
|--------------------------------------------------------------------------
*/

function setChildWebhook($token, $username)
{
    global $BASE_URL;

    if ($BASE_URL === '') {
        return false;
    }

    $webhook = $BASE_URL . '/bots/' . rawurlencode($username) . '/index.php';

    $url = 'https://api.telegram.org/bot' . $token . '/setWebhook';

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'url' => $webhook,
            'drop_pending_updates' => 'true'
        ],
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);

    curl_close($ch);

    return json_decode($response, true);
}

/*
|--------------------------------------------------------------------------
| ساخت ربات فرزند
|--------------------------------------------------------------------------
*/

function createChildBot($token, $admin_id, $type, $sourceName, $extraReplacements = [])
{
    global $BOTS_DIR, $SOURCE_DIR;

    $info = getBotInfo($token);

    if (!$info) {
        return [
            'ok' => false,
            'error' => 'توکن نامعتبر است.'
        ];
    }

    $username = $info['username'];

    if (!$username) {
        return [
            'ok' => false,
            'error' => 'این ربات Username ندارد.'
        ];
    }

    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);

    if ($username === '') {
        return [
            'ok' => false,
            'error' => 'Username ربات معتبر نیست.'
        ];
    }

    $botDir = $BOTS_DIR . '/' . $username;

    if (is_dir($botDir)) {
        return [
            'ok' => false,
            'error' => 'این ربات قبلاً در سیستم ساخته شده است.'
        ];
    }

    $sourceFile = $SOURCE_DIR . '/' . $sourceName . '/index.php';

    if (!file_exists($sourceFile)) {
        return [
            'ok' => false,
            'error' => 'فایل سورس این نوع ربات پیدا نشد: ' . $sourceName
        ];
    }

    ensureDir($botDir);
    ensureDir($botDir . '/data');
    ensureDir($botDir . '/other');
    ensureDir($botDir . '/other/' . intval($admin_id));
    ensureDir($botDir . '/other/setting');

    saveFile(
        $botDir . '/data/my_id.txt',
        $admin_id
    );

    saveFile(
        $botDir . '/data/bottype.txt',
        strtolower($type)
    );

    saveFile(
        $botDir . '/other/' . intval($admin_id) . '/my_id.txt',
        $admin_id
    );

    $source = file_get_contents($sourceFile);

    $replacements = [
        '[TOKEN]' => $token,
        '[ADMIN]' => $admin_id,
        '[USERBOT]' => $username,
        '[BOTUSER]' => $username
    ];

    foreach ($extraReplacements as $key => $value) {
        $replacements[$key] = $value;
    }

    $source = str_replace(
        array_keys($replacements),
        array_values($replacements),
        $source
    );

    saveFile(
        $botDir . '/index.php',
        $source
    );

    return [
        'ok' => true,
        'username' => $username,
        'info' => $info,
        'path' => $botDir
    ];
}

/*
|--------------------------------------------------------------------------
| منوی اصلی
|--------------------------------------------------------------------------
*/

$start = json_encode([
    'keyboard' => [
        [
            ['text' => '🌀ساخت ربات🌀']
        ],
        [
            ['text' => 'پشتیبانی🍭'],
            ['text' => 'حذف ربات🗂']
        ],
        [
            ['text' => 'کد رایگان🍫']
        ],
        [
            ['text' => 'حساب کاربری من👤'],
            ['text' => 'ربات های من🏄🏻']
        ],
        [
            ['text' => 'حساب ویژه👑'],
            ['text' => 'بات اینفو⚙️']
        ]
    ],
    'resize_keyboard' => true
], JSON_UNESCAPED_UNICODE);

/*
|--------------------------------------------------------------------------
| منوی ساخت ربات
|--------------------------------------------------------------------------
*/

$Create_b = json_encode([
    'keyboard' => [
        [
            ['text' => 'ست وب هوک🔩️'],
            ['text' => 'پیامرسان💬️']
        ],
        [
            ['text' => '️️مبدل فایل🤹‍♂️']
        ],
        [
            ['text' => 'جست و جوی موزیک🎙'],
            ['text' => 'فونت ساز🎭']
        ],
        [
            ['text' => 'بازی XO🎲'],
            ['text' => 'حرف ناشناس💍']
        ],
        [
            ['text' => 'بازگشت✖️️']
        ]
    ],
    'resize_keyboard' => true
], JSON_UNESCAPED_UNICODE);

/*
|--------------------------------------------------------------------------
| منوی بازگشت
|--------------------------------------------------------------------------
*/

$button_back = json_encode([
    'keyboard' => [
        [
            ['text' => 'بازگشت✖️️']
        ]
    ],
    'resize_keyboard' => true
], JSON_UNESCAPED_UNICODE);

/*
|--------------------------------------------------------------------------
| منوی اطلاعات
|--------------------------------------------------------------------------
*/

$bet_info = json_encode([
    'keyboard' => [
        [
            ['text' => 'زیرمجموعه گیری🔮'],
            ['text' => 'امتیاز من💎']
        ],
        [
            ['text' => 'ویژه کردن با زیرمجموعه💵'],
            ['text' => 'انتقال امتیاز🍭']
        ],
        [
            ['text' => 'بازگشت✖️️']
        ]
    ],
    'resize_keyboard' => true
], JSON_UNESCAPED_UNICODE);

/*
|--------------------------------------------------------------------------
| منوی ادمین
|--------------------------------------------------------------------------
*/

$button_manage = json_encode([
    'keyboard' => [
        [
            ['text' => 'ساخت ربات⚙️'],
            ['text' => 'حذف ربات🗂']
        ],
        [
            ['text' => '🎁ساخت کد']
        ],
        [
            ['text' => 'ویژه کردن🎉'],
            ['text' => '🔻ربات ها']
        ],
        [
            ['text' => 'حذف حساب ویژه❌']
        ],
        [
            ['text' => '💬فوروارد'],
            ['text' => '🎈آمار']
        ],
        [
            ['text' => 'امتیاز به کاربر💲'],
            ['text' => 'کم کردن امتیاز کاربر⚠️']
        ],
        [
            ['text' => 'بازگشت✖️️']
        ]
    ],
    'resize_keyboard' => true
], JSON_UNESCAPED_UNICODE);

/*
|--------------------------------------------------------------------------
| دریافت Update
|--------------------------------------------------------------------------
*/

$raw = file_get_contents('php://input');

if (!$raw) {
    exit;
}

$update = json_decode($raw);

if (!$update) {
    exit;
}

$message = $update->message ?? null;

if (!$message) {
    exit;
}

$from = $message->from ?? null;

if (!$from) {
    exit;
}

$from_id = (string)($from->id ?? '');
$chat_id = (string)($message->chat->id ?? $from_id);

$text = trim((string)($message->text ?? ''));

$first_name = (string)($from->first_name ?? '');
$last_name = (string)($from->last_name ?? '');
$username = (string)($from->username ?? '');

$message_id = (int)($message->message_id ?? 0);

if ($from_id === '') {
    exit;
}

/*
|--------------------------------------------------------------------------
| ساخت کاربر
|--------------------------------------------------------------------------
*/

initUser($from_id);

$state   = getUserValue($from_id, 'state.txt', 'none');
$created = getUserValue($from_id, 'create.txt', 'no');
$type    = getUserValue($from_id, 'type.txt', 'Free');
$gold    = (int)getUserValue($from_id, 'gold.txt', '0');
$user_bots = getUserValue($from_id, 'bots.txt', '');

$allBots = readFileSafe($DATA_DIR . '/bots.txt', '');

/*
|--------------------------------------------------------------------------
| بررسی عضویت کانال
|--------------------------------------------------------------------------
*/

$channelMember = yzi('getChatMember', [
    'chat_id' => $channel,
    'user_id' => $from_id
]);

$channelStatus = '';

if (
    $channelMember &&
    isset($channelMember->ok) &&
    $channelMember->ok &&
    isset($channelMember->result->status)
) {
    $channelStatus = $channelMember->result->status;
}

/*
|--------------------------------------------------------------------------
| Start
|--------------------------------------------------------------------------
*/

if (preg_match('/^\/start(?:\s+(.+))?$/u', $text, $match)) {

    setUserValue($from_id, 'state.txt', 'none');

    $referrer = isset($match[1]) ? trim($match[1]) : '';

    if (
        $referrer !== '' &&
        $referrer !== $from_id &&
        ctype_digit($referrer)
    ) {

        initUser($referrer);

        $joins = getUserValue($referrer, 'joins.txt', '');
        $joinList = preg_split('/\r\n|\r|\n/', $joins);

        if (!in_array($from_id, $joinList, true)) {

            $refGold = (int)getUserValue(
                $referrer,
                'gold.txt',
                '0'
            );

            setUserValue(
                $referrer,
                'gold.txt',
                $refGold + 1
            );

            addLineUnique(
                userDir($referrer) . '/joins.txt',
                $from_id
            );

            SendMessage(
                $referrer,
                '🎉 یک نفر با لینک شما وارد ربات شد و ۱ امتیاز دریافت کرد.'
            );
        }
    }

    if (
        $channelStatus !== 'member' &&
        $channelStatus !== 'creator' &&
        $channelStatus !== 'administrator'
    ) {

        SendMessage(
            $chat_id,
            "🔸 برای استفاده از ربات ابتدا عضو کانال زیر شوید:\n\n" .
            "🆔 {$channel}\n\n" .
            "بعد از عضویت دوباره /start را ارسال کنید.",
            'HTML',
            true
        );

        exit;
    }

    SendMessage(
        $chat_id,
        "سلام {$first_name} 👋🏻\n\n" .
        "💎 به ربات ساز ما خوش آمدید.\n\n" .
        "برای ساخت ربات دکمه «🌀ساخت ربات🌀» را بزنید.",
        'HTML',
        true,
        $start
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| اجبار عضویت
|--------------------------------------------------------------------------
*/

if (
    $channelStatus !== '' &&
    $channelStatus !== 'member' &&
    $channelStatus !== 'creator' &&
    $channelStatus !== 'administrator'
) {

    SendMessage(
        $chat_id,
        "🔸 برای استفاده از ربات ابتدا عضو کانال زیر شوید:\n\n" .
        "🆔 {$channel}\n\n" .
        "بعد از عضویت /start را ارسال کنید.",
        'HTML',
        true
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| بازگشت
|--------------------------------------------------------------------------
*/

if ($text === 'بازگشت✖️️') {

    setUserValue($from_id, 'state.txt', 'none');
    setUserValue($from_id, 'command.txt', 'none');

    SendMessage(
        $chat_id,
        "سلام {$first_name} 👋🏻\n\n" .
        "💎 به ربات ساز ما خوش آمدید.\n\n" .
        "برای ساخت ربات دکمه «🌀ساخت ربات🌀» را بزنید.",
        'HTML',
        true,
        $start
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| ساخت ربات
|--------------------------------------------------------------------------
*/

if ($text === '🌀ساخت ربات🌀') {

    SendMessage(
        $chat_id,
        '💎 یک نوع ربات انتخاب کنید:',
        'HTML',
        true,
        $Create_b
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| انتخاب نوع ربات
|--------------------------------------------------------------------------
*/

$botTypes = [

    'پیامرسان💬️' => [
        'state' => 'create_pm',
        'source' => 'pv',
        'title' => 'پیامرسان'
    ],

    'حرف ناشناس💍' => [
        'state' => 'create_harf',
        'source' => 'harfnashenas',
        'title' => 'حرف ناشناس'
    ],

    '️️مبدل فایل🤹‍♂️' => [
        'state' => 'create_File',
        'source' => 'File',
        'title' => 'تبدیل فایل'
    ],

    'بازی XO🎲' => [
        'state' => 'create_XO',
        'source' => 'XObot',
        'title' => 'ربات XO'
    ],

    'فونت ساز🎭' => [
        'state' => 'create_font',
        'source' => 'font',
        'title' => 'فونت ساز'
    ],

    'ست وب هوک🔩️' => [
        'state' => 'create_setwebhook',
        'source' => 'setwebhook',
        'title' => 'ست وب هوک'
    ],

    'جست و جوی موزیک🎙' => [
        'state' => 'create_music',
        'source' => 'music',
        'title' => 'جست و جوی موزیک'
    ]
];

if (isset($botTypes[$text])) {

    setUserValue(
        $from_id,
        'state.txt',
        $botTypes[$text]['state']
    );

    SendMessage(
        $chat_id,
        "🎯 توکن ربات را ارسال کنید:\n\n" .
        "توکن را از @BotFather دریافت کنید.",
        'HTML',
        true,
        $button_back
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| دریافت توکن و ساخت ربات
|--------------------------------------------------------------------------
*/

$currentCreateState = $state;

$createMap = [];

foreach ($botTypes as $button => $info) {
    $createMap[$info['state']] = $info;
}

if (isset($createMap[$currentCreateState]) && $text !== '') {

    $config = $createMap[$currentCreateState];

    if ($text === 'بازگشت✖️️') {
        exit;
    }

    $token = trim($text);

    if (!preg_match('/^\d+:[A-Za-z0-9_-]+$/', $token)) {

        SendMessage(
            $chat_id,
            '❌ فرمت توکن صحیح نیست.\n\nلطفاً توکن واقعی BotFather را ارسال کنید.',
            'HTML',
            true,
            $button_back
        );

        exit;
    }

    SendMessage(
        $chat_id,
        '⏳ در حال بررسی توکن و ساخت ربات...',
        'HTML',
        true
    );

    $result = createChildBot(
        $token,
        $from_id,
        ($type === 'Gold' ? 'gold' : 'free'),
        $config['source']
    );

    if (!$result['ok']) {

        SendMessage(
            $chat_id,
            '❌ ساخت ربات انجام نشد.\n\n' .
            htmlspecialchars($result['error']),
            'HTML',
            true,
            $button_back
        );

        exit;
    }

    $un = $result['username'];

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    */

    $webhookResult = setChildWebhook(
        $token,
        $un
    );

    /*
    |--------------------------------------------------------------------------
    | ثبت ربات
    |--------------------------------------------------------------------------
    */

    setUserValue(
        $from_id,
        'state.txt',
        'none'
    );

    setUserValue(
        $from_id,
        'create.txt',
        'yes'
    );

    addLineUnique(
        $DATA_DIR . '/bots.txt',
        $un
    );

    addLineUnique(
        userDir($from_id) . '/bots.txt',
        $un
    );

    /*
    |--------------------------------------------------------------------------
    | پیام به ادمین
    |--------------------------------------------------------------------------
    */

    $adminKeyboard = json_encode([
        'inline_keyboard' => [
            [
                [
                    'text' => '@' . $un,
                    'url' => 'https://t.me/' . $un
                ]
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

    SendMessage(
        $admin,
        "🤖 ربات جدید ساخته شد\n\n" .
        "👤 سازنده: {$from_id}\n" .
        "🤖 ربات: @{$un}\n" .
        "🛠 نوع: {$config['title']}",
        'HTML',
        true,
        $adminKeyboard
    );

    /*
    |--------------------------------------------------------------------------
    | پیام به کاربر
    |--------------------------------------------------------------------------
    */

    $userKeyboard = json_encode([
        'inline_keyboard' => [
            [
                [
                    'text' => '🚀 ورود به ربات',
                    'url' => 'https://t.me/' . $un
                ]
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

    $webhookOk = false;

    if (
        is_array($webhookResult) &&
        !empty($webhookResult['ok'])
    ) {
        $webhookOk = true;
    }

    $webhookText = $webhookOk
        ? "✅ Webhook نیز با موفقیت تنظیم شد."
        : "⚠️ ربات ساخته شد، اما Webhook تنظیم نشد. BASE_URL را در Environment Variables بررسی کنید.";

    SendMessage(
        $chat_id,
        "🎁 ربات شما با موفقیت ساخته شد!\n\n" .
        "🤖 آیدی ربات: @{$un}\n" .
        "🛠 نوع: {$config['title']}\n\n" .
        $webhookText,
        'HTML',
        true,
        $userKeyboard
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| ربات‌های من
|--------------------------------------------------------------------------
*/

if ($text === 'ربات های من🏄🏻') {

    $bots = getLines(
        userDir($from_id) . '/bots.txt'
    );

    if (count($bots) === 0) {

        SendMessage(
            $chat_id,
            '❌ شما هنوز هیچ رباتی نساخته‌اید.',
            'HTML',
            true,
            $start
        );

        exit;
    }

    $out = "🤖 <b>ربات‌های شما:</b>\n\n";

    foreach ($bots as $index => $bot) {
        $number = $index + 1;

        $out .= $number .
            ". <a href=\"https://t.me/" .
            htmlspecialchars($bot) .
            "\">@" .
            htmlspecialchars($bot) .
            "</a>\n";
    }

    SendMessage(
        $chat_id,
        $out,
        'HTML',
        true,
        $start
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| حذف ربات
|--------------------------------------------------------------------------
*/

if ($text === 'حذف ربات🗂') {

    $bots = getLines(
        userDir($from_id) . '/bots.txt'
    );

    if (count($bots) === 0) {

        SendMessage(
            $chat_id,
            '❌ شما هیچ رباتی برای حذف ندارید.',
            'HTML',
            true,
            $button_back
        );

        exit;
    }

    setUserValue(
        $from_id,
        'state.txt',
        'delete'
    );

    SendMessage(
        $chat_id,
        "♠️ آیدی ربات را بدون @ ارسال کنید.\n\n" .
        "مثال:\n" .
        "<code>MyBot</code>",
        'HTML',
        true,
        $button_back
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| اجرای حذف
|--------------------------------------------------------------------------
*/

if ($state === 'delete' && $text !== '') {

    $botUsername = preg_replace(
        '/[^a-zA-Z0-9_]/',
        '',
        $text
    );

    if ($botUsername === '') {

        SendMessage(
            $chat_id,
            '❌ آیدی ربات معتبر نیست.',
            'HTML',
            true,
            $button_back
        );

        exit;
    }

    if (
        (string)$from_id !== (string)$admin &&
        !userOwnsBot($from_id, $botUsername)
    ) {

        SendMessage(
            $chat_id,
            '🚫 این ربات متعلق به شما نیست.',
            'HTML',
            true,
            $button_back
        );

        exit;
    }

    $botPath = $BOTS_DIR . '/' . $botUsername;

    if (!is_dir($botPath)) {

        SendMessage(
            $chat_id,
            '❌ ربات مورد نظر پیدا نشد.',
            'HTML',
            true,
            $button_back
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | حذف Webhook
    |--------------------------------------------------------------------------
    */

    $botTokenFile = $botPath . '/data/token.txt';

    if (file_exists($botTokenFile)) {

        $childToken = readFileSafe(
            $botTokenFile,
            ''
        );

        if ($childToken !== '') {
            yzi('deleteWebhook', []);
        }
    }

    deleteFolder($botPath);

    /*
    |--------------------------------------------------------------------------
    | حذف از لیست کاربر
    |--------------------------------------------------------------------------
    */

    $bots = getLines(
        userDir($from_id) . '/bots.txt'
    );

    $newBots = [];

    foreach ($bots as $bot) {
        if ($bot !== $botUsername) {
            $newBots[] = $bot;
        }
    }

    saveFile(
        userDir($from_id) . '/bots.txt',
        count($newBots)
            ? implode("\n", $newBots) . "\n"
            : ''
    );

    setUserValue(
        $from_id,
        'state.txt',
        'none'
    );

    SendMessage(
        $chat_id,
        "✅ ربات <b>@{$botUsername}</b> با موفقیت حذف شد.",
        'HTML',
        true,
        $start
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| بات اینفو
|--------------------------------------------------------------------------
*/

if ($text === 'بات اینفو⚙️') {

    SendMessage(
        $chat_id,
        "🤖 <b>اطلاعات ربات ساز</b>\n\n" .
        "🛠 ساخت انواع ربات تلگرامی\n" .
        "🎁 ساخت ربات فرزند\n" .
        "💎 سیستم امتیاز\n" .
        "👑 سیستم ویژه\n" .
        "🔧 مدیریت Webhook\n\n" .
        "🌐 @{$bot_id}",
        'HTML',
        true,
        $bet_info
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| زیرمجموعه گیری
|--------------------------------------------------------------------------
*/

if ($text === 'زیرمجموعه گیری🔮') {

    SendMessage(
        $chat_id,
        "🔮 لینک دعوت شما:\n\n" .
        "https://t.me/{$bot_id}?start={$from_id}\n\n" .
        "🎁 هر کاربری که با لینک شما وارد شود، ۱ امتیاز دریافت می‌کنید.",
        'HTML',
        true,
        $bet_info
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| امتیاز من
|--------------------------------------------------------------------------
*/

if ($text === 'امتیاز من💎') {

    SendMessage(
        $chat_id,
        "💎 <b>امتیازات شما</b>\n\n" .
        "▪️ موجودی: <b>{$gold}</b> امتیاز\n" .
        "▪️ قیمت ویژه: <b>6</b> امتیاز",
        'HTML',
        true,
        $bet_info
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| حساب کاربری
|--------------------------------------------------------------------------
*/

if ($text === 'حساب کاربری من👤') {

    $displayUsername = $username !== ''
        ? '@' . $username
        : 'ندارد';

    $accountType = ucfirst(strtolower($type));

    SendMessage(
        $chat_id,
        "💠 <b>اطلاعات حساب شما</b>\n\n" .
        "📝 نام: {$first_name} {$last_name}\n\n" .
        "🔢 آیدی عددی: <code>{$from_id}</code>\n\n" .
        "🆔 آیدی: {$displayUsername}\n\n" .
        "🅰️ نوع حساب: {$accountType}\n\n" .
        "🎖 امتیاز: {$gold}",
        'HTML',
        true,
        $start
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| حساب ویژه
|--------------------------------------------------------------------------
*/

if ($text === 'حساب ویژه👑') {

    $paymentKeyboard = json_encode([
        'inline_keyboard' => [
            [
                [
                    'text' => '💳 پرداخت',
                    'url' => 'https://www.payping.ir/d/7cnA'
                ]
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

    SendMessage(
        $chat_id,
        "👑 <b>حساب ویژه</b>\n\n" .
        "💰 قیمت اکانت ویژه: 2000 تومان\n\n" .
        "برای پرداخت روی دکمه زیر بزنید.",
        'HTML',
        true,
        $paymentKeyboard
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| ویژه کردن با امتیاز
|--------------------------------------------------------------------------
*/

if ($text === 'ویژه کردن با زیرمجموعه💵') {

    if ($gold < 6) {

        SendMessage(
            $chat_id,
            '❌ امتیازات شما کافی نیست.\n\nبرای ویژه کردن ربات به ۶ امتیاز نیاز دارید.',
            'HTML',
            true,
            $bet_info
        );

        exit;
    }

    setUserValue(
        $from_id,
        'state.txt',
        'VIP12'
    );

    SendMessage(
        $chat_id,
        '🤖 آیدی ربات خود را بدون @ ارسال کنید.',
        'HTML',
        true,
        $button_back
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| اجرای ویژه کردن
|--------------------------------------------------------------------------
*/

if ($state === 'VIP12' && $text !== '') {

    $botUsername = preg_replace(
        '/[^a-zA-Z0-9_]/',
        '',
        $text
    );

    if (!userOwnsBot($from_id, $botUsername)) {

        setUserValue(
            $from_id,
            'state.txt',
            'none'
        );

        SendMessage(
            $chat_id,
            '❌ این ربات متعلق به شما نیست.',
            'HTML',
            true,
            $start
        );

        exit;
    }

    $botTypeFile =
        $BOTS_DIR . '/' .
        $botUsername .
        '/data/bottype.txt';

    $botType = readFileSafe(
        $botTypeFile,
        'free'
    );

    if (strtolower($botType) === 'gold') {

        setUserValue(
            $from_id,
            'state.txt',
            'none'
        );

        SendMessage(
            $chat_id,
            '😐 این ربات از قبل ویژه است.',
            'HTML',
            true,
            $start
        );

        exit;
    }

    saveFile(
        $botTypeFile,
        'gold'
    );

    setUserValue(
        $from_id,
        'type.txt',
        'Gold'
    );

    setUserValue(
        $from_id,
        'gold.txt',
        max(0, $gold - 6)
    );

    setUserValue(
        $from_id,
        'state.txt',
        'none'
    );

    SendMessage(
        $chat_id,
        "👑 ربات <b>@{$botUsername}</b> ویژه شد.\n\n" .
        "💎 ۶ امتیاز از حساب شما کم شد.",
        'HTML',
        true,
        $start
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| پشتیبانی
|--------------------------------------------------------------------------
*/

if ($text === 'پشتیبانی🍭') {

    setUserValue(
        $from_id,
        'state.txt',
        'mok'
    );

    SendMessage(
        $chat_id,
        "💬 پیام خود را ارسال کنید.\n\n" .
        "برای پایان گفتگو «بازگشت✖️️» را بزنید.",
        'HTML',
        true,
        $button_back
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| ارسال پیام به پشتیبانی
|--------------------------------------------------------------------------
*/

if ($state === 'mok' && $text !== '') {

    ForwardMessage(
        $admin,
        $from_id,
        $message_id
    );

    SendMessage(
        $chat_id,
        '✅ پیام شما برای مدیریت ارسال شد.',
        'HTML',
        true
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| پاسخ ادمین به کاربر
|--------------------------------------------------------------------------
*/

$replyTo = $message->reply_to_message ?? null;

if (
    (string)$from_id === (string)$admin &&
    $replyTo &&
    isset($replyTo->forward_from->id)
) {

    $targetUser = (string)$replyTo->forward_from->id;

    SendMessage(
        $targetUser,
        $text,
        'HTML',
        true
    );

    SendMessage(
        $chat_id,
        '✅ پیام برای کاربر ارسال شد.'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| انتقال امتیاز
|--------------------------------------------------------------------------
*/

if ($text === 'انتقال امتیاز🍭') {

    setUserValue(
        $from_id,
        'state.txt',
        'kodom'
    );

    SendMessage(
        $chat_id,
        '🔹 آیدی عددی کاربر گیرنده را ارسال کنید.',
        'HTML',
        true,
        $button_back
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| دریافت گیرنده
|--------------------------------------------------------------------------
*/

if ($state === 'kodom' && $text !== '') {

    if (!ctype_digit($text)) {

        SendMessage(
            $chat_id,
            '❌ آیدی عددی صحیح نیست.',
            'HTML',
            true,
            $button_back
        );

        exit;
    }

    $target = $text;

    if (!is_dir(userDir($target))) {

        setUserValue(
            $from_id,
            'state.txt',
            'none'
        );

        SendMessage(
            $chat_id,
            '❌ این کاربر در ربات عضو نیست.',
            'HTML',
            true,
            $start
        );

        exit;
    }

    setUserValue(
        $from_id,
        'kodom.txt',
        $target
    );

    setUserValue(
        $from_id,
        'state.txt',
        'ine'
    );

    SendMessage(
        $chat_id,
        '💎 تعداد امتیازی که می‌خواهید انتقال دهید را ارسال کنید.',
        'HTML',
        true,
        $button_back
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| انجام انتقال
|--------------------------------------------------------------------------
*/

if ($state === 'ine' && $text !== '') {

    if (!ctype_digit($text)) {

        SendMessage(
            $chat_id,
            '❌ فقط عدد وارد کنید.',
            'HTML',
            true,
            $button_back
        );

        exit;
    }

    $amount = (int)$text;
    $target = getUserValue(
        $from_id,
        'kodom.txt',
        ''
    );

    $senderGold = (int)getUserValue(
        $from_id,
        'gold.txt',
        '0'
    );

    $receiverGold = (int)getUserValue(
        $target,
        'gold.txt',
        '0'
    );

    if ($amount <= 0) {

        SendMessage(
            $chat_id,
            '❌ مقدار امتیاز صحیح نیست.',
            'HTML',
            true,
            $button_back
        );

        exit;
    }

    if ($senderGold < $amount) {

        setUserValue(
            $from_id,
            'state.txt',
            'none'
        );

        SendMessage(
            $chat_id,
            '❌ امتیاز کافی ندارید.',
            'HTML',
            true,
            $start
        );

        exit;
    }

    setUserValue(
        $from_id,
        'gold.txt',
        $senderGold - $amount
    );

    setUserValue(
        $target,
        'gold.txt',
        $receiverGold + $amount
    );

    setUserValue(
        $from_id,
        'state.txt',
        'none'
    );

    SendMessage(
        $chat_id,
        "✅ {$amount} امتیاز با موفقیت منتقل شد.",
        'HTML',
        true,
        $start
    );

    SendMessage(
        $target,
        "🎁 شما {$amount} امتیاز از کاربر دریافت کردید."
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| کد رایگان
|--------------------------------------------------------------------------
*/

if ($text === 'کد رایگان🍫') {

    setUserValue(
        $from_id,
        'state.txt',
        'code'
    );

    SendMessage(
        $chat_id,
        '🎟 کد را ارسال کنید.',
        'HTML',
        true,
        $button_back
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| بررسی کد
|--------------------------------------------------------------------------
*/

if ($state === 'code' && $text !== '') {

    $codeFile = $CODE_DIR . '/' . basename($text) . '.txt';

    if (!file_exists($codeFile)) {

        SendMessage(
            $chat_id,
            '❌ این کد وجود ندارد.',
            'HTML',
            true,
            $button_back
        );

        exit;
    }

    $codeStatus = readFileSafe(
        $codeFile,
        'true'
    );

    if ($codeStatus === 'used') {

        SendMessage(
            $chat_id,
            '❌ این کد قبلاً استفاده شده است.',
            'HTML',
            true,
            $button_back
        );

        exit;
    }

    setUserValue(
        $from_id,
        'state.txt',
        'code_free'
    );

    saveFile(
        $codeFile,
        'used'
    );

    SendMessage(
        $chat_id,
        "✅ کد معتبر است.\n\n" .
        "🤖 آیدی ربات خود را بدون @ ارسال کنید.",
        'HTML',
        true,
        $button_back
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| استفاده از کد رایگان
|--------------------------------------------------------------------------
*/

if ($state === 'code_free' && $text !== '') {

    $botUsername = preg_replace(
        '/[^a-zA-Z0-9_]/',
        '',
        $text
    );

    if (!userOwnsBot($from_id, $botUsername)) {

        setUserValue(
            $from_id,
            'state.txt',
            'none'
        );

        SendMessage(
            $chat_id,
            '❌ این ربات متعلق به شما نیست.',
            'HTML',
            true,
            $start
        );

        exit;
    }

    saveFile(
        $BOTS_DIR . '/' .
        $botUsername .
        '/data/bottype.txt',
        'gold'
    );

    setUserValue(
        $from_id,
        'type.txt',
        'Gold'
    );

    setUserValue(
        $from_id,
        'state.txt',
        'none'
    );

    SendMessage(
        $chat_id,
        "👑 ربات <b>@{$botUsername}</b> ویژه شد.",
        'HTML',
        true,
        $start
    );

    SendMessage(
        $channel,
        "🎟 <b>استفاده از کد رایگان</b>\n\n" .
        "👤 نام: {$first_name} {$last_name}\n" .
        "🆔 آیدی: {$from_id}\n" .
        "🤖 ربات: @{$botUsername}",
        'HTML'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| پنل ادمین
|--------------------------------------------------------------------------
*/

if (
    $text === '/panel' &&
    (string)$from_id === (string)$admin
) {

    SendMessage(
        $chat_id,
        '👑 به پنل مدیریت خوش آمدید.',
        'HTML',
        true,
        $button_manage
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| آمار
|--------------------------------------------------------------------------
*/

if (
    $text === '🎈آمار' &&
    (string)$from_id === (string)$admin
) {

    $members = getLines(
        $DATA_DIR . '/Member.txt'
    );

    $bots = getLines(
        $DATA_DIR . '/bots.txt'
    );

    SendMessage(
        $chat_id,
        "📊 <b>آمار ربات</b>\n\n" .
        "👥 کاربران: " . count($members) . "\n" .
        "🤖 ربات‌ها: " . count($bots),
        'HTML',
        true,
        $button_manage
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| لیست ربات‌ها
|--------------------------------------------------------------------------
*/

if (
    $text === '🔻ربات ها' &&
    (string)$from_id === (string)$admin
) {

    $bots = getLines(
        $DATA_DIR . '/bots.txt'
    );

    if (count($bots) === 0) {

        SendMessage(
            $chat_id,
            '❌ هنوز رباتی ساخته نشده است.',
            'HTML',
            true,
            $button_manage
        );

        exit;
    }

    $out = "🤖 <b>لیست ربات‌های ساخته شده:</b>\n\n";

    foreach ($bots as $i => $bot) {
        $out .= ($i + 1) .
            ". @" .
            htmlspecialchars($bot) .
            "\n";
    }

    SendMessage(
        $chat_id,
        $out,
        'HTML',
        true,
        $button_manage
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| ساخت کد
|--------------------------------------------------------------------------
*/

if (
    $text === '🎁ساخت کد' &&
    (string)$from_id === (string)$admin
) {

    setUserValue(
        $from_id,
        'state.txt',
        'CreateCode'
    );

    SendMessage(
        $chat_id,
        '🎟 کد مورد نظر را ارسال کنید.',
        'HTML',
        true,
        $button_back
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| ایجاد کد
|--------------------------------------------------------------------------
*/

if (
    $state === 'CreateCode' &&
    (string)$from_id === (string)$admin &&
    $text !== ''
) {

    $safeCode = preg_replace(
        '/[^a-zA-Z0-9_-]/',
        '',
        $text
    );

    if ($safeCode === '') {

        SendMessage(
            $chat_id,
            '❌ کد معتبر نیست.',
            'HTML',
            true,
            $button_back
        );

        exit;
    }

    saveFile(
        $CODE_DIR . '/' . $safeCode . '.txt',
        'unused'
    );

    setUserValue(
        $from_id,
        'state.txt',
        'none'
    );

    SendMessage(
        $chat_id,
        "✅ کد ساخته شد.\n\n" .
        "🎟 <code>{$safeCode}</code>",
        'HTML',
        true,
        $button_manage
    );

    SendMessage(
        $channel,
        "🎟 <b>کد جدید ساخته شد</b>\n\n" .
        "1️⃣ وارد ربات شوید.\n" .
        "2️⃣ گزینه کد رایگان را بزنید.\n" .
        "3️⃣ کد را وارد کنید.\n" .
        "4️⃣ آیدی ربات خود را وارد کنید.\n\n" .
        "🎟 Code: <code>{$safeCode}</code>\n" .
        "🤖 @{$bot_id}",
        'HTML'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| اضافه کردن امتیاز به کاربر
|--------------------------------------------------------------------------
*/

if (
    $text === 'امتیاز به کاربر💲' &&
    (string)$from_id === (string)$admin
) {

    setUserValue(
        $from_id,
        'state.txt',
        'be_kar'
    );

    SendMessage(
        $chat_id,
        '🔹 آیدی عددی کاربر را ارسال کنید.',
        'HTML',
        true,
        $button_back
    );

    exit;
}

if (
    $state === 'be_kar' &&
    (string)$from_id === (string)$admin
) {

    if (!ctype_digit($text)) {

        SendMessage(
            $chat_id,
            '❌ آیدی عددی صحیح نیست.',
            'HTML',
            true,
            $button_back
        );

        exit;
    }

    if (!is_dir(userDir($text))) {

        SendMessage(
            $chat_id,
            '❌ این کاربر وجود ندارد.',
            'HTML',
            true,
            $button_back
        );

        exit;
    }

    setUserValue(
        $from_id,
        'be_id.txt',
        $text
    );

    setUserValue(
        $from_id,
        'state.txt',
        'be_kar_ted'
    );

    SendMessage(
        $chat_id,
        '💎 تعداد امتیاز را ارسال کنید.',
        'HTML',
        true,
        $button_back
    );

    exit;
}

if (
    $state === 'be_kar_ted' &&
    (string)$from_id === (string)$admin
) {

    if (!ctype_digit($text)) {

        SendMessage(
            $chat_id,
            '❌ فقط عدد وارد کنید.',
            'HTML',
            true,
            $button_back
        );

        exit;
    }

    $target = getUserValue(
        $from_id,
        'be_id.txt',
        ''
    );

    $amount = (int)$text;

    $oldGold = (int)getUserValue(
        $target,
        'gold.txt',
        '0'
    );

    setUserValue(
        $target,
        'gold.txt',
        $oldGold + $amount
    );

    setUserValue(
        $from_id,
        'state.txt',
        'none'
    );

    SendMessage(
        $chat_id,
        "✅ {$amount} امتیاز به کاربر {$target} اضافه شد.",
        'HTML',
        true,
        $button_manage
    );

    SendMessage(
        $target,
        "🎁 {$amount} امتیاز توسط مدیریت به حساب شما اضافه شد."
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| کم کردن امتیاز کاربر
|--------------------------------------------------------------------------
*/

if (
    $text === 'کم کردن امتیاز کاربر⚠️' &&
    (string)$from_id === (string)$admin
) {

    setUserValue(
        $from_id,
        'state.txt',
        'kam_kar'
    );

    SendMessage(
        $chat_id,
        '🔹 آیدی عددی کاربر را ارسال کنید.',
        'HTML',
        true,
        $button_back
    );

    exit;
}

if (
    $state === 'kam_kar' &&
    (string)$from_id === (string)$admin
) {

    if (!ctype_digit($text)) {

        SendMessage(
            $chat_id,
            '❌ آیدی عددی صحیح نیست.',
            'HTML',
            true,
            $button_back
        );

        exit;
    }

    if (!is_dir(userDir($text))) {

        SendMessage(
            $chat_id,
            '❌ این کاربر وجود ندارد.',
            'HTML',
            true,
            $button_back
        );

        exit;
    }

    setUserValue(
        $from_id,
        'kam_id.txt',
        $text
    );

    setUserValue(
        $from_id,
        'state.txt',
        'kam_kar_ted'
    );

    SendMessage(
        $chat_id,
        '💎 مقدار امتیاز برای کم کردن را ارسال کنید.',
        'HTML',
        true,
        $button_back
    );

    exit;
}

if (
    $state === 'kam_kar_ted' &&
    (string)$from_id === (string)$admin
) {

    if (!ctype_digit($text)) {

        SendMessage(
            $chat_id,
            '❌ فقط عدد وارد کنید.',
            'HTML',
            true,
            $button_back
        );

        exit;
    }

    $target = getUserValue(
        $from_id,
        'kam_id.txt',
        ''
    );

    $amount = (int)$text;

    $oldGold = (int)getUserValue(
        $target,
        'gold.txt',
        '0'
    );

    $newGold = max(
        0,
        $oldGold - $amount
    );

    setUserValue(
        $target,
        'gold.txt',
        $newGold
    );

    setUserValue(
        $from_id,
        'state.txt',
        'none'
    );

    SendMessage(
        $chat_id,
        "✅ {$amount} امتیاز از کاربر {$target} کم شد.",
        'HTML',
        true,
        $button_manage
    );

    SendMessage(
        $target,
        "⚠️ {$amount} امتیاز از حساب شما کم شد.\n\n" .
        "💎 موجودی جدید: {$newGold}"
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| ویژه کردن توسط ادمین
|--------------------------------------------------------------------------
*/

if (
    $text === 'ویژه کردن🎉' &&
    (string)$from_id === (string)$admin
) {

    setUserValue(
        $from_id,
        'state.txt',
        'VIP'
    );

    SendMessage(
        $chat_id,
        '🤖 آیدی ربات را بدون @ ارسال کنید.',
        'HTML',
        true,
        $button_back
    );

    exit;
}

if (
    $state === 'VIP' &&
    (string)$from_id === (string)$admin
) {

    $botUsername = preg_replace(
        '/[^a-zA-Z0-9_]/',
        '',
        $text
    );

    $botPath =
        $BOTS_DIR . '/' .
        $botUsername;

    if (!is_dir($botPath)) {

        setUserValue(
            $from_id,
            'state.txt',
            'none'
        );

        SendMessage(
            $chat_id,
            '❌ ربات پیدا نشد.',
            'HTML',
            true,
            $button_manage
        );

        exit;
    }

    saveFile(
        $botPath . '/data/bottype.txt',
        'gold'
    );

    $owner = getBotOwner(
        $botUsername
    );

    if ($owner !== '') {
        setUserValue(
            $owner,
            'type.txt',
            'Gold'
        );
    }

    setUserValue(
        $from_id,
        'state.txt',
        'none'
    );

    SendMessage(
        $chat_id,
        "👑 ربات <b>@{$botUsername}</b> ویژه شد.",
        'HTML',
        true,
        $button_manage
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| حذف حساب ویژه
|--------------------------------------------------------------------------
*/

if (
    $text === 'حذف حساب ویژه❌' &&
    (string)$from_id === (string)$admin
) {

    setUserValue(
        $from_id,
        'state.txt',
        'delete_VIP'
    );

    SendMessage(
        $chat_id,
        '🤖 آیدی ربات را بدون @ ارسال کنید.',
        'HTML',
        true,
        $button_back
    );

    exit;
}

if (
    $state === 'delete_VIP' &&
    (string)$from_id === (string)$admin
) {

    $botUsername = preg_replace(
        '/[^a-zA-Z0-9_]/',
        '',
        $text
    );

    $botPath =
        $BOTS_DIR . '/' .
        $botUsername;

    if (!is_dir($botPath)) {

        setUserValue(
            $from_id,
            'state.txt',
            'none'
        );

        SendMessage(
            $chat_id,
            '❌ ربات پیدا نشد.',
            'HTML',
            true,
            $button_manage
        );

        exit;
    }

    saveFile(
        $botPath . '/data/bottype.txt',
        'free'
    );

    $owner = getBotOwner(
        $botUsername
    );

    if ($owner !== '') {

        setUserValue(
            $owner,
            'type.txt',
            'Free'
        );
    }

    setUserValue(
        $from_id,
        'state.txt',
        'none'
    );

    SendMessage(
        $chat_id,
        "✅ حساب ویژه ربات <b>@{$botUsername}</b> حذف شد.",
        'HTML',
        true,
        $button_manage
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| فوروارد همگانی توسط ادمین
|--------------------------------------------------------------------------
*/

$command = getUserValue(
    $from_id,
    'command.txt',
    'none'
);

if (
    $text === '💬فوروارد' &&
    (string)$from_id === (string)$admin
) {

    setUserValue(
        $from_id,
        'command.txt',
        's2a_fwd'
    );

    SendMessage(
        $chat_id,
        '📨 پیام مورد نظر را فوروارد کنید.',
        'HTML',
        true,
        $button_back
    );

    exit;
}

if (
    $command === 's2a_fwd' &&
    (string)$from_id === (string)$admin
) {

    setUserValue(
        $from_id,
        'command.txt',
        'none'
    );

    $members = getLines(
        $DATA_DIR . '/Member.txt'
    );

    $sent = 0;

    foreach ($members as $member) {

        if (!ctype_digit($member)) {
            continue;
        }

        ForwardMessage(
            $member,
            $admin,
            $message_id
        );

        $sent++;
    }

    SendMessage(
        $chat_id,
        "✅ پیام در صف ارسال قرار گرفت.\n\n" .
        "👥 تعداد کاربران: {$sent}",
        'HTML',
        true,
        $button_manage
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| ثبت کاربر
|--------------------------------------------------------------------------
*/

addLineUnique(
    $DATA_DIR . '/Member.txt',
    $chat_id
);

/*
|--------------------------------------------------------------------------
| پایان
|--------------------------------------------------------------------------
*/

exit;

?>