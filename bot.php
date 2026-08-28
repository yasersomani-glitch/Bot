<?php

require_once __DIR__ . '/database.php';

db();


// ==================================================
// Telegram API
// ==================================================

function telegram(
    $token,
    $method,
    $data = []
) {

    $url =
        'https://api.telegram.org/bot'
        . $token
        . '/'
        . $method;

    $ch = curl_init();

    curl_setopt_array(
        $ch,
        [
            CURLOPT_URL => $url,

            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_POST => true,

            CURLOPT_POSTFIELDS => $data,

            CURLOPT_CONNECTTIMEOUT => 10,

            CURLOPT_TIMEOUT => API_TIMEOUT
        ]
    );

    $result = curl_exec($ch);

    $error = curl_error($ch);

    curl_close($ch);

    if ($result === false) {

        return [
            'ok' => false,
            'description' => $error
        ];
    }

    $json = json_decode(
        $result,
        true
    );

    return is_array($json)
        ? $json
        : [
            'ok' => false,
            'description' => 'Invalid Telegram response'
        ];
}


// ==================================================
// ارسال پیام
// ==================================================

function sendMessage(
    $token,
    $chat_id,
    $text,
    $keyboard = null
) {

    $data = [

        'chat_id' => $chat_id,

        'text' => $text,

        'parse_mode' => 'HTML',

        'disable_web_page_preview' => true
    ];

    if ($keyboard !== null) {

        $data['reply_markup'] =
            json_encode(
                $keyboard,
                JSON_UNESCAPED_UNICODE
            );
    }

    return telegram(
        $token,
        'sendMessage',
        $data
    );
}


// ==================================================
// ویرایش پیام
// ==================================================

function editMessage(
    $token,
    $chat_id,
    $message_id,
    $text,
    $keyboard = null
) {

    $data = [

        'chat_id' => $chat_id,

        'message_id' => $message_id,

        'text' => $text,

        'parse_mode' => 'HTML'
    ];

    if ($keyboard !== null) {

        $data['reply_markup'] =
            json_encode(
                $keyboard,
                JSON_UNESCAPED_UNICODE
            );
    }

    return telegram(
        $token,
        'editMessageText',
        $data
    );
}


// ==================================================
// جواب Callback
// ==================================================

function answerCallback(
    $token,
    $id,
    $text = ''
) {

    return telegram(
        $token,
        'answerCallbackQuery',
        [
            'callback_query_id' => $id,

            'text' => $text
        ]
    );
}


// ==================================================
// پاک کردن HTML
// ==================================================

