FROM php:7.3-apache

RUN docker-php-ext-install pdo_mysql mbstring

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
