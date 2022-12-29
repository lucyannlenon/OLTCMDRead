# Base image
FROM php:7.4-apache

# Fix debconf warnings upon build
ARG DEBIAN_FRONTEND=noninteractive

# Run apt update and install some dependancies needed for docker-php-ext
RUN apt update && apt install -y apt-utils libcurl4-openssl-dev sendmail mariadb-client pngquant unzip zip libpng-dev libmcrypt-dev git \
  curl libicu-dev libxml2-dev libzip-dev openssl libssl-dev libcurl4  libsqlite3-dev libsqlite3-0 memcached snmpd libmemcached-tools libmemcached-dev
# Enable mod_rewrite
RUN a2enmod rewrite
RUN a2enmod headers
RUN apt-get update && apt-get install -y \
    libmagickwand-dev --no-install-recommends \
    && pecl install imagick \
	&& docker-php-ext-enable imagick

#pecl install memcached
RUN pecl install memcached

# Install PHP extensions
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install gd
RUN docker-php-ext-install intl
RUN docker-php-ext-install xml
RUN docker-php-ext-install curl
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install pdo_sqlite
RUN docker-php-ext-install dom
RUN docker-php-ext-install session
RUN docker-php-ext-install opcache
RUN docker-php-ext-install pcntl
RUN docker-php-ext-install zip
RUN docker-php-ext-install sockets
RUN docker-php-ext-enable memcached

#install composer
RUN curl -sS https://getcomposer.org/installer | php -- \
--install-dir=/usr/bin --filename=composer

# install memcached
#RUN git clone https://github.com/php-memcached-dev/php-memcached /usr/src/php/ext/memcached
#RUN cd /usr/src/php/ext/memcached && git checkout -b php7 origin/php7
#RUN docker-php-ext-configure memcached
#RUN docker-php-ext-install memcached


# Update web root to public
# See: https://hub.docker.com/_/php#changing-documentroot-or-other-apache-configuration
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN apt-get -y install librabbitmq-dev
RUN pecl install amqp

RUN docker-php-ext-enable amqp

RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

COPY 90-xdebug.ini "${PHP_INI_DIR}/conf.d"

## teste
RUN apt install -y  wget  libssh2-1-dev  libssh2-1

RUN wget https://pecl.php.net/get/ssh2-1.2.tgz

RUN  tar -xzf ssh2-1.2.tgz && rm -rf ssh2-1.2.tgz &&  cd ssh2-1.2 && phpize \
     && ./configure && make && make install  && cd .. &&  rm -rf ssh2-1.2

RUN echo "extension=ssh2.so" > ${PHP_INI_DIR}/conf.d/docker-php-ext-ssh2.ini



