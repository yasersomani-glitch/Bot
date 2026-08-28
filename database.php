<?php

require_once __DIR__ . '/config.php';

function db()
{
    static $pdo = null;

    if ($pdo === null) {

        $pdo = new PDO(
            'sqlite:' . DB_FILE
        );

        $pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        $pdo->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            PDO::FETCH_ASSOC
        );

        // ==========================================
        // کاربران ربات مادر
        // ==========================================

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                telegram_id INTEGER UNIQUE NOT NULL,
                first_name TEXT DEFAULT '',
                username TEXT DEFAULT '',
                state TEXT DEFAULT '',
                state_data TEXT DEFAULT '',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // ==========================================
        // ربات های فرزند
        // ==========================================

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS child_bots (
                id INTEGER PRIMARY KEY AUTOINCREMENT,

                owner_id INTEGER NOT NULL,

                token TEXT UNIQUE NOT NULL,

                bot_id INTEGER UNIQUE NOT NULL,

                username TEXT DEFAULT '',

                first_name TEXT DEFAULT '',

                type TEXT NOT NULL,

                title TEXT DEFAULT '',

                enabled INTEGER DEFAULT 1,

                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // ==========================================
        // کاربران ربات های فرزند
        // ==========================================

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS child_users (

                id INTEGER PRIMARY KEY AUTOINCREMENT,

                bot_id INTEGER NOT NULL,

                telegram_id INTEGER NOT NULL,

                first_name TEXT DEFAULT '',

                username TEXT DEFAULT '',

                referrals INTEGER DEFAULT 0,

                coins INTEGER DEFAULT 0,

                invited_by INTEGER DEFAULT NULL,

                banned INTEGER DEFAULT 0,

                created_at TEXT DEFAULT CURRENT_TIMESTAMP,

                UNIQUE(bot_id, telegram_id)
            )
        ");

        // ==========================================
        // تنظیمات هر ربات فرزند
        // ==========================================

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (

                id INTEGER PRIMARY KEY AUTOINCREMENT,

                bot_id INTEGER NOT NULL,

                name TEXT NOT NULL,

                value TEXT DEFAULT '',

                UNIQUE(bot_id, name)
            )
        ");
    }

    return $pdo;
}


// ==================================================
// کاربر مادر
// ==================================================

function getMotherUser($telegram_id)
{
    $stmt = db()->prepare("
        SELECT *
        FROM users
        WHERE telegram_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $telegram_id
    ]);

    return $stmt->fetch() ?: null;
}


// ==================================================
// ثبت / بروزرسانی کاربر مادر
// ==================================================

function saveMotherUser(
    $telegram_id,
    $first_name = '',
    $username = ''
) {

    $old = getMotherUser($telegram_id);

    if ($old) {

        $stmt = db()->prepare("
            UPDATE users
            SET first_name = ?,
                username = ?
            WHERE telegram_id = ?
        ");

        $stmt->execute([
            $first_name,
            $username,
            $telegram_id
        ]);

    } else {

        $stmt = db()->prepare("
            INSERT INTO users
            (
                telegram_id,
                first_name,
                username
            )
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $telegram_id,
            $first_name,
            $username
        ]);
    }

    return getMotherUser($telegram_id);
}


// ==================================================
// وضعیت کاربر مادر
// ==================================================

function setMotherState(
    $telegram_id,
    $state,
    $data = []
) {

    $stmt = db()->prepare("
        UPDATE users
        SET state = ?,
            state_data = ?
        WHERE telegram_id = ?
    ");

    $stmt->execute([
        $state,
        json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
        ),
        $telegram_id
    ]);
}


// ==================================================
// پاک کردن وضعیت
// ==================================================

function clearMotherState($telegram_id)
{
    setMotherState(
        $telegram_id,
        '',
        []
    );
}


// ==================================================
// ساخت ربات فرزند
// ==================================================

function createChildBot(
    $owner_id,
    $token,
    $bot_info,
    $type
) {

    $stmt = db()->prepare("
        INSERT INTO child_bots
        (
            owner_id,
            token,
            bot_id,
            username,
            first_name,
            type
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $owner_id,
        $token,
        $bot_info['id'],
        $bot_info['username'] ?? '',
        $bot_info['first_name'] ?? '',
        $type
    ]);

    return db()->lastInsertId();
}


// ==================================================
// دریافت ربات فرزند
// ==================================================

function getChildBot($id)
{
    $stmt = db()->prepare("
        SELECT *
        FROM child_bots
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $id
    ]);

    return $stmt->fetch() ?: null;
}


