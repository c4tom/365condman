<?php
namespace CondMan\Domain\Interfaces;

interface AdminPageInterface {
    /**
     * Renderiza o conteúdo da página administrativa
     */
    public function render(): void;

    /**
     * Registra ações e filtros específicos da página
     */
    public function registerHooks(): void;

    /**
     * Enfileira scripts e estilos específicos da página
     */
    public function enqueueAssets(): void;

    /**
     * Processa ações de formulário
     * @param array $data Dados do formulário
     * @return array Resultado do processamento
     */
    public function processFormActions(array $data): array;

    /**
     * Obtém configurações da página
     * @return array Configurações da página administrativa
     */
    public function getPageConfig(): array;

    /**
     * Valida permissões de acesso
     * @param int|null $userId ID do usuário
     * @return bool Possui permissão
     */
    public function checkPermissions(?int $userId = null): bool;
}
