#!/bin/bash

# Definir cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função para log
log() {
    echo -e "${GREEN}[MIGRAÇÃO]${NC} $1"
}

# Função para aviso
warn() {
    echo -e "${YELLOW}[AVISO]${NC} $1"
}

# Verificar se estamos no diretório correto
if [[ ! -f 365condman.php ]]; then
    echo "Este script deve ser executado na raiz do plugin 365condman"
    exit 1
fi

# Criar nova estrutura de diretórios
log "Criando nova estrutura de diretórios..."
mkdir -p src/{Core,Admin,Public,Domain,Infrastructure}
mkdir -p tests/{Unit,Integration}
mkdir -p config
mkdir -p assets/{css,js,images}

# Migrar arquivos do admin
log "Migrando arquivos administrativos..."
if [[ -d admin ]]; then
    mv admin/* src/Admin/
    rmdir admin
fi

# Migrar arquivos de includes
log "Migrando arquivos de includes..."
if [[ -d includes ]]; then
    # Distribuir arquivos de includes entre os novos namespaces
    for file in includes/*; do
        if [[ -f "$file" ]]; then
            # Lógica básica de distribuição (pode ser refinada)
            if [[ "$file" == *"core"* ]]; then
                mv "$file" src/Core/
            elif [[ "$file" == *"domain"* ]]; then
                mv "$file" src/Domain/
            elif [[ "$file" == *"infrastructure"* ]]; then
                mv "$file" src/Infrastructure/
            else
                mv "$file" src/Core/
            fi
        fi
    done
    rmdir includes
fi

# Criar composer.json para autoload
log "Configurando Composer para autoload..."
cat > composer.json << EOL
{
    "name": "365condman/wordpress-plugin",
    "description": "Sistema de Gestão Condominial",
    "type": "wordpress-plugin",
    "require": {
        "php": "^8.0"
    },
    "autoload": {
        "psr-4": {
            "CondMan\\Core\\": "src/Core",
            "CondMan\\Admin\\": "src/Admin",
            "CondMan\\Public\\": "src/Public",
            "CondMan\\Domain\\": "src/Domain",
            "CondMan\\Infrastructure\\": "src/Infrastructure"
        }
    },
    "require-dev": {
        "phpunit/phpunit": "^9.5",
        "squizlabs/php_codesniffer": "^3.6"
    }
}
EOL

# Atualizar o arquivo principal do plugin para usar novos namespaces
log "Atualizando namespace no arquivo principal..."
sed -i 's/namespace CondMan;/namespace CondMan\\Core;/' 365condman.php

# Criar arquivo de configuração inicial
log "Criando arquivo de configuração..."
cat > config/constants.php << EOL
<?php
namespace CondMan\Config;

defined('ABSPATH') or die('Acesso direto não permitido.');

class Constants {
    public const PLUGIN_NAME = '365 Cond Man';
    public const PLUGIN_VERSION = '1.0.0';
    public const PLUGIN_SLUG = '365condman';
}
EOL

# Criar README de migração
log "Criando documentação de migração..."
cat > MIGRATION_README.md << EOL
# Migração de Estrutura de Projeto

## Mudanças Principais
- Adoção de estrutura PSR-4
- Namespaces reorganizados
- Autoload via Composer
- Separação clara de responsabilidades

## Próximos Passos
1. Executar \`composer install\`
2. Verificar compatibilidade de namespaces
3. Atualizar importações em todos os arquivos
4. Rodar testes de integração

## Considerações
- Manter retrocompatibilidade
- Atualizar documentação
- Revisar todas as importações
EOL

log "Migração concluída com sucesso!"
echo "Por favor, execute 'composer install' para finalizar a configuração."
