FROM php:7.1-fpm-alpine

RUN echo http://mirrors.ustc.edu.cn/alpine/v3.7/main/  > /etc/apk/repositories \
	&& echo http://mirrors.ustc.edu.cn/alpine/v3.7/community/  >> /etc/apk/repositories \
	&& apk update \
	&& apk upgrade \
	&& EXPECTED_COMPOSER_SIGNATURE=$(wget -q -O - https://composer.github.io/installer.sig) \
	&& php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('SHA384', 'composer-setup.php') === '${EXPECTED_COMPOSER_SIGNATURE}') { echo 'Composer.phar Installer verified'; } else { echo 'Composer.phar Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php --install-dir=/usr/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"  \
	&& composer config -g repo.packagist composer https://packagist.phpcomposer.com \
    && apk add hiredis-dev libmcrypt-dev gmp-dev icu-dev linux-headers musl --virtual .phpize-deps autoconf m4 make g++ gcc openssl-dev git \
    && pecl download swoole-4.0.2 \
	&& mkdir -p /tmp/swoole \
    && tar -xf swoole-4.0.2.tgz  -C /tmp/swoole --strip-components=1 \
    && rm -f swoole-4.0.2.tgz \
	&& docker-php-ext-install sockets \
	&& docker-php-ext-configure /tmp/swoole/ --enable-async-redis --enable-openssl --enable-mysqlnd --enable-sockets=/usr/local/include/php/ext/sockets \
    && docker-php-ext-install /tmp/swoole \
	&& docker-php-ext-install intl \
	&& git clone https://github.com/runkit7/runkit7.git \
	&& cd runkit7 \
	&& phpize \
	&& ./configure \
	&& make \
	&& make install \
	&& cd .. \
	&& rm -rf runkit7 \
	&& docker-php-ext-enable runkit
	
EXPOSE 80 9501

CMD ["php", "think","swoole:server"]