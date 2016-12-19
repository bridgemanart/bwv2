FROM php:7.0-fpm

# APT-GET updates and Installs
RUN apt-get update
RUN apt-get install -y vim git wget \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng12-dev

# PHP Libs.
RUN docker-php-ext-install -j$(nproc) opcache
RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/
RUN docker-php-ext-install -j$(nproc) gd
RUN docker-php-ext-install -j$(nproc) zip
RUN docker-php-ext-install -j$(nproc) pdo
RUN docker-php-ext-install -j$(nproc) pdo_mysql

# Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('SHA384', 'composer-setup.php') === 'c32408bcd017c577ce80605420e5987ce947a5609e8443dd72cd3867cc3a0cf442e5bf4edddbcbe72246a953a6c48e21') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN mv composer.phar /usr/local/bin/composer


################################## BlackFire PHP
RUN wget -O - https://packagecloud.io/gpg.key | apt-key add -
RUN echo "deb http://packages.blackfire.io/debian any main" | tee /etc/apt/sources.list.d/blackfire.list
RUN apt-get update

### BlackFire PHP: Installing the Agent
RUN apt-get install -y blackfire-agent
#RUN blackfire-agent -register

### BlackFire PHP: Installing the Blackfire CLI tool
RUN apt-get install -y blackfire-agent
#RUN blackfire config

### BlackFire PHP: Installing the PHP Probe
RUN apt-get install -y blackfire-php

## BlackFire PHP : Known incompatibilities
#Please note that PHP compiled with debug are not supported and that the Probe may conflict with XDebug or XHProf; disable those extensions when enabling the Probe.
#The Probe conflicts with some PHP extensions like Pinba.

################################## BlackFire PHP

# Install Graphics Magick
RUN apt-get install -y python-software-properties
RUN apt-get install -y software-properties-common
RUN echo | add-apt-repository ppa:rwky/graphicsmagick
#RUN apt-get update
RUN apt-get install -y graphicsmagick


## BlackFire PHP : Docker Container
#RUN version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;") \
#    && curl -A "Docker" -o /tmp/blackfire-probe.tar.gz -D - -L -s https://blackfire.io/api/v1/releases/probe/php/linux/amd64/$version \
#    && tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp \
#    && mv /tmp/blackfire-*.so $(php -r "echo ini_get('extension_dir');")/blackfire.so \
#    && printf "extension=blackfire.so\nblackfire.agent_socket=tcp://blackfire:8707\n" > $PHP_INI_DIR/conf.d/blackfire.ini

## XDebug - Remove when in production.
#RUN yes | pecl install xdebug \
#    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
#    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
#    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini
