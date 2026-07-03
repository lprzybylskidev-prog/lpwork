FROM php:8.5-fpm

ARG USERNAME=vscode
ARG USER_UID=1000
ARG USER_GID=$USER_UID

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        bubblewrap \
        chromium \
        fonts-freefont-ttf \
        fonts-ipafont-gothic \
        fonts-liberation \
        fonts-noto-color-emoji \
        fonts-tlwg-loma-otf \
        fonts-unifont \
        fonts-wqy-zenhei \
        git \
        lftp \
        libfontenc1 \
        libpq-dev \
        libssh2-1-dev \
        libsqlite3-dev \
        libunwind8 \
        libxfont2 \
        libzip-dev \
        mariadb-client \
        msmtp-mta \
        nodejs \
        npm \
        openssh-client \
        postgresql-client \
        redis-tools \
        ripgrep \
        sqlite3 \
        sudo \
        unzip \
        x11-xkb-utils \
        xauth \
        xfonts-encodings \
        xfonts-scalable \
        xfonts-utils \
        xserver-common \
        xvfb \
        libicu-dev \
        zip \
    && docker-php-ext-install \
        ftp \
        intl \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        zip \
    && pecl install \
        apcu \
        redis \
        ssh2 \
        xdebug \
    && docker-php-ext-enable \
        apcu \
        redis \
        ssh2 \
        xdebug \
    && groupadd --gid "$USER_GID" "$USERNAME" \
    && useradd --uid "$USER_UID" --gid "$USER_GID" --shell /usr/bin/bash -m "$USERNAME" \
    && echo "$USERNAME ALL=(root) NOPASSWD:ALL" > "/etc/sudoers.d/$USERNAME" \
    && chmod 0440 "/etc/sudoers.d/$USERNAME" \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN { \
        echo "opcache.enable=1"; \
        echo "opcache.enable_cli=1"; \
        echo "opcache.validate_timestamps=1"; \
        echo "opcache.revalidate_freq=0"; \
        echo "apc.enable_cli=1"; \
        echo "xdebug.mode=develop,debug"; \
        echo "xdebug.client_host=host.docker.internal"; \
        echo "xdebug.start_with_request=trigger"; \
    } > /usr/local/etc/php/conf.d/dev.ini
