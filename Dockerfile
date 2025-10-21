FROM webdevops/php-nginx:7.3-alpine

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    WEB_DOCUMENT_ROOT="/app"


RUN composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/ \
    && sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories \
    && sed -i 's/dl-4.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories \
    && apk add autoconf \
    && docker-service-enable ssh \
    && echo 'root:123456' | chpasswd

RUN apk add --no-cache --repository http://dl-3.alpinelinux.org/alpine/edge/testing gnu-libiconv
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

#RUN pecl install xdebug && docker-php-ext-enable xdebug

WORKDIR $WEB_DOCUMENT_ROOT
