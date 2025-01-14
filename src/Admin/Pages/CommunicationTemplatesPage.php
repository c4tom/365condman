<?php
namespace CondMan\Admin\Pages;

use CondMan\Domain\Services\CommunicationTemplateService;
use CondMan\Domain\Repositories\CommunicationTemplateRepositoryInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use WP_List_Table;
use DateTime;
use Exception;

class CommunicationTemplatesPage {
    private CommunicationTemplateService $templateService;
    private CommunicationTemplateRepositoryInterface $repository;
    private LoggerInterface $logger;

    public function __construct(
        CommunicationTemplateService $templateService,
        CommunicationTemplateRepositoryInterface $repository,
        LoggerInterface $logger
    ) {
        $this->templateService = $templateService;
        $this->repository = $repository;
        $this->logger = $logger;
    }

    /**
     * Registra página de templates no menu WordPress
     */
    public function registerPage(): void {
        add_menu_page(
            'Templates de Comunicação',
            'Templates',
            'manage_options',
            'condman-communication-templates',
            [$this, 'renderPage'],
            'dashicons-format-aside',
            31
        );
    }

    /**
     * Renderiza página de templates
     */
    public function renderPage(): void {
        $action = $_GET['action'] ?? 'list';

        switch ($action) {
            case 'new':
                $this->renderNewTemplateForm();
                break;
            case 'edit':
                $this->renderEditTemplateForm();
                break;
            case 'view':
                $this->renderTemplateDetails();
                break;
            default:
                $this->renderTemplateList();
        }
    }

