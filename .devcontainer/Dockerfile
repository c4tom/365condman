FROM wordpress:latest

# Instalar dependências necessárias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    default-mysql-client tree vim\
    && docker-php-ext-install zip

# Instalar Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instalar SQLite e PHP SQLite extension
RUN apt-get install -y sqlite3 libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite

# Instalar WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

# Criar alias para wp com --allow-root
RUN echo 'alias wp="wp --allow-root"' >> ~/.bashrc

# Configurar o PHP
COPY php.ini /usr/local/etc/php/conf.d/php.ini

# Configurar permissões
RUN usermod -u 1000 www-data \
    && groupmod -g 1000 www-data \
    && chown -R www-data:www-data /var/www/html

# Definir diretório de trabalho
WORKDIR /var/www/html

# Script para ajustar permissões durante a inicialização
COPY docker-entrypoint-custom.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint-custom.sh

ENTRYPOINT ["docker-entrypoint-custom.sh"]
CMD ["apache2-foreground"]
