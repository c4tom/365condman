#!/bin/bash

# Script de configuração inicial do projeto 365 Cond Man

set -e

# Instalar dependências do Composer
composer install

# Instalar dependências do Node.js (se aplicável)
npm install

# Configurar permissões
chmod -R 755 .

# Criar arquivo de configuração de ambiente
if [ ! -f .env ]; then
    cp .env.example .env
    echo "Arquivo .env criado com base no exemplo"
fi

# Executar verificações de código
composer phpcs
composer phpstan

# Executar testes
./vendor/bin/phpunit

echo "Configuração do projeto 365 Cond Man concluída com sucesso!"
