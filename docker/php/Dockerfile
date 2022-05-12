FROM php:7.4.15-apache

RUN docker-php-ext-install pdo pdo_mysql

RUN apt-get update && \
   apt-get install -y libpng-dev libzip-dev git libicu-dev
RUN docker-php-ext-install gd
RUN docker-php-ext-install zip
RUN docker-php-ext-install intl

RUN a2enmod rewrite
COPY php.ini /usr/local/etc/php/

RUN php -d allow_url_fopen=1 -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
	php -d allow_url_fopen=On composer-setup.php --quiet && \
	rm composer-setup.php && \
	mv composer.phar /usr/local/sbin/composer

RUN apt-get install npm -y
RUN npm install --force -g yarn