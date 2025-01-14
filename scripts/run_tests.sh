#!/bin/bash

# Configurar ambiente de testes
cd /projetos/wordpress-plugins/365condman

# Instalar PHPUnit se não estiver instalado
if ! command -v phpunit &> /dev/null; then
    composer require --dev phpunit/phpunit
fi

# Rodar testes de unidade
echo "Rodando testes de unidade..."
./vendor/bin/phpunit tests/Unit

# Rodar testes de integração
echo "Rodando testes de integração..."
./vendor/bin/phpunit tests/Integration

# Verificar cobertura de código
echo "Gerando relatório de cobertura de código..."
./vendor/bin/phpunit --coverage-html coverage

echo "Testes concluídos."