// ==================================================
// دریافت ربات با Bot ID
// ==================================================

function getChildByTelegramBotId($bot_id)
{
    $stmt = db()->prepare("
        SELECT *
        FROM child_bots
        WHERE bot_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $bot_id
    ]);

    return $stmt->fetch() ?: null;
}


// ==================================================
// ربات های یک مالک
// ==================================================

function getOwnerBots($owner_id)
{
    $stmt = db()->prepare("
        SELECT *
        FROM child_bots
        WHERE owner_id = ?
        ORDER BY id DESC
    ");

    $stmt->execute([
        $owner_id
    ]);

    return $stmt->fetchAll();
}


// ==================================================
// حذف ربات
// ==================================================

function deleteChildBot(
    $id,
    $owner_id
) {

    $stmt = db()->prepare("
        DELETE FROM child_bots
        WHERE id = ?
        AND owner_id = ?
    ");

    return $stmt->execute([
        $id,
        $owner_id
    ]);
}


// ==================================================
// کاربر ربات فرزند
// ==================================================

function getChildUser(
    $bot_id,
    $telegram_id
) {

    $stmt = db()->prepare("
        SELECT *
        FROM child_users

        WHERE bot_id = ?

        AND telegram_id = ?

        LIMIT 1
    ");

    $stmt->execute([
        $bot_id,
        $telegram_id
    ]);

    return $stmt->fetch() ?: null;
}


// ==================================================
// ثبت کاربر ربات فرزند
// ==================================================

function createChildUser(
    $bot_id,
    $telegram_id,
    $first_name = '',
    $username = '',
    $invited_by = null
) {

    $old = getChildUser(
        $bot_id,
        $telegram_id
    );

    if ($old) {

        $stmt = db()->prepare("
            UPDATE child_users

            SET first_name = ?,
                username = ?

            WHERE bot_id = ?
            AND telegram_id = ?
        ");

        $stmt->execute([
            $first_name,
            $username,
            $bot_id,
            $telegram_id
        ]);

        return getChildUser(
            $bot_id,
            $telegram_id
        );
    }

    $stmt = db()->prepare("
        INSERT INTO child_users
        (
            bot_id,
            telegram_id,
            first_name,
            username,
            invited_by
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $bot_id,
        $telegram_id,
        $first_name,
        $username,
        $invited_by
    ]);

    return getChildUser(
        $bot_id,
        $telegram_id
    );
}


// ==================================================
// اضافه کردن دعوت
// ==================================================

function addReferral(
    $bot_id,
    $user_id
) {

    $stmt = db()->prepare("
        UPDATE child_users

        SET referrals = referrals + 1,
            coins = coins + 1

        WHERE bot_id = ?
        AND telegram_id = ?
    ");

    $stmt->execute([
        $bot_id,
        $user_id
    ]);
}


// ==================================================
// تعداد کاربران
// ==================================================

function childUserCount($bot_id)
{
    $stmt = db()->prepare("
        SELECT COUNT(*)

        FROM child_users

        WHERE bot_id = ?
    ");

    $stmt->execute([
        $bot_id
    ]);

    return (int)$stmt->fetchColumn();
}


// ==================================================
// برترین کاربران
// ==================================================

function childTopUsers(
    $bot_id,
    $limit = 10
) {

    $limit = (int)$limit;

    $stmt = db()->prepare("
        SELECT *

        FROM child_users

        WHERE bot_id = ?

        ORDER BY referrals DESC,
                 coins DESC

        LIMIT $limit
    ");

    $stmt->execute([
        $bot_id
    ]);

    return $stmt->fetchAll();
}


// ==================================================
// تنظیمات ربات
// ==================================================

function getSetting(
    $bot_id,
    $name,
    $default = ''
) {

    $stmt = db()->prepare("
        SELECT value

        FROM settings

        WHERE bot_id = ?

        AND name = ?

        LIMIT 1
    ");

    $stmt->execute([
        $bot_id,
        $name
    ]);

    $value = $stmt->fetchColumn();

    return $value === false
        ? $default
        : $value;
}


// ==================================================
// ذخیره تنظیمات
// ==================================================

function setSetting(
    $bot_id,
    $name,
    $value
) {

    $stmt = db()->prepare("
        INSERT INTO settings
        (
            bot_id,
            name,
            value
        )

        VALUES (?, ?, ?)

        ON CONFLICT(bot_id, name)

        DO UPDATE SET
            value = excluded.value
    ");

    $stmt->execute([
        $bot_id,
        $name,
        $value
    ]);
}