<?php
namespace CondMan\Domain\Interfaces;

interface MigrationInterface {
    /**
     * Executa a migração
     * @return bool Indica se a migração foi bem-sucedida
     */
    public function up(): bool;

    /**
     * Reverte a migração
     * @return bool Indica se o rollback foi bem-sucedido
     */
    public function down(): bool;

    /**
     * Verifica se a migração já foi executada
     * @return bool Indica se a migração já foi aplicada
     */
    public function isApplied(): bool;

    /**
     * Obtém a versão da migração
     * @return string Versão da migração
     */
    public function getVersion(): string;

    /**
     * Obtém a descrição da migração
     * @return string Descrição da migração
     */
    public function getDescription(): string;
}
