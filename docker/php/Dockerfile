FROM php:7.3-apache

RUN docker-php-ext-install pdo_mysql mbstring

# A imagem base (Debian) já define AllowOverride None para /var/www/ no
# apache2.conf padrão — replica a mesma restrição do servidor de produção
# (AllowOverride None confirmado no spike técnico), sem precisar de vhost customizado.
