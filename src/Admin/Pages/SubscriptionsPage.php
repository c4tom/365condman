<?php
namespace CondMan\Admin\Pages;

use CondMan\Domain\Services\SubscriptionService;
use CondMan\Domain\Repositories\SubscriptionRepositoryInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use WP_List_Table;
use DateTime;
use Exception;

class SubscriptionsPage {
    private SubscriptionService $subscriptionService;
    private SubscriptionRepositoryInterface $repository;
    private LoggerInterface $logger;

    public function __construct(
        SubscriptionService $subscriptionService,
        SubscriptionRepositoryInterface $repository,
        LoggerInterface $logger
    ) {
        $this->subscriptionService = $subscriptionService;
        $this->repository = $repository;
        $this->logger = $logger;
    }

    /**
     * Registra página de assinaturas no menu WordPress
     */
    public function registerPage(): void {
        add_menu_page(
            'Assinaturas',
            'Assinaturas',
            'manage_options',
            'condman-subscriptions',
            [$this, 'renderPage'],
            'dashicons-bell',
            32
        );
    }

    /**
     * Renderiza página de assinaturas
     */
    public function renderPage(): void {
        $action = $_GET['action'] ?? 'list';

        switch ($action) {
            case 'new':
                $this->renderNewSubscriptionForm();
                break;
            case 'edit':
                $this->renderEditSubscriptionForm();
                break;
            case 'view':
                $this->renderSubscriptionDetails();
                break;
            default:
                $this->renderSubscriptionList();
        }
    }

