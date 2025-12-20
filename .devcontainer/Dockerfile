FROM mcr.microsoft.com/devcontainers/base:ubuntu-24.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    ca-certificates curl git zsh unzip gnupg2 software-properties-common \
    build-essential pkg-config \
  && rm -rf /var/lib/apt/lists/*

RUN add-apt-repository -y ppa:ondrej/php \
  && apt-get update \
  && apt-get install -y --no-install-recommends \
    php8.3-cli \
    php8.3-fpm \
    php8.3-common \
    php8.3-dev \
    php-pear \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-curl \
    php8.3-zip \
    php8.3-intl \
    php8.3-bcmath \
    php8.3-readline \
    php8.3-xdebug \
    php8.3-pgsql \
    php8.3-mysql \
    php8.3-sqlite3 \
  && rm -rf /var/lib/apt/lists/*

RUN update-alternatives --install /usr/bin/php php /usr/bin/php8.3 83 \
  && update-alternatives --set php /usr/bin/php8.3

RUN set -eux; \
  curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor > /usr/share/keyrings/microsoft.gpg; \
  echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft.gpg] https://packages.microsoft.com/ubuntu/24.04/prod noble main" > /etc/apt/sources.list.d/microsoft-prod.list; \
  apt-get update; \
  ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql18 unixodbc-dev; \
  pecl channel-update pecl.php.net; \
  printf "\n" | pecl install sqlsrv; \
  printf "\n" | pecl install pdo_sqlsrv; \
  echo "extension=sqlsrv.so" > /etc/php/8.3/mods-available/sqlsrv.ini; \
  echo "extension=pdo_sqlsrv.so" > /etc/php/8.3/mods-available/pdo_sqlsrv.ini; \
  phpenmod -v 8.3 sqlsrv pdo_sqlsrv; \
  rm -rf /var/lib/apt/lists/*

RUN sed -i \
    -e 's|^listen = .*|listen = 9000|g' \
    -e 's|^user = .*|user = vscode|g' \
    -e 's|^group = .*|group = vscode|g' \
    /etc/php/8.3/fpm/pool.d/www.conf

RUN printf "%s\n" \
  "xdebug.mode=debug,develop" \
  "xdebug.start_with_request=yes" \
  "xdebug.discover_client_host=0" \
  "xdebug.client_host=host.docker.internal" \
  "xdebug.client_port=9003" \
  "xdebug.log_level=0" \
  > /etc/php/8.3/mods-available/99-xdebug-devcontainer.ini \
  && phpenmod -v 8.3 99-xdebug-devcontainer

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
  && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
  && rm -f composer-setup.php

WORKDIR /var/www/html

CMD ["php-fpm8.3", "-F"]
