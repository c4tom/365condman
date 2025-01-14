<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\PluginExtensionInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use Exception;
use ReflectionClass;
use ReflectionException;

class PluginExtensionService implements PluginExtensionInterface {
    private LoggerInterface $logger;
    private array $registeredExtensions = [];

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function registerHooks(): void {
        try {
            do_action('condman_pre_register_hooks', $this);

            foreach ($this->registeredExtensions as $extension) {
                if (method_exists($extension, 'registerHooks')) {
                    $extension->registerHooks();
                }
            }

            do_action('condman_post_register_hooks', $this);

            $this->logger->info('Hooks de extensão registrados');
        } catch (Exception $e) {
            $this->logger->error('Erro ao registrar hooks de extensão', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function addCustomCapabilities(string $role, array $capabilities): bool {
        try {
            $wpRole = get_role($role);
            if (!$wpRole) {
                throw new Exception("Papel de usuário não encontrado: {$role}");
            }

            foreach ($capabilities as $capability) {
                $wpRole->add_cap($capability);
            }

            $this->logger->info('Capacidades personalizadas adicionadas', [
                'role' => $role,
                'capabilities' => $capabilities
            ]);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Erro ao adicionar capacidades personalizadas', [
                'error' => $e->getMessage(),
                'role' => $role
            ]);
            throw $e;
        }
    }

    public function registerCustomPostType(string $name, array $args): bool {
        try {
            $result = register_post_type($name, $args);

            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }

            $this->logger->info('Tipo de post personalizado registrado', [
                'post_type' => $name
            ]);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Erro ao registrar tipo de post personalizado', [
                'error' => $e->getMessage(),
                'post_type' => $name
            ]);
            throw $e;
        }
    }

    public function registerCustomTaxonomy(
        string $name, 
        array $objectTypes, 
        array $args
    ): bool {
        try {
            $result = register_taxonomy($name, $objectTypes, $args);

            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }

            $this->logger->info('Taxonomia personalizada registrada', [
                'taxonomy' => $name,
                'object_types' => $objectTypes
            ]);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Erro ao registrar taxonomia personalizada', [
                'error' => $e->getMessage(),
                'taxonomy' => $name
            ]);
            throw $e;
        }
    }

    public function addCustomAdminPage(
        string $pageTitle,
        string $menuTitle,
        string $capability,
        string $menuSlug,
        callable $renderCallback
    ): bool {
        try {
            add_menu_page(
                $pageTitle,
                $menuTitle,
                $capability,
                $menuSlug,
                $renderCallback
            );

            $this->logger->info('Página administrativa personalizada adicionada', [
                'page_title' => $pageTitle,
                'menu_slug' => $menuSlug
            ]);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Erro ao adicionar página administrativa personalizada', [
                'error' => $e->getMessage(),
                'page_title' => $pageTitle
            ]);
            throw $e;
        }
    }

    public function enqueueCustomScripts(
        string $handle, 
        string $src, 
        array $deps = [], 
        bool $inFooter = false
    ): bool {
        try {
            wp_enqueue_script(
                $handle, 
                $src, 
                $deps, 
                filemtime($src), 
                $inFooter
            );

            $this->logger->info('Script personalizado enfileirado', [
                'handle' => $handle,
                'src' => $src
            ]);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Erro ao enfileirar script personalizado', [
                'error' => $e->getMessage(),
                'handle' => $handle
            ]);
            throw $e;
        }
    }

    public function addCustomShortcode(string $tag, callable $callback): bool {
        try {
            add_shortcode($tag, $callback);

            $this->logger->info('Shortcode personalizado adicionado', [
                'tag' => $tag
            ]);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Erro ao adicionar shortcode personalizado', [
                'error' => $e->getMessage(),
                'tag' => $tag
            ]);
            throw $e;
        }
    }

    public function registerCustomWidget(string $widgetClass): bool {
        try {
            if (!class_exists($widgetClass)) {
                throw new Exception("Classe de widget não encontrada: {$widgetClass}");
            }

            register_widget($widgetClass);

            $this->logger->info('Widget personalizado registrado', [
                'widget_class' => $widgetClass
            ]);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Erro ao registrar widget personalizado', [
                'error' => $e->getMessage(),
                'widget_class' => $widgetClass
            ]);
            throw $e;
        }
    }

    public function addExtensionPoint(string $extensionPoint, callable $callback): bool {
        try {
            add_action("condman_{$extensionPoint}", $callback);

            $this->logger->info('Ponto de extensão adicionado', [
                'extension_point' => $extensionPoint
            ]);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Erro ao adicionar ponto de extensão', [
                'error' => $e->getMessage(),
                'extension_point' => $extensionPoint
            ]);
            throw $e;
        }
    }

    /**
     * Registra uma extensão para gerenciamento
     */
    public function registerExtension(object $extension): bool {
        try {
            $reflection = new ReflectionClass($extension);
            $extensionName = $reflection->getName();

            if (isset($this->registeredExtensions[$extensionName])) {
                throw new Exception("Extensão já registrada: {$extensionName}");
            }

            $this->registeredExtensions[$extensionName] = $extension;

            $this->logger->info('Extensão registrada', [
                'extension_name' => $extensionName
            ]);

            return true;
        } catch (ReflectionException|Exception $e) {
            $this->logger->error('Erro ao registrar extensão', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtém todas as extensões registradas
     */
    public function getRegisteredExtensions(): array {
        return $this->registeredExtensions;
    }
}
