FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get -y update \
# && apt-get install -y software-properties-common \
# && add-apt-repository ppa:ondrej/php \
 && apt-get -y upgrade\
 && apt-get install -y --no-install-recommends \
    init \
    systemd \
    tzdata \
    curl \
    ca-certificates \
    wget \
    git \
    unzip \
    mariadb-client \
    sudo \
    subversion \
    libxt6 \
    php \
    php-cli \
    php-mbstring \
    php-xmlwriter \
    php-curl \
    php-mysql \
    vim \
    apache2 \
    libapache2-mod-php \
 && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer && chmod +x /usr/local/bin/composer
ADD . /fmpress-forms
ADD composer.json /composer.json
RUN useradd -m wordpress
RUN sudo -u wordpress -i -- cd / && composer update

# Installing WordPress Coding Standards for PHP_CodeSniffer
RUN sudo -u wordpress -i -- cd / && composer config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
RUN sudo -u wordpress -i -- cd / && composer require --dev wp-coding-standards/wpcs:"^3.0"
RUN sudo -u wordpress -i -- cd / && composer update wp-coding-standards/wpcs --with-dependencies
RUN sudo -u wordpress -i -- /vendor/bin/phpcs -ps . --standard=WordPress

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && chmod +x wp-cli.phar && mv wp-cli.phar /usr/local/bin/wp
RUN rm -f /var/www/html/index.html
RUN chown wordpress /var/www/html
RUN sudo -u wordpress -i -- /usr/local/bin/wp core download --allow-root --locale=ja --path=/var/www/html
ADD . /var/www/html/wp-content/plugins/fmpress-forms
RUN chown -R www-data:wordpress /var/www/html

RUN curl -L -o /usr/local/bin/phpunit https://phar.phpunit.de/phpunit-9.phar && chmod +x /usr/local/bin/phpunit && /usr/local/bin/phpunit --version

CMD [ "/sbin/init" ]
