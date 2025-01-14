<?php
/**
 * Template da página de dashboard administrativo
 *
 * @package CondMan\Admin
 */

// Prevenir acesso direto ao arquivo
defined( 'ABSPATH' ) || exit;

// Verificar permissões de acesso
if ( ! current_user_can( '365condman_manage_condominiums' ) ) {
    wp_die( __( 'Você não tem permissão para acessar esta página.', '365condman' ) );
}
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <div class="card-container">
        <div class="card">
            <h2><?php _e( 'Bem-vindo ao 365 Cond Man', '365condman' ); ?></h2>
            <p><?php _e( 'Seu sistema de gestão de condomínios.', '365condman' ); ?></p>
        </div>

        <div class="card">
            <h3><?php _e( 'Estatísticas Rápidas', '365condman' ); ?></h3>
            <ul>
                <li>
                    <?php
                    $condominiums_count = wp_count_posts( 'condominium' )->publish;
                    printf(
                        /* translators: %d: Number of condominiums */
                        __( 'Condomínios Cadastrados: %d', '365condman' ),
                        $condominiums_count
                    );
                    ?>
                </li>
            </ul>
        </div>
    </div>
</div>

<style>
    .card-container {
        display: flex;
        gap: 20px;
    }
    .card {
        background: white;
        border: 1px solid #e2e4e7;
        padding: 20px;
        border-radius: 4px;
        flex: 1;
    }
</style>
