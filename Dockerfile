FROM --platform=amd64 ubuntu:18.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get -y update \
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
 && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer && chmod +x /usr/local/bin/composer
ADD . /fmpress-forms
ADD composer.json /composer.json
RUN useradd -m wordpress
RUN sudo -u wordpress -i -- cd / && composer update
RUN git clone -b master https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards.git ./vendor/squizlabs/php_codesniffer/Standards/WordPress
RUN ./vendor/bin/phpcs --config-set installed_paths `pwd`/vendor/squizlabs/php_codesniffer/Standards/WordPress

RUN chown wordpress /home/wordpress
ADD . /var/www/html/wp-content/plugins/fmpress-forms
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && chmod +x wp-cli.phar && mv wp-cli.phar /usr/local/bin/wp
RUN sudo -u wordpress -i -- /usr/local/bin/wp core download
RUN sudo -u wordpress -i -- /usr/local/bin/wp core version

RUN (/etc/init.d/mysql start &); sleep 10; mysqladmin -u root password PASSWORD; (echo 'CREATE USER wordpress@localhost IDENTIFIED BY "PASSWORD";' | mysql -u root -pPASSWORD); (echo 'CREATE DATABASE wordpress CHARACTER SET utf8mb4;' | mysql -u root -pPASSWORD); (echo 'grant all privileges on *.* to wordpress@localhost identified by "PASSWORD";' | mysql -u root -pPASSWORD); (echo 'grant all privileges on *.* to root@localhost identified by "PASSWORD";' | mysql -u root -pPASSWORD); sudo -u wordpress -i -- /usr/local/bin/wp config create --dbname=wordpress --dbuser=wordpress --dbpass=PASSWORD --dbhost=localhost; sudo -u wordpress -i -- /usr/local/bin/wp core install --url=http://localhost:9080 --title=WP-Docker --admin_user=admin --admin_password=admin --admin_email=fmpress@emic.co.jp --skip-email; sudo -u wordpress -i -- /usr/local/bin/wp option update timezone_string 'Asia/Tokyo'; sudo -u wordpress -i -- /usr/local/bin/wp option update ping_sites ''; sudo -u wordpress -i -- /usr/local/bin/wp plugin install wp-multibyte-patch --activate; cp -pr /var/www/html/wp-content/plugins/fmpress-forms /home/wordpress/wp-content/plugins/; chown -R wordpress:wordpress /home/wordpress/wp-content/plugins/fmpress-forms; sudo -u wordpress -i -- /usr/local/bin/wp plugin activate fmpress-forms; curl -L -o /usr/local/bin/phpunit https://phar.phpunit.de/phpunit-7.5.9.phar && chmod +x /usr/local/bin/phpunit && /usr/local/bin/phpunit --version

CMD [ "/sbin/init" ]
