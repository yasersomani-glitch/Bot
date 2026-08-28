FROM php:8.3-apache

# فعال کردن Rewrite
RUN a2enmod rewrite

# نصب SQLite
RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# پوشه اصلی Apache
WORKDIR /var/www/html

# کپی پروژه
COPY . /var/www/html/

# تنظیم دسترسی‌ها
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

# پورت Apache
EXPOSE 80

# اجرای Apache
CMD ["apache2-foreground"]
