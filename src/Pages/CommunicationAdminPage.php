<?php
namespace CondMan\Pages;

class CommunicationAdminPage {
    public function render(): void {
        ?>
        <div class="wrap">
            <h1>Central de Comunicação</h1>
            <div class="communication-container">
                <div class="communication-channels">
                    <h2>Canais de Comunicação</h2>
                    <div class="channels-grid">
                        <div class="channel-card">
                            <h3>E-mail</h3>
                            <p>Status: Configurado</p>
                            <button class="button">Configurar</button>
                        </div>
                        <div class="channel-card">
                            <h3>SMS</h3>
                            <p>Status: Não Configurado</p>
                            <button class="button">Ativar</button>
                        </div>
                        <div class="channel-card">
                            <h3>WhatsApp</h3>
                            <p>Status: Não Configurado</p>
                            <button class="button">Ativar</button>
                        </div>
                    </div>
                </div>
                <div class="notifications-section">
                    <h2>Comunicados</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Destinatários</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Comunicados serão preenchidos dinamicamente -->
                        </tbody>
                    </table>
                    <div class="communication-actions">
                        <button class="button button-primary">Novo Comunicado</button>
                        <button class="button button-secondary">Modelos</button>
                    </div>
                </div>
                <div class="communication-templates">
                    <h2>Modelos de Comunicação</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Última Edição</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Templates serão preenchidos dinamicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}
