# Telegram Clock Userbot - PHP + MadelineProto + Render

این پروژه با PHP، MadelineProto و GD ساخته شده است.

کار:
1. زمان Asia/Kabul را می‌گیرد.
2. روی تصویر ساعت، عقربه ساعت/دقیقه/ثانیه را رسم می‌کند.
3. عکس را روی پروفایل اکانت Telegram قرار می‌دهد.
4. خطاها را در stdout/stderr چاپ می‌کند تا در Render Logs دیده شوند.
5. در خطاهای موقت، بعد از 30 ثانیه دوباره تلاش می‌کند.

## مهم

برای اجرای دائمی روی Render از Background Worker استفاده شده است.
Render Free برای Background Worker در دسترس نیست و Free Web Service هم بعد از 15 دقیقه بدون ترافیک ورودی sleep می‌شود.

## Environment Variables

API_ID
API_HASH
TIMEZONE=Asia/Kabul
UPDATE_INTERVAL=60
SESSION_PATH=/var/data/telegram-clock/clock.madeline

## اولین Login

بعد از deploy، در Render روی Worker از Shell استفاده کن و:

php login.php

شماره، کد Telegram و در صورت نیاز رمز 2FA را وارد کن.

Session روی Persistent Disk ذخیره می‌شود.

بعد Worker را Restart/Deploy کن.

## اجرای محلی

php login.php
php index.php

## امنیت

API_HASH و فایل session را در GitHub commit نکن.
فایل .gitignore برای همین کار اضافه شده است.

## درباره آپدیت هر ثانیه

UPDATE_INTERVAL حداقل 30 ثانیه در config محدود شده است.
تغییر عکس پروفایل در هر ثانیه مناسب نیست و می‌تواند باعث FloodWait یا محدودیت Telegram شود.
