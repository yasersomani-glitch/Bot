FROM php:8.3-apache

# فعال کردن Rewrite
RUN a2enmod rewrite

# نصب SQLite و PDO SQLite
RUN docker-php-ext-install pdo pdo_sqlite

# تنظیم پوشه اصلی Apache
WORKDIR /var/www/html

# کپی تمام فایل‌های پروژه
COPY . /var/www/html/

# تنظیم دسترسی‌ها
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# پورت Apache
EXPOSE 80

# اجرای Apache در حالت foreground
CMD ["apache2-foreground"]
