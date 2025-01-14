#!/bin/bash

# Instalar dependências do sistema
sudo apt-get update
sudo apt-get install -y curl php-cli php-mbstring git unzip

# Baixar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Instalar dependências do projeto
cd /projetos/wordpress-plugins/365condman
composer install

# Verificar instalação
composer --version
