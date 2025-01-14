#!/bin/bash

# Executar o script de entrypoint original do WordPress
/usr/local/bin/docker-entrypoint.sh "$@" &

# Aguardar um pouco para garantir que os arquivos foram criados
sleep 5

# Ajustar permissões
chown -R www-data:www-data /var/www/html
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

# Manter o container rodando


wait $!
