FROM --platform=amd64 ubuntu:20.04

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
    mariadb-server \
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

RUN (/etc/init.d/mysql start &); sleep 10; mysqladmin -u root password PASSWORD; (echo 'CREATE USER wordpress@localhost IDENTIFIED BY "PASSWORD";' | mysql -u root -pPASSWORD); (echo 'CREATE DATABASE wordpress CHARACTER SET utf8mb4;' | mysql -u root -pPASSWORD); (echo 'grant all privileges on *.* to wordpress@localhost identified by "PASSWORD";' | mysql -u root -pPASSWORD); (echo 'grant all privileges on *.* to root@localhost identified by "PASSWORD";' | mysql -u root -pPASSWORD); sudo -u wordpress -i -- /usr/local/bin/wp config create --dbname=wordpress --dbuser=wordpress --dbpass=PASSWORD --dbhost=localhost; sudo -u wordpress -i -- /usr/local/bin/wp core install --url=http://localhost:9080 --title=WP-Docker --admin_user=admin --admin_password=admin --admin_email=fmpress@emic.co.jp --skip-email; sudo -u wordpress -i -- /usr/local/bin/wp option update timezone_string 'Asia/Tokyo'; sudo -u wordpress -i -- /usr/local/bin/wp option update ping_sites ''; sudo -u wordpress -i -- /usr/local/bin/wp plugin install wp-multibyte-patch --activate; cp -pr /var/www/html/wp-content/plugins/fmpress-forms /home/wordpress/wp-content/plugins/; chown -R wordpress:wordpress /home/wordpress/wp-content/plugins/fmpress-forms; sudo -u wordpress -i -- /usr/local/bin/wp plugin activate fmpress-forms; curl -L -o /usr/local/bin/phpunit https://phar.phpunit.de/phpunit-8.phar && chmod +x /usr/local/bin/phpunit && /usr/local/bin/phpunit --version

CMD [ "/sbin/init" ]
