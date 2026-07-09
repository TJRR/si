FROM php:7.3-apache

RUN docker-php-ext-install pdo_mysql mbstring

# GD com suporte a WebP - usado pelo ImagemService para redimensionar/converter
# imagens enviadas pelo admin no CMS do site publico (upload de logo/imagens de secao).
RUN apt-get update && apt-get install -y --no-install-recommends \
        libwebp-dev libjpeg62-turbo-dev libpng-dev libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd \
        --with-webp-dir=/usr --with-jpeg-dir=/usr --with-png-dir=/usr --with-freetype-dir=/usr \
    && docker-php-ext-install gd

# A imagem base (Debian) já define AllowOverride None para /var/www/ no
# apache2.conf padrão — replica a mesma restrição do servidor de produção
# (AllowOverride None confirmado no spike técnico), sem precisar de vhost customizado.

# Limites de upload: o padrão da imagem (2M/8M) é bem menor que os 15MB exigidos
# pela aplicação e que os 10240M do servidor real do TJRR (spike técnico) — sem
# isso o PHP descarta a requisição inteira antes mesmo dela chegar ao código.
RUN { \
    echo 'upload_max_filesize = 20M'; \
    echo 'post_max_size = 25M'; \
} > /usr/local/etc/php/conf.d/uploads.ini

# /etc/mime.types desta imagem (Debian) nao mapeia .webp - sem isso o Apache
# serve as imagens convertidas pelo ImagemService sem Content-Type, e o
# navegador nao exibe. Config global (nao .htaccess, AllowOverride None
# continua valendo) - precisa do mesmo ajuste no Apache do servidor de
# producao antes do lancamento.
RUN echo 'AddType image/webp .webp' > /etc/apache2/conf-enabled/webp.conf
