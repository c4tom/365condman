<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\AdminPageInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use Exception;

class AdminPageService {
    private LoggerInterface $logger;
    private array $registeredPages = [];

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * Registra uma página administrativa
     * @param AdminPageInterface $page Página administrativa
     * @param array $menuConfig Configurações de menu
     */
    public function registerAdminPage(
        AdminPageInterface $page, 
        array $menuConfig = []
    ): bool {
        try {
            $pageConfig = $page->getPageConfig();
            $defaultConfig = [
                'page_title' => $pageConfig['title'] ?? 'Página Administrativa',
                'menu_title' => $pageConfig['menu_title'] ?? 'Página Admin',
                'capability' => $pageConfig['capability'] ?? 'manage_options',
                'menu_slug' => $pageConfig['slug'] ?? 'condman-admin-page',
                'icon' => $pageConfig['icon'] ?? 'dashicons-admin-generic',
                'position' => $pageConfig['position'] ?? null
            ];

            $mergedConfig = array_merge($defaultConfig, $menuConfig);

            add_menu_page(
                $mergedConfig['page_title'],
                $mergedConfig['menu_title'],
                $mergedConfig['capability'],
                $mergedConfig['menu_slug'],
                function() use ($page) {
                    if ($page->checkPermissions()) {
                        $page->render();
                    } else {
                        wp_die('Acesso negado');
                    }
                },
                $mergedConfig['icon'],
                $mergedConfig['position']
            );

            $page->registerHooks();
            $this->registeredPages[$mergedConfig['menu_slug']] = $page;

            $this->logger->info('Página administrativa registrada', [
                'page_slug' => $mergedConfig['menu_slug'],
                'page_title' => $mergedConfig['page_title']
            ]);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Erro ao registrar página administrativa', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Registra submenu em uma página administrativa existente
     * @param string $parentSlug Slug da página pai
     * @param AdminPageInterface $page Página administrativa
     * @param array $submenuConfig Configurações de submenu
     */
    public function registerAdminSubmenu(
        string $parentSlug,
        AdminPageInterface $page,
        array $submenuConfig = []
    ): bool {
        try {
            $pageConfig = $page->getPageConfig();
            $defaultConfig = [
                'page_title' => $pageConfig['title'] ?? 'Submenu',
                'menu_title' => $pageConfig['menu_title'] ?? 'Submenu',
                'capability' => $pageConfig['capability'] ?? 'manage_options',
                'menu_slug' => $pageConfig['slug'] ?? 'condman-submenu-page'
            ];

            $mergedConfig = array_merge($defaultConfig, $submenuConfig);

            add_submenu_page(
                $parentSlug,
                $mergedConfig['page_title'],
                $mergedConfig['menu_title'],
                $mergedConfig['capability'],
                $mergedConfig['menu_slug'],
                function() use ($page) {
                    if ($page->checkPermissions()) {
                        $page->render();
                    } else {
                        wp_die('Acesso negado');
                    }
                }
            );

            $page->registerHooks();
            $this->registeredPages[$mergedConfig['menu_slug']] = $page;

            $this->logger->info('Submenu administrativo registrado', [
                'parent_slug' => $parentSlug,
                'submenu_slug' => $mergedConfig['menu_slug']
            ]);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Erro ao registrar submenu administrativo', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtém página administrativa registrada
     * @param string $slug Slug da página
     * @return AdminPageInterface|null Página administrativa
     */
    public function getAdminPage(string $slug): ?AdminPageInterface {
        return $this->registeredPages[$slug] ?? null;
    }

    /**
     * Lista todas as páginas administrativas registradas
     * @return array Páginas administrativas
     */
    public function listAdminPages(): array {
        return $this->registeredPages;
    }
}
