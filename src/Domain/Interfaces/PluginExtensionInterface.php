<?php
namespace CondMan\Domain\Interfaces;

interface PluginExtensionInterface {
    /**
     * Registra hooks e filtros personalizados
     */
    public function registerHooks(): void;

    /**
     * Adiciona capacidades personalizadas
     * @param string $role Papel do usuário
     * @param array $capabilities Capacidades a serem adicionadas
     */
    public function addCustomCapabilities(string $role, array $capabilities): bool;

    /**
     * Registra tipos de post personalizados
     * @param string $name Nome do tipo de post
     * @param array $args Argumentos de configuração
     */
    public function registerCustomPostType(string $name, array $args): bool;

    /**
     * Registra taxonomias personalizadas
     * @param string $name Nome da taxonomia
     * @param array $objectTypes Tipos de objetos associados
     * @param array $args Argumentos de configuração
     */
    public function registerCustomTaxonomy(
        string $name, 
        array $objectTypes, 
        array $args
    ): bool;

    /**
     * Adiciona páginas de menu personalizadas
     * @param string $pageTitle Título da página
     * @param string $menuTitle Título do menu
     * @param string $capability Capacidade necessária
     * @param string $menuSlug Slug do menu
     * @param callable $renderCallback Função de renderização
     */
    public function addCustomAdminPage(
        string $pageTitle,
        string $menuTitle,
        string $capability,
        string $menuSlug,
        callable $renderCallback
    ): bool;

    /**
     * Registra scripts e estilos personalizados
     * @param string $handle Identificador do script/estilo
     * @param string $src Caminho do arquivo
     * @param array $deps Dependências
     * @param bool $inFooter Se o script deve ser carregado no rodapé
     */
    public function enqueueCustomScripts(
        string $handle, 
        string $src, 
        array $deps = [], 
        bool $inFooter = false
    ): bool;

    /**
     * Adiciona shortcodes personalizados
     * @param string $tag Nome do shortcode
     * @param callable $callback Função de processamento
     */
    public function addCustomShortcode(string $tag, callable $callback): bool;

    /**
     * Registra widgets personalizados
     * @param string $widgetClass Classe do widget
     */
    public function registerCustomWidget(string $widgetClass): bool;

    /**
     * Adiciona pontos de extensão para integrações
     * @param string $extensionPoint Nome do ponto de extensão
     * @param callable $callback Função de callback
     */
    public function addExtensionPoint(string $extensionPoint, callable $callback): bool;
}
