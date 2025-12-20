FROM mcr.microsoft.com/devcontainers/base:ubuntu-24.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    ca-certificates curl git zsh unzip gnupg2 software-properties-common \
  && rm -rf /var/lib/apt/lists/*

# PHP 8.5 from ondrej/php
RUN add-apt-repository -y ppa:ondrej/php \
  && apt-get update \
  && apt-get install -y --no-install-recommends \
    php8.5-cli \
    php8.5-common \
    php8.5-mbstring \
    php8.5-xml \
    php8.5-curl \
    php8.5-zip \
    php8.5-intl \
    php8.5-bcmath \
    php8.5-readline \
    php8.5-xdebug \
  && rm -rf /var/lib/apt/lists/*

RUN update-alternatives --install /usr/bin/php php /usr/bin/php8.5 85 \
  && update-alternatives --set php /usr/bin/php8.5

# Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
  && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
  && rm -f composer-setup.php