function safe($text)
{
    return htmlspecialchars(
        $text ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}


// ==================================================
// منوی مادر
// ==================================================

function motherKeyboard()
{
    return [

        'keyboard' => [

            [
                ['text' => '🤖 ساخت ربات'],
                ['text' => '📦 ربات‌های من']
            ],

            [
                ['text' => '📊 آمار'],
                ['text' => 'ℹ️ راهنما']
            ]

        ],

        'resize_keyboard' => true
    ];
}


// ==================================================
// انتخاب نوع ربات
// ==================================================

function typeKeyboard()
{
    return [

        'inline_keyboard' => [

            [
                [
                    'text' => '👥 ربات جذب ممبر',
                    'callback_data' => 'type_member'
                ]
            ],

            [
                [
                    'text' => '🎁 ربات قرعه‌کشی',
                    'callback_data' => 'type_lottery'
                ]
            ],

            [
                [
                    'text' => '🛒 ربات فروشگاهی',
                    'callback_data' => 'type_shop'
                ]
            ],

            [
                [
                    'text' => '📢 ربات تبلیغات',
                    'callback_data' => 'type_ads'
                ]
            ],

            [
                [
                    'text' => '🎫 ربات پشتیبانی',
                    'callback_data' => 'type_support'
                ]
            ]

        ]
    ];
}


// ==================================================
// نام نوع ربات
// ==================================================

function typeName($type)
{
    $names = [

        'member' =>
            '👥 ربات جذب ممبر',

        'lottery' =>
            '🎁 ربات قرعه‌کشی',

        'shop' =>
            '🛒 ربات فروشگاهی',

        'ads' =>
            '📢 ربات تبلیغات',

        'support' =>
            '🎫 ربات پشتیبانی'
    ];

    return $names[$type] ?? 'ربات';
}


// ==================================================
// منوی مدیریت ربات
// ==================================================

function childAdminKeyboard($id)
{
    return [

        'inline_keyboard' => [

            [
                [
                    'text' => '📊 آمار',
                    'callback_data' => 'stats_' . $id
                ],

                [
                    'text' => '⚙️ تنظیمات',
                    'callback_data' => 'settings_' . $id
                ]
            ],

            [
                [
                    'text' => '📋 اطلاعات',
                    'callback_data' => 'info_' . $id
                ]
            ],

            [
                [
                    'text' => '🔄 فعال / غیرفعال',
                    'callback_data' => 'toggle_' . $id
                ]
            ],

            [
                [
                    'text' => '🗑 حذف ربات',
                    'callback_data' => 'delete_' . $id
                ]
            ]

        ]
    ];
}


// ==================================================
// دکمه‌های کاربر ربات فرزند
// ==================================================

function childKeyboard($type)
{

    if ($type === 'member') {

        return [

            'keyboard' => [

                [
                    ['text' => '🔗 لینک دعوت من'],
                    ['text' => '👥 دعوت‌های من']
                ],

                [
                    ['text' => '💰 امتیاز من'],
                    ['text' => '🏆 برترین‌ها']
                ],

                [
                    ['text' => '📊 آمار من'],
                    ['text' => 'ℹ️ راهنما']
                ]

            ],

            'resize_keyboard' => true
        ];
    }


    if ($type === 'lottery') {

        return [

            'keyboard' => [

                [
                    ['text' => '🎟 قرعه‌کشی‌های فعال']
                ],

                [
                    ['text' => '🏆 برندگان'],
                    ['text' => 'ℹ️ راهنما']
                ]

            ],

            'resize_keyboard' => true
        ];
    }


    if ($type === 'shop') {

        return [

            'keyboard' => [

                [
                    ['text' => '🛍 محصولات'],
                    ['text' => '🛒 سبد خرید']
                ],

                [
                    ['text' => '📦 سفارش‌های من'],
                    ['text' => 'ℹ️ راهنما']
                ]

            ],

            'resize_keyboard' => true
        ];
    }


    if ($type === 'ads') {

        return [

            'keyboard' => [

                [
                    ['text' => '📢 ثبت تبلیغ'],
                    ['text' => '📋 تبلیغات من']
                ],

                [
                    ['text' => 'ℹ️ راهنما']
                ]

            ],

            'resize_keyboard' => true
        ];
    }


    return [

        'keyboard' => [

            [
                ['text' => '🎫 تیکت جدید'],
                ['text' => '📋 تیکت‌های من']
            ],

            [
                ['text' => 'ℹ️ راهنما']
            ]

        ],

        'resize_keyboard' => true
    ];
}


// ==================================================
// Webhook ربات فرزند
// ==================================================

function setChildWebhook($child)
{

    $url =
        BASE_URL
        . '?bot='
        . $child['id'];

    $data = [
        'url' => $url
    ];

    if (WEBHOOK_SECRET !== '') {

        $data['secret_token'] =
            WEBHOOK_SECRET;
    }

    return telegram(
        $child['token'],
        'setWebhook',
        $data
    );
}


// ==================================================
// بررسی توکن
// ==================================================

function checkBotToken($token)
{

    $result =
        telegram(
            $token,
            'getMe'
        );

    if (
        !($result['ok'] ?? false)
        ||
        empty($result['result']['id'])
    ) {

        return null;
    }

    return $result['result'];
}


// ==================================================
// شروع سیستم
// ==================================================

$update = json_decode(
    file_get_contents('php://input'),
    true
);


// ==================================================
// اگر مستقیماً صفحه باز شد
// ==================================================

if (!$update) {

    echo 'Bot Factory is running';

    exit;
}


// ==================================================
// تشخیص ربات فرزند
// ==================================================

$child_id =
    isset($_GET['bot'])
    ? (int)$_GET['bot']
    : 0;


$child =
    $child_id
    ? getChildBot($child_id)
    : null;


// ==================================================
// اگر ربات فرزند بود
// ==================================================

if ($child) {

    handleChildBot(
        $child,
        $update
    );

    exit;
}


// ==================================================
// در غیر این صورت ربات مادر
// ==================================================

handleMotherBot(
    $update
);

exit;


// ==================================================
// ربات مادر
// ==================================================

function handleMotherBot($update)
{

    $token = BOT_TOKEN;


    // ------------------------------------------
    // Callback
    // ------------------------------------------

    if (
        isset(
            $update['callback_query']
        )
    ) {

        motherCallback(
            $update['callback_query'],
            $token
        );

        return;
    }


    $message =
        $update['message']
        ?? null;


    if (!$message) {
        return;
    }


    $chat_id =
        (int)$message['chat']['id'];


    $from =
        $message['from']
        ?? [];


    $user_id =
        (int)($from['id'] ?? 0);


    $first_name =
        $from['first_name']
        ?? 'کاربر';


    $username =
        $from['username']
        ?? '';


    $text =
        trim(
            $message['text']
            ?? ''
        );


    // ثبت کاربر

    $user =
        saveMotherUser(
            $user_id,
            $first_name,
            $username
        );


    // ------------------------------------------
    // Start
    // ------------------------------------------

    if ($text === '/start') {

        clearMotherState(
            $user_id
        );

        sendMessage(
            $token,
            $chat_id,

            "👋 <b>سلام "
            . safe($first_name)
            . "!</b>\n\n"

            . "🤖 به ربات‌ساز خوش آمدی.\n\n"

            . "از اینجا می‌توانی چند ربات فرزند بسازی و هر ربات پنل و دکمه‌های اختصاصی خودش را داشته باشد.",

            motherKeyboard()
        );

        return;
    }


    // ------------------------------------------
    // ساخت ربات
    // ------------------------------------------

    if ($text === '🤖 ساخت ربات') {

        setMotherState(
            $user_id,
            'choose_type'
        );

        sendMessage(
            $token,
            $chat_id,

            "🤖 <b>ساخت ربات فرزند</b>\n\n"
            . "نوع رباتی که می‌خواهی بسازی انتخاب کن:",

            typeKeyboard()
        );

        return;
    }


    // ------------------------------------------
    // ربات‌های من
    // ------------------------------------------

    if ($text === '📦 ربات‌های من') {

        showOwnerBots(
            $user_id,
            $chat_id,
            $token
        );

        return;
    }


    // ------------------------------------------
    // آمار
    // ------------------------------------------

    if ($text === '📊 آمار') {

        $bots =
            getOwnerBots(
                $user_id
            );

        sendMessage(
            $token,
            $chat_id,

            "📊 <b>آمار شما</b>\n\n"
            . "🤖 تعداد ربات‌ها: <b>"
            . count($bots)
            . "</b>"
        );

        return;
    }


    // ------------------------------------------
    // راهنما
    // ------------------------------------------

    if ($text === 'ℹ️ راهنما') {

        sendMessage(
            $token,
            $chat_id,

            "ℹ️ <b>راهنما</b>\n\n"

            . "🤖 ساخت ربات\n"
            . "نوع ربات را انتخاب کن.\n\n"

            . "🔑 سپس توکن رباتی که از @BotFather ساخته‌ای را ارسال کن.\n\n"

            . "⚙️ بعد از ساخت، پنل مدیریت اختصاصی همان ربات را خواهی داشت."
        );

        return;
    }


    // ------------------------------------------
    // انتظار توکن
    // ------------------------------------------

    if (
        $user['state']
        === 'waiting_token'
    ) {

        $state_data =
            json_decode(
                $user['state_data'] ?? '{}',
                true
            );


        $type =
            $state_data['type']
            ?? 'member';


        $bot_token =
            trim($text);


        if (
            $bot_token === ''
            ||
            strpos(
                $bot_token,
                ':'
            ) === false
        ) {

            sendMessage(
                $token,
                $chat_id,

                "❌ <b>توکن نامعتبر است.</b>\n\n"
                . "توکن را دقیقاً از @BotFather کپی کن."
            );

            return;
        }


        // بررسی توکن

        $bot_info =
            checkBotToken(
                $bot_token
            );


        if (!$bot_info) {

            sendMessage(
                $token,
                $chat_id,

                "❌ <b>توکن صحیح نیست.</b>\n\n"
                . "دوباره توکن را ارسال کن."
            );

            return;
        }


        try {

            $new_id =
                createChildBot(
                    $user_id,
                    $bot_token,
                    $bot_info,
                    $type
                );


            $new_child =
                getChildBot(
                    $new_id
                );


            // Webhook

            $webhook =
                setChildWebhook(
                    $new_child
                );


            if (
                !($webhook['ok'] ?? false)
            ) {

                deleteChildBot(
                    $new_id,
                    $user_id
                );

                sendMessage(
                    $token,
                    $chat_id,

                    "❌ <b>Webhook تنظیم نشد.</b>\n\n"
                    . "آدرس BASE_URL را در config.php بررسی کن."
                );

                return;
            }


            clearMotherState(
                $user_id
            );


            sendMessage(
                $token,
                $chat_id,

                "✅ <b>ربات با موفقیت ساخته شد!</b>\n\n"

                . "🤖 نام: <b>"
                . safe(
                    $bot_info['first_name']
                    ?? ''
                )
                . "</b>\n"

                . "🔹 یوزرنیم: @"
                . safe(
                    $bot_info['username']
                    ?? ''
                )
                . "\n"

                . "📌 نوع: <b>"
                . safe(
                    typeName($type)
                )
                . "</b>\n\n"

                . "🔗 Webhook با موفقیت تنظیم شد."
            );

        } catch (Throwable $e) {

            clearMotherState(
                $user_id
            );

            sendMessage(
                $token,
                $chat_id,

                "❌ <b>ساخت ربات انجام نشد.</b>\n\n"
                . "ممکن است این توکن قبلاً ثبت شده باشد."
            );
        }

        return;
    }


    sendMessage(
        $token,
        $chat_id,

        "❓ از منوی اصلی استفاده کن.",

        motherKeyboard()
    );
}


// ==================================================
// Callback مادر
// ==================================================

function motherCallback(
    $callback,
    $token
)
{

    $user_id =
        (int)$callback['from']['id'];


    $chat_id =
        (int)$callback['message']['chat']['id'];


    $message_id =
        (int)$callback['message']['message_id'];


    $data =
        $callback['data']
        ?? '';


    answerCallback(
        $token,
        $callback['id']
    );


    // ------------------------------------------
    // نوع ربات
    // ------------------------------------------

    if (
        strpos(
            $data,
            'type_'
        ) === 0
    ) {

        $type =
            substr(
                $data,
                5
            );


        $allowed = [
            'member',
            'lottery',
            'shop',
            'ads',
            'support'
        ];


        if (
            !in_array(
                $type,
                $allowed,
                true
            )
        ) {
            return;
        }


        setMotherState(
            $user_id,
            'waiting_token',
            [
                'type' => $type
            ]
        );


        editMessage(
            $token,
            $chat_id,
            $message_id,

            "🔑 <b>توکن ربات را ارسال کن</b>\n\n"

            . "نوع انتخاب‌شده:\n"
            . "<b>"
            . safe(
                typeName($type)
            )
            . "</b>\n\n"

            . "توکن را از @BotFather کپی کن و همینجا بفرست."
        );

        return;
    }


    // ------------------------------------------
    // پنل
    // ------------------------------------------

    if (
        strpos(
            $data,
            'panel_'
        ) === 0
    ) {

        $id =
            (int)substr(
                $data,
                6
            );


        $child =
            getChildBot($id);


        if (
            !$child
            ||
            (int)$child['owner_id']
            !== $user_id
        ) {
            return;
        }


        editMessage(
            $token,
            $chat_id,
            $message_id,

            "👑 <b>پنل مدیریت ربات</b>\n\n"

            . "🤖 @"
            . safe(
                $child['username']
            )
            . "\n"

            . "📌 نوع: "
            . safe(
                typeName(
                    $child['type']
                )
            ),

            childAdminKeyboard(
                $id
            )
        );

        return;
    }


    // ------------------------------------------
    // آمار
    // ------------------------------------------

    if (
        strpos(
            $data,
            'stats_'
        ) === 0
    ) {

        $id =
            (int)substr(
                $data,
                6
            );


        $child =
            getChildBot($id);


        if (
            !$child
            ||
            (int)$child['owner_id']
            !== $user_id
        ) {
            return;
        }


        $count =
            childUserCount(
                $id
            );


        editMessage(
            $token,
            $chat_id,
            $message_id,

            "📊 <b>آمار ربات</b>\n\n"

            . "🤖 @"
            . safe(
                $child['username']
            )
            . "\n\n"

            . "👥 کاربران: <b>"
            . $count
            . "</b>\n"

            . "🔘 وضعیت: <b>"
            . (
                $child['enabled']
                ? 'فعال'
                : 'غیرفعال'
            )
            . "</b>",

            childAdminKeyboard(
                $id
            )
        );

        return;
    }


    // ------------------------------------------
    // تنظیمات
    // ------------------------------------------

    if (
        strpos(
            $data,
            'settings_'
        ) === 0
    ) {

        $id =
            (int)substr(
                $data,
                9
            );


        $child =
            getChildBot($id);


        if (
            !$child
            ||
            (int)$child['owner_id']
            !== $user_id
        ) {
            return;
        }


        editMessage(
            $token,
            $chat_id,
            $message_id,

            "⚙️ <b>تنظیمات اختصاصی</b>\n\n"

            . "🤖 @"
            . safe(
                $child['username']
            )
            . "\n"

            . "📌 نوع: "
            . safe(
                typeName(
                    $child['type']
                )
            )
            . "\n\n"

            . "این پنل بر اساس نوع ربات در ادامه قابل گسترش است.",

            childAdminKeyboard(
                $id
            )
        );

        return;
    }


    // ------------------------------------------
    // اطلاعات
    // ------------------------------------------

    if (
        strpos(
            $data,
            'info_'
        ) === 0
    ) {

        $id =
            (int)substr(
                $data,
                5
            );


        $child =
            getChildBot($id);


        if (
            !$child
            ||
            (int)$child['owner_id']
            !== $user_id
        ) {
            return;
        }


        editMessage(
            $token,
            $chat_id,
            $message_id,

            "📋 <b>اطلاعات ربات</b>\n\n"

            . "🆔 ID داخلی: <code>"
            . $child['id']
            . "</code>\n"

            . "🤖 @"
            . safe(
                $child['username']
            )
            . "\n"

            . "📌 نوع: "
            . safe(
                typeName(
                    $child['type']
                )
            )
            . "\n"

            . "🔘 وضعیت: "
            . (
                $child['enabled']
                ? '🟢 فعال'
                : '🔴 غیرفعال'
            ),

            childAdminKeyboard(
                $id
            )
        );

        return;
    }


    // ------------------------------------------
    // فعال / غیرفعال
    // ------------------------------------------

    if (
        strpos(
            $data,
            'toggle_'
        ) === 0
    ) {

        $id =
            (int)substr(
                $data,
                7
            );


        $child =
            getChildBot($id);


        if (
            !$child
            ||
            (int)$child['owner_id']
            !== $user_id
        ) {
            return;
        }


        $new =
            $child['enabled']
            ? 0
            : 1;


        $stmt =
            db()->prepare("
                UPDATE child_bots

                SET enabled = ?

                WHERE id = ?
            ");


        $stmt->execute([
            $new,
            $id
        ]);


        editMessage(
            $token,
            $chat_id,
            $message_id,

            "🔄 وضعیت ربات تغییر کرد.\n\n"

            . "وضعیت فعلی: <b>"
            . (
                $new
                ? '🟢 فعال'
                : '🔴 غیرفعال'
            )
            . "</b>",

            childAdminKeyboard(
                $id
            )
        );

        return;
    }


    // ------------------------------------------
    // حذف
    // ------------------------------------------

    if (
        strpos(
            $data,
            'delete_'
        ) === 0
    ) {

        $id =
            (int)substr(
                $data,
                7
            );


        $child =
            getChildBot($id);


        if (
            !$child
            ||
            (int)$child['owner_id']
            !== $user_id
        ) {
            return;
        }


        telegram(
            $child['token'],
            'deleteWebhook'
        );


        deleteChildBot(
            $id,
            $user_id
        );


        editMessage(
            $token,
            $chat_id,
            $message_id,

            "🗑 <b>ربات فرزند حذف شد.</b>"
        );

        return;
    }
}


// ==================================================
// نمایش ربات های مالک
// ==================================================

function showOwnerBots(
    $user_id,
    $chat_id,
    $token
)
{

    $bots =
        getOwnerBots(
            $user_id
        );


    if (!$bots) {

        sendMessage(
            $token,
            $chat_id,

            "📦 <b>هنوز رباتی نداری.</b>\n\n"
            . "برای ساخت اولین ربات روی «🤖 ساخت ربات» بزن."
        );

        return;
    }


    $keyboard = [
        'inline_keyboard' => []
    ];


    foreach ($bots as $bot) {

        $status =
            $bot['enabled']
            ? '🟢'
            : '🔴';


        $keyboard['inline_keyboard'][] = [

            [
                'text' =>
                    $status
                    . ' @'
                    . $bot['username'],

                'callback_data' =>
                    'panel_'
                    . $bot['id']
            ]

        ];
    }


    sendMessage(
        $token,
        $chat_id,

        "📦 <b>ربات‌های شما</b>\n\n"
        . "یکی از ربات‌ها را انتخاب کن:",

        $keyboard
    );
}


// ==================================================
// ربات فرزند
// ==================================================

function handleChildBot(
    $child,
    $update
)
{

    if (
        !(int)$child['enabled']
    ) {
        return;
    }


    $token =
        $child['token'];


    // ------------------------------------------
    // Callback
    // ------------------------------------------

    if (
        isset(
            $update['callback_query']
        )
    ) {

        answerCallback(
            $token,
            $update['callback_query']['id']
        );

        return;
    }


    $message =
        $update['message']
        ?? null;


    if (!$message) {
        return;
    }


    $chat_id =
        (int)$message['chat']['id'];


    $from =
        $message['from']
        ?? [];


    $user_id =
        (int)($from['id'] ?? 0);


    $first_name =
        $from['first_name']
        ?? 'کاربر';


    $username =
        $from['username']
        ?? '';


    $text =
        trim(
            $message['text']
            ?? ''
        );


    $bot_id =
        (int)$child['id'];


    // ------------------------------------------
    // Referral
    // ------------------------------------------

    $referrer = null;


    if (
        preg_match(
            '/^\/start(?:\s+(\d+))?/',
            $text,
            $match
        )
    ) {

        if (
            isset(
                $match[1]
            )
        ) {

            $referrer =
                (int)$match[1];
        }
    }


    // ------------------------------------------
    // کاربر
    // ------------------------------------------

    $old =
        getChildUser(
            $bot_id,
            $user_id
        );


    if (!$old) {

        $validRef =
            (
                $referrer
                &&
                $referrer !== $user_id
            )
            ? $referrer
            : null;


        createChildUser(
            $bot_id,
            $user_id,
            $first_name,
            $username,
            $validRef
        );


        if (
            $validRef
            &&
            getChildUser(
                $bot_id,
                $validRef
            )
        ) {

            addReferral(
                $bot_id,
                $validRef
            );


            sendMessage(
                $token,
                $validRef,

                "🎉 <b>یک نفر با لینک دعوت شما وارد شد!</b>\n\n"
                . "💰 +1 امتیاز"
            );
        }

    } else {

        createChildUser(
            $bot_id,
            $user_id,
            $first_name,
            $username
        );
    }


    $user =
        getChildUser(
            $bot_id,
            $user_id
        );


    // ------------------------------------------
    // بن
    // ------------------------------------------

    if (
        $user
        &&
        (int)$user['banned']
    ) {

        sendMessage(
            $token,
            $chat_id,

            "🚫 حساب شما مسدود شده است."
        );

        return;
    }


    // ------------------------------------------
    // Start
    // ------------------------------------------

    if (
        $text === '/start'
        ||
        strpos(
            $text,
            '/start '
        ) === 0
    ) {

        $title =
            $child['title']
            ?: typeName(
                $child['type']
            );


        sendMessage(
            $token,
            $chat_id,

            "👋 <b>به "
            . safe($title)
            . " خوش آمدی!</b>\n\n"

            . "از منوی زیر استفاده کن.",

            childKeyboard(
                $child['type']
            )
        );

        return;
    }


    // ------------------------------------------
    // ربات جذب ممبر
    // ------------------------------------------

    if (
        $child['type']
        === 'member'
    ) {

        handleMemberBot(
            $child,
            $user,
            $chat_id,
            $text
        );

        return;
    }


    // ------------------------------------------
    // قرعه کشی
    // ------------------------------------------

    if (
        $child['type']
        === 'lottery'
    ) {

        sendMessage(
            $token,
            $chat_id,

            "🎁 <b>ربات قرعه‌کشی</b>\n\n"
            . "بخش قرعه‌کشی اختصاصی این ربات آماده توسعه است.",

            childKeyboard('lottery')
        );

        return;
    }


    // ------------------------------------------
    // فروشگاه
    // ------------------------------------------

    if (
        $child['type']
        === 'shop'
    ) {

        sendMessage(
            $token,
            $chat_id,

            "🛒 <b>فروشگاه</b>\n\n"
            . "محصولات و سفارش‌های این ربات از پنل اختصاصی مدیریت خواهند شد.",

            childKeyboard('shop')
        );

        return;
    }


    // ------------------------------------------
    // تبلیغات
    // ------------------------------------------

    if (
        $child['type']
        === 'ads'
    ) {

        sendMessage(
            $token,
            $chat_id,

            "📢 <b>ربات تبلیغات</b>\n\n"
            . "ثبت و مدیریت تبلیغات از پنل اختصاصی انجام خواهد شد.",

            childKeyboard('ads')
        );

        return;
    }


    // ------------------------------------------
    // پشتیبانی
    // ------------------------------------------

    sendMessage(
        $token,
        $chat_id,

        "🎫 <b>ربات پشتیبانی</b>\n\n"
        . "سیستم تیکت اختصاصی این ربات آماده توسعه است.",

        childKeyboard('support')
    );
}


// ==================================================
// ربات جذب ممبر
// ==================================================

function handleMemberBot(
    $child,
    $user,
    $chat_id,
    $text
)
{

    $token =
        $child['token'];


    $bot_id =
        (int)$child['id'];


    $user_id =
        (int)$user['telegram_id'];


    // ------------------------------------------
    // لینک دعوت
    // ------------------------------------------

    if (
        $text
        === '🔗 لینک دعوت من'
    ) {

        $link =
            'https://t.me/'
            . $child['username']
            . '?start='
            . $user_id;


        sendMessage(
            $token,
            $chat_id,

            "🔗 <b>لینک دعوت اختصاصی شما:</b>\n\n"

            . "<code>"
            . safe($link)
            . "</code>\n\n"

            . "👥 لینک را برای دوستانت ارسال کن."
        );

        return;
    }


    // ------------------------------------------
    // دعوت ها
    // ------------------------------------------

    if (
        $text
        === '👥 دعوت‌های من'
    ) {

        sendMessage(
            $token,
            $chat_id,

            "👥 <b>دعوت‌های شما</b>\n\n"

            . "👤 دعوت موفق: <b>"
            . (int)$user['referrals']
            . "</b>\n\n"

            . "💰 امتیاز: <b>"
            . (int)$user['coins']
            . "</b>"
        );

        return;
    }


    // ------------------------------------------
    // امتیاز
    // ------------------------------------------

    if (
        $text
        === '💰 امتیاز من'
    ) {

        sendMessage(
            $token,
            $chat_id,

            "💰 امتیاز شما:\n\n"

            . "<b>"
            . (int)$user['coins']
            . "</b> 💎"
        );

        return;
    }


    // ------------------------------------------
    // آمار
    // ------------------------------------------

    if (
        $text
        === '📊 آمار من'
    ) {

        sendMessage(
            $token,
            $chat_id,

            "📊 <b>آمار شما</b>\n\n"

            . "👤 "
            . safe(
                $user['first_name']
            )
            . "\n\n"

            . "👥 دعوت‌ها: <b>"
            . (int)$user['referrals']
            . "</b>\n"

            . "💰 امتیاز: <b>"
            . (int)$user['coins']
            . "</b>"
        );

        return;
    }


    // ------------------------------------------
    // برترین ها
    // ------------------------------------------

    if (
        $text
        === '🏆 برترین‌ها'
    ) {

        $top =
            childTopUsers(
                $bot_id,
                10
            );


        $message =
            "🏆 <b>برترین دعوت‌کنندگان</b>\n\n";


        $rank = 1;


        foreach (
            $top
            as $item
        ) {

            $message .=

                $rank
                . ". "

                . safe(
                    $item['first_name']
                    ?: 'کاربر'
                )

                . " — 👥 "

                . (int)$item['referrals']

                . "\n";


            $rank++;
        }


        sendMessage(
            $token,
            $chat_id,
            $message
        );

        return;
    }


    // ------------------------------------------
    // راهنما
    // ------------------------------------------

    if (
        $text
        === 'ℹ️ راهنما'
    ) {

        sendMessage(
            $token,
            $chat_id,

            "ℹ️ <b>راهنما</b>\n\n"

            . "🔗 لینک دعوت خودت را دریافت کن.\n\n"

            . "👥 لینک را برای دوستانت بفرست.\n\n"

            . "💰 با دعوت موفق امتیاز دریافت می‌کنی.\n\n"

            . "🏆 رتبه خودت را در برترین‌ها ببین."
        );

        return;
    }


    sendMessage(
        $token,
        $chat_id,

        "از منوی ربات استفاده کن.",

        childKeyboard(
            'member'
        )
    );
}