    /**
     * Renderiza formulário de novo template
     */
    private function renderNewTemplateForm(): void {
        ?>
        <div class="wrap">
            <h1>Novo Template de Comunicação</h1>
            <form method="post" action="">
                <?php wp_nonce_field('create_template', 'template_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="name">Nome do Template</label></th>
                        <td><input type="text" name="name" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="title">Título Padrão</label></th>
                        <td><input type="text" name="title" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="content">Conteúdo</label></th>
                        <td>
                            <textarea name="content" required rows="10" class="large-text" placeholder="Use {{placeholders}} para substituição dinâmica"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="type">Tipo</label></th>
                        <td>
                            <select name="type">
                                <option value="general">Geral</option>
                                <option value="financial">Financeiro</option>
                                <option value="maintenance">Manutenção</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="placeholders">Placeholders</label></th>
                        <td>
                            <input type="text" name="placeholders" class="regular-text" 
                                   placeholder="nome,data,valor (separados por vírgula)">
                            <p class="description">Placeholders para substituição no template</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="is_default">Template Padrão</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="is_default" value="1">
                                Definir como template padrão para este tipo
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Criar Template'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Renderiza formulário de edição de template
     */
    private function renderEditTemplateForm(): void {
        $templateId = intval($_GET['id'] ?? 0);
        $template = $this->templateService->getTemplateById($templateId);

        if (!$template) {
            wp_die('Template não encontrado');
        }

        ?>
        <div class="wrap">
            <h1>Editar Template de Comunicação</h1>
            <form method="post" action="">
                <?php wp_nonce_field('edit_template', 'template_nonce'); ?>
                <input type="hidden" name="template_id" value="<?php echo $templateId; ?>">
                
                <table class="form-table">
                    <tr>
                        <th><label for="name">Nome do Template</label></th>
                        <td><input type="text" name="name" required class="regular-text" value="<?php echo esc_attr($template->getName()); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="title">Título Padrão</label></th>
                        <td><input type="text" name="title" required class="regular-text" value="<?php echo esc_attr($template->getTitle()); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="content">Conteúdo</label></th>
                        <td>
                            <textarea name="content" required rows="10" class="large-text"><?php echo esc_textarea($template->getContent()); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="type">Tipo</label></th>
                        <td>
                            <select name="type">
                                <option value="general" <?php selected($template->getType(), 'general'); ?>>Geral</option>
                                <option value="financial" <?php selected($template->getType(), 'financial'); ?>>Financeiro</option>
                                <option value="maintenance" <?php selected($template->getType(), 'maintenance'); ?>>Manutenção</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="placeholders">Placeholders</label></th>
                        <td>
                            <input type="text" name="placeholders" class="regular-text" 
                                   value="<?php echo esc_attr(implode(',', $template->getPlaceholders())); ?>"
                                   placeholder="nome,data,valor (separados por vírgula)">
                            <p class="description">Placeholders para substituição no template</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="is_default">Template Padrão</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="is_default" value="1" <?php checked($template->isDefault(), true); ?>>
                                Definir como template padrão para este tipo
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Atualizar Template'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Renderiza lista de templates
     */
    private function renderTemplateList(): void {
        $templateList = new TemplateListTable(
            $this->repository, 
            $this->templateService
        );
        $templateList->prepare_items();
        ?>
        <div class="wrap">
            <h1>Templates de Comunicação</h1>
            <a href="<?php echo admin_url('admin.php?page=condman-communication-templates&action=new'); ?>" class="page-title-action">Novo Template</a>
            
            <form method="get">
                <input type="hidden" name="page" value="condman-communication-templates">
                <?php $templateList->display(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Renderiza detalhes de um template
     */
    private function renderTemplateDetails(): void {
        $templateId = intval($_GET['id'] ?? 0);
        $template = $this->templateService->getTemplateById($templateId);

        if (!$template) {
            wp_die('Template não encontrado');
        }

        ?>
        <div class="wrap">
            <h1>Detalhes do Template</h1>
            <table class="form-table">
                <tr>
                    <th>Nome</th>
                    <td><?php echo esc_html($template->getName()); ?></td>
                </tr>
                <tr>
                    <th>Título</th>
                    <td><?php echo esc_html($template->getTitle()); ?></td>
                </tr>
                <tr>
                    <th>Conteúdo</th>
                    <td><?php echo wp_kses_post($template->getContent()); ?></td>
                </tr>
                <tr>
                    <th>Tipo</th>
                    <td><?php echo esc_html(ucfirst($template->getType())); ?></td>
                </tr>
                <tr>
                    <th>Placeholders</th>
                    <td><?php echo esc_html(implode(', ', $template->getPlaceholders())); ?></td>
                </tr>
                <tr>
                    <th>Criado em</th>
                    <td><?php echo $template->getCreatedAt()->format('d/m/Y H:i'); ?></td>
                </tr>
                <tr>
                    <th>Template Padrão</th>
                    <td><?php echo $template->isDefault() ? 'Sim' : 'Não'; ?></td>
                </tr>
            </table>
            <p>
                <a href="<?php echo admin_url('admin.php?page=condman-communication-templates&action=edit&id=' . $templateId); ?>" class="button">Editar</a>
                <a href="<?php echo admin_url('admin.php?page=condman-communication-templates'); ?>" class="button">Voltar</a>
            </p>
        </div>
        <?php
    }

    /**
     * Processa formulário de criação/edição de template
     */
    public function processTemplateForm(): void {
        if (!isset($_POST['template_nonce']) || 
            !wp_verify_nonce($_POST['template_nonce'], 
                isset($_POST['template_id']) ? 'edit_template' : 'create_template')) {
            wp_die('Ação não autorizada');
        }

        try {
            $name = sanitize_text_field($_POST['name']);
            $title = sanitize_text_field($_POST['title']);
            $content = wp_kses_post($_POST['content']);
            $type = sanitize_text_field($_POST['type']);
            $placeholders = array_map('sanitize_text_field', 
                explode(',', $_POST['placeholders'] ?? '')
            );
            $isDefault = !empty($_POST['is_default']);
            $currentUserId = get_current_user_id();

            if (isset($_POST['template_id'])) {
                // Atualizar template existente
                $templateId = intval($_POST['template_id']);
                $template = $this->templateService->updateTemplate(
                    $templateId,
                    $name,
                    $title,
                    $content,
                    $type,
                    [],
                    $isDefault,
                    $placeholders
                );

                wp_redirect(admin_url('admin.php?page=condman-communication-templates&action=view&id=' . $template->getId()));
            } else {
                // Criar novo template
                $template = $this->templateService->createTemplate(
                    $currentUserId,
                    $name,
                    $title,
                    $content,
                    $type,
                    [],
                    $isDefault,
                    $placeholders
                );

                wp_redirect(admin_url('admin.php?page=condman-communication-templates&action=view&id=' . $template->getId()));
            }

            exit;
        } catch (Exception $e) {
            wp_die('Erro ao processar template: ' . $e->getMessage());
        }
    }
}

/**
 * Tabela de listagem de templates
 */
class TemplateListTable extends WP_List_Table {
    private CommunicationTemplateRepositoryInterface $repository;
    private CommunicationTemplateService $templateService;

    public function __construct(
        CommunicationTemplateRepositoryInterface $repository,
        CommunicationTemplateService $templateService
    ) {
        parent::__construct([
            'singular' => 'template',
            'plural'   => 'templates',
            'ajax'     => false
        ]);

        $this->repository = $repository;
        $this->templateService = $templateService;
    }

    public function get_columns(): array {
        return [
            'cb'        => '<input type="checkbox">',
            'name'      => 'Nome',
            'type'      => 'Tipo',
            'is_default'=> 'Padrão',
            'created_at'=> 'Criado em'
        ];
    }

    public function column_default($item, $column_name): string {
        switch ($column_name) {
            case 'name':
                return $item->getName();
            case 'type':
                return ucfirst($item->getType());
            case 'is_default':
                return $item->isDefault() ? 'Sim' : 'Não';
            case 'created_at':
                return $item->getCreatedAt()->format('d/m/Y H:i');
            default:
                return '';
        }
    }

    public function column_cb($item): string {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],
            $item->getId()
        );
    }

    public function column_name($item): string {
        $actions = [
            'view'   => sprintf(
                '<a href="?page=%s&action=view&id=%s">Ver</a>',
                $_REQUEST['page'],
                $item->getId()
            ),
            'edit'   => sprintf(
                '<a href="?page=%s&action=edit&id=%s">Editar</a>',
                $_REQUEST['page'],
                $item->getId()
            ),
            'delete' => sprintf(
                '<a href="?page=%s&action=delete&id=%s" onclick="return confirm(\'Tem certeza?\')">Excluir</a>',
                $_REQUEST['page'],
                $item->getId()
            ),
        ];

        return sprintf(
            '%1$s %2$s',
            $item->getName(),
            $this->row_actions($actions)
        );
    }

    public function prepare_items(): void {
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        $per_page = 10;
        $current_page = $this->get_pagenum();

        $filter = [
            'limit' => $per_page,
            'start_date' => new DateTime('-90 days')
        ];

        $templates = $this->repository->findByFilters($filter);
        $total_items = $this->repository->countByFilters($filter);

        $this->items = $templates;

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
    }

    public function get_sortable_columns(): array {
        return [
            'name'      => ['name', false],
            'type'      => ['type', false],
            'created_at'=> ['created_at', false]
        ];
    }
}
