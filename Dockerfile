FROM php:8.3-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        unzip \
    && docker-php-ext-configure gd \
    && docker-php-ext-install gd \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY . /app

RUN mkdir -p /var/data/telegram-clock \
    && chmod -R 775 /var/data/telegram-clock

CMD ["php", "index.php"]
