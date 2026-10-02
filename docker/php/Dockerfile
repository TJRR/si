FROM php:7.3-apache

RUN docker-php-ext-install pdo_mysql mbstring

# unzip: o Composer baixa os pacotes compactados. Se a versao mais nova sumir
# do espelho de seguranca, o "||" instala a anterior, do espelho principal.
RUN apt-get update && ( \
        apt-get install -y --no-install-recommends unzip \
        || apt-get install -y --no-install-recommends unzip=6.0-26+deb11u1 \
    ) \
    && rm -rf /var/lib/apt/lists/*

# GD com suporte a WebP, para o redimensionamento e a conversao de imagens.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libwebp-dev libjpeg62-turbo-dev libpng-dev libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd \
        --with-webp-dir=/usr --with-jpeg-dir=/usr --with-png-dir=/usr --with-freetype-dir=/usr \
    && docker-php-ext-install gd

# AllowOverride None ja vem da imagem base: o sistema nao depende de .htaccess.

# Limites de envio acima dos 15MB exigidos pela aplicacao: sem isso o PHP
# descarta a requisicao inteira antes de ela chegar ao codigo.
RUN { \
    echo 'upload_max_filesize = 20M'; \
    echo 'post_max_size = 25M'; \
} > /usr/local/etc/php/conf.d/uploads.ini

# /etc/mime.types desta imagem nao mapeia .webp: sem isso o Apache serve as
# imagens convertidas sem o tipo, e o navegador nao exibe. Ver Implantar.md,
# secao 4.2.
RUN echo 'AddType image/webp .webp' > /etc/apache2/conf-enabled/webp.conf
