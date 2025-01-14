<?php
namespace CondMan\Admin\Pages;

use CondMan\Domain\Services\InternalCommunicationManagementService;
use CondMan\Domain\Repositories\InternalCommunicationRepositoryInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use DateTime;
use WP_List_Table;

class InternalCommunicationPage {
    private InternalCommunicationManagementService $managementService;
    private InternalCommunicationRepositoryInterface $repository;
    private LoggerInterface $logger;

    public function __construct(
        InternalCommunicationManagementService $managementService,
        InternalCommunicationRepositoryInterface $repository,
        LoggerInterface $logger
    ) {
        $this->managementService = $managementService;
        $this->repository = $repository;
        $this->logger = $logger;
    }

    /**
     * Registra página de comunicados no menu WordPress
     */
    public function registerPage(): void {
        add_menu_page(
            'Comunicados Internos',
            'Comunicados',
            'manage_options',
            'condman-internal-communications',
            [$this, 'renderPage'],
            'dashicons-megaphone',
            30
        );
    }

    /**
     * Renderiza página de comunicados
     */
    public function renderPage(): void {
        $action = $_GET['action'] ?? 'list';

        switch ($action) {
            case 'new':
                $this->renderNewCommunicationForm();
                break;
            case 'edit':
                $this->renderEditCommunicationForm();
                break;
            case 'view':
                $this->renderCommunicationDetails();
                break;
            default:
                $this->renderCommunicationList();
        }
    }

    /**
     * Renderiza formulário de novo comunicado
     */
    private function renderNewCommunicationForm(): void {
        // Lógica de renderização do formulário
        ?>
        <div class="wrap">
            <h1>Novo Comunicado</h1>
            <form method="post" action="">
                <?php wp_nonce_field('create_communication', 'communication_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="title">Título</label></th>
                        <td><input type="text" name="title" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="content">Conteúdo</label></th>
                        <td><textarea name="content" required rows="5" class="large-text"></textarea></td>
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
                        <th><label for="recipients">Destinatários</label></th>
                        <td>
                            <?php 
                            $users = get_users(['fields' => ['ID', 'display_name']]);
                            foreach ($users as $user): ?>
                                <label>
                                    <input type="checkbox" name="recipients[]" value="<?php echo $user->ID; ?>">
                                    <?php echo $user->display_name; ?>
                                </label><br>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="scheduled_for">Agendar para</label></th>
                        <td><input type="datetime-local" name="scheduled_for"></td>
                    </tr>
                </table>

                <?php submit_button('Criar Comunicado'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Renderiza lista de comunicados
     */
    private function renderCommunicationList(): void {
        $communicationList = new CommunicationListTable(
            $this->repository, 
            $this->managementService
        );
        $communicationList->prepare_items();
        ?>
        <div class="wrap">
            <h1>Comunicados Internos</h1>
            <a href="<?php echo admin_url('admin.php?page=condman-internal-communications&action=new'); ?>" class="page-title-action">Novo Comunicado</a>
            
            <form method="get">
                <input type="hidden" name="page" value="condman-internal-communications">
                <?php $communicationList->display(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Processa formulário de criação de comunicado
     */
    public function processCommunicationForm(): void {
        if (!isset($_POST['communication_nonce']) || 
            !wp_verify_nonce($_POST['communication_nonce'], 'create_communication')) {
            wp_die('Ação não autorizada');
        }

        try {
            $title = sanitize_text_field($_POST['title']);
            $content = wp_kses_post($_POST['content']);
            $type = sanitize_text_field($_POST['type']);
            $recipients = array_map('intval', $_POST['recipients'] ?? []);
            $scheduledFor = !empty($_POST['scheduled_for']) 
                ? new DateTime(sanitize_text_field($_POST['scheduled_for'])) 
                : null;

            $currentUserId = get_current_user_id();

            $communication = $this->managementService->createCommunication(
                $currentUserId, 
                $title, 
                $content, 
                $type, 
                $recipients, 
                $scheduledFor
            );

            wp_redirect(admin_url('admin.php?page=condman-internal-communications&action=view&id=' . $communication->getId()));
            exit;
        } catch (Exception $e) {
            wp_die('Erro ao criar comunicado: ' . $e->getMessage());
        }
    }
}

/**
 * Tabela de listagem de comunicados
 */
class CommunicationListTable extends WP_List_Table {
    private InternalCommunicationRepositoryInterface $repository;
    private InternalCommunicationManagementService $managementService;

    public function __construct(
        InternalCommunicationRepositoryInterface $repository,
        InternalCommunicationManagementService $managementService
    ) {
        parent::__construct([
            'singular' => 'comunicado',
            'plural'   => 'comunicados',
            'ajax'     => false
        ]);

        $this->repository = $repository;
        $this->managementService = $managementService;
    }

    public function get_columns(): array {
        return [
            'cb'        => '<input type="checkbox">',
            'title'     => 'Título',
            'type'      => 'Tipo',
            'status'    => 'Status',
            'sent_at'   => 'Enviado em',
            'read_rate' => 'Taxa de Leitura'
        ];
    }

    public function column_default($item, $column_name): string {
        switch ($column_name) {
            case 'title':
                return $item->getTitle();
            case 'type':
                return ucfirst($item->getType());
            case 'status':
                return ucfirst($item->getStatus());
            case 'sent_at':
                return $item->getSentAt() ? $item->getSentAt()->format('d/m/Y H:i') : 'Não enviado';
            case 'read_rate':
                $stats = $this->managementService->getReadStatistics($item->getId());
                return $stats['read_rate'] . '%';
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

    public function column_title($item): string {
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
            $item->getTitle(),
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
            'start_date' => new DateTime('-30 days')
        ];

        $communications = $this->repository->findByFilters($filter);
        $total_items = $this->repository->countByFilters($filter);

        $this->items = $communications;

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
    }

    public function get_sortable_columns(): array {
        return [
            'title'     => ['title', false],
            'type'      => ['type', false],
            'sent_at'   => ['sent_at', false]
        ];
    }
}
