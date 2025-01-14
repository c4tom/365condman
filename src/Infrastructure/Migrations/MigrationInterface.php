<?php
namespace CondMan\Infrastructure\Migrations;

interface MigrationInterface {
    /**
     * Executa a migração
     * @return bool Sucesso da migração
     */
    public function up(): bool;

    /**
     * Reverte a migração
     * @return bool Sucesso da reversão
     */
    public function down(): bool;

    /**
     * Verifica se a migração já foi aplicada
     * @return bool Status da migração
     */
    public function isApplied(): bool;
}