    /**
     * Renderiza formulário de nova assinatura
     */
    private function renderNewSubscriptionForm(): void {
        ?>
        <div class="wrap">
            <h1>Nova Assinatura</h1>
            <form method="post" action="">
                <?php wp_nonce_field('create_subscription', 'subscription_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="user_id">Usuário</label></th>
                        <td>
                            <?php 
                            $users = get_users(['fields' => ['ID', 'display_name']]);
                            ?>
                            <select name="user_id" required>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user->ID; ?>">
                                        <?php echo $user->display_name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="type">Tipo de Assinatura</label></th>
                        <td>
                            <select name="type">
                                <option value="communication">Comunicações</option>
                                <option value="financial">Financeiro</option>
                                <option value="maintenance">Manutenção</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="notification_channels">Canais de Notificação</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="notification_channels[]" value="email" checked>
                                E-mail
                            </label>
                            <label>
                                <input type="checkbox" name="notification_channels[]" value="dashboard" checked>
                                Painel Administrativo
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="preferences">Preferências</label></th>
                        <td>
                            <textarea name="preferences" rows="3" class="large-text" placeholder="JSON de preferências"></textarea>
                            <p class="description">Preferências em formato JSON, ex: {"frequency": "daily", "priority": "high"}</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="is_active">Status</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="is_active" value="1" checked>
                                Assinatura Ativa
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Criar Assinatura'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Renderiza formulário de edição de assinatura
     */
    private function renderEditSubscriptionForm(): void {
        $subscriptionId = intval($_GET['id'] ?? 0);
        $subscription = $this->subscriptionService->getSubscriptionById($subscriptionId);

        if (!$subscription) {
            wp_die('Assinatura não encontrada');
        }

        ?>
        <div class="wrap">
            <h1>Editar Assinatura</h1>
            <form method="post" action="">
                <?php wp_nonce_field('edit_subscription', 'subscription_nonce'); ?>
                <input type="hidden" name="subscription_id" value="<?php echo $subscriptionId; ?>">
                
                <table class="form-table">
                    <tr>
                        <th><label>Usuário</label></th>
                        <td>
                            <?php 
                            $user = get_user_by('ID', $subscription->getUserId());
                            echo esc_html($user->display_name);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="type">Tipo de Assinatura</label></th>
                        <td>
                            <select name="type">
                                <option value="communication" <?php selected($subscription->getType(), 'communication'); ?>>Comunicações</option>
                                <option value="financial" <?php selected($subscription->getType(), 'financial'); ?>>Financeiro</option>
                                <option value="maintenance" <?php selected($subscription->getType(), 'maintenance'); ?>>Manutenção</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="notification_channels">Canais de Notificação</label></th>
                        <td>
                            <?php $channels = $subscription->getNotificationChannels(); ?>
                            <label>
                                <input type="checkbox" name="notification_channels[]" value="email" 
                                    <?php checked(in_array('email', $channels), true); ?>>
                                E-mail
                            </label>
                            <label>
                                <input type="checkbox" name="notification_channels[]" value="dashboard" 
                                    <?php checked(in_array('dashboard', $channels), true); ?>>
                                Painel Administrativo
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="preferences">Preferências</label></th>
                        <td>
                            <textarea name="preferences" rows="3" class="large-text" placeholder="JSON de preferências"><?php 
                                echo esc_textarea(json_encode($subscription->getPreferences(), JSON_PRETTY_PRINT)); 
                            ?></textarea>
                            <p class="description">Preferências em formato JSON, ex: {"frequency": "daily", "priority": "high"}</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="is_active">Status</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="is_active" value="1" 
                                    <?php checked($subscription->isActive(), true); ?>>
                                Assinatura Ativa
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Atualizar Assinatura'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Renderiza lista de assinaturas
     */
    private function renderSubscriptionList(): void {
        $subscriptionList = new SubscriptionListTable(
            $this->repository, 
            $this->subscriptionService
        );
        $subscriptionList->prepare_items();
        ?>
        <div class="wrap">
            <h1>Assinaturas</h1>
            <a href="<?php echo admin_url('admin.php?page=condman-subscriptions&action=new'); ?>" class="page-title-action">Nova Assinatura</a>
            
            <form method="get">
                <input type="hidden" name="page" value="condman-subscriptions">
                <?php $subscriptionList->display(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Renderiza detalhes de uma assinatura
     */
    private function renderSubscriptionDetails(): void {
        $subscriptionId = intval($_GET['id'] ?? 0);
        $subscription = $this->subscriptionService->getSubscriptionById($subscriptionId);

        if (!$subscription) {
            wp_die('Assinatura não encontrada');
        }

        $user = get_user_by('ID', $subscription->getUserId());

        ?>
        <div class="wrap">
            <h1>Detalhes da Assinatura</h1>
            <table class="form-table">
                <tr>
                    <th>Usuário</th>
                    <td><?php echo esc_html($user->display_name); ?></td>
                </tr>
                <tr>
                    <th>Tipo</th>
                    <td><?php echo esc_html(ucfirst($subscription->getType())); ?></td>
                </tr>
                <tr>
                    <th>Canais de Notificação</th>
                    <td><?php echo esc_html(implode(', ', $subscription->getNotificationChannels())); ?></td>
                </tr>
                <tr>
                    <th>Preferências</th>
                    <td>
                        <pre><?php 
                            echo esc_html(json_encode($subscription->getPreferences(), JSON_PRETTY_PRINT)); 
                        ?></pre>
                    </td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td><?php echo $subscription->isActive() ? 'Ativo' : 'Inativo'; ?></td>
                </tr>
                <tr>
                    <th>Criado em</th>
                    <td><?php echo $subscription->getCreatedAt()->format('d/m/Y H:i'); ?></td>
                </tr>
            </table>
            <p>
                <a href="<?php echo admin_url('admin.php?page=condman-subscriptions&action=edit&id=' . $subscriptionId); ?>" class="button">Editar</a>
                <a href="<?php echo admin_url('admin.php?page=condman-subscriptions'); ?>" class="button">Voltar</a>
            </p>
        </div>
        <?php
    }

    /**
     * Processa formulário de criação/edição de assinatura
     */
    public function processSubscriptionForm(): void {
        if (!isset($_POST['subscription_nonce']) || 
            !wp_verify_nonce($_POST['subscription_nonce'], 
                isset($_POST['subscription_id']) ? 'edit_subscription' : 'create_subscription')) {
            wp_die('Ação não autorizada');
        }

        try {
            $notificationChannels = array_map('sanitize_text_field', $_POST['notification_channels'] ?? []);
            $preferences = json_decode(sanitize_textarea_field($_POST['preferences'] ?? '{}'), true);
            $isActive = !empty($_POST['is_active']);

            if (isset($_POST['subscription_id'])) {
                // Atualizar assinatura existente
                $subscriptionId = intval($_POST['subscription_id']);
                $subscription = $this->subscriptionService->updateSubscription(
                    $subscriptionId,
                    $preferences,
                    $isActive,
                    $notificationChannels
                );

                wp_redirect(admin_url('admin.php?page=condman-subscriptions&action=view&id=' . $subscription->getId()));
            } else {
                // Criar nova assinatura
                $userId = intval($_POST['user_id']);
                $type = sanitize_text_field($_POST['type']);

                $subscription = $this->subscriptionService->createSubscription(
                    $userId,
                    $type,
                    $preferences,
                    $isActive,
                    $notificationChannels
                );

                wp_redirect(admin_url('admin.php?page=condman-subscriptions&action=view&id=' . $subscription->getId()));
            }

            exit;
        } catch (Exception $e) {
            wp_die('Erro ao processar assinatura: ' . $e->getMessage());
        }
    }
}

/**
 * Tabela de listagem de assinaturas
 */
class SubscriptionListTable extends WP_List_Table {
    private SubscriptionRepositoryInterface $repository;
    private SubscriptionService $subscriptionService;

    public function __construct(
        SubscriptionRepositoryInterface $repository,
        SubscriptionService $subscriptionService
    ) {
        parent::__construct([
            'singular' => 'assinatura',
            'plural'   => 'assinaturas',
            'ajax'     => false
        ]);

        $this->repository = $repository;
        $this->subscriptionService = $subscriptionService;
    }

    public function get_columns(): array {
        return [
            'cb'        => '<input type="checkbox">',
            'user'      => 'Usuário',
            'type'      => 'Tipo',
            'status'    => 'Status',
            'channels'  => 'Canais',
            'created_at'=> 'Criado em'
        ];
    }

    public function column_default($item, $column_name): string {
        $user = get_user_by('ID', $item->getUserId());

        switch ($column_name) {
            case 'user':
                return esc_html($user->display_name);
            case 'type':
                return esc_html(ucfirst($item->getType()));
            case 'status':
                return $item->isActive() ? 'Ativo' : 'Inativo';
            case 'channels':
                return esc_html(implode(', ', $item->getNotificationChannels()));
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

    public function column_user($item): string {
        $user = get_user_by('ID', $item->getUserId());
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
            esc_html($user->display_name),
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

        $subscriptions = $this->repository->findByFilters($filter);
        $total_items = $this->repository->countByFilters($filter);

        $this->items = $subscriptions;

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
    }

    public function get_sortable_columns(): array {
        return [
            'type'      => ['type', false],
            'created_at'=> ['created_at', false]
        ];
    }
}
