<?php
namespace CondMan\Pages;

class CommonAreasAdminPage {
    public function render(): void {
        ?>
        <div class="wrap">
            <h1>Reserva de Áreas Comuns</h1>
            <div class="common-areas-container">
                <div class="areas-list">
                    <h2>Áreas Disponíveis</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Área</th>
                                <th>Capacidade</th>
                                <th>Regras</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Áreas serão preenchidas dinamicamente -->
                        </tbody>
                    </table>
                </div>
                <div class="reservations-calendar">
                    <h2>Calendário de Reservas</h2>
                    <!-- Calendário interativo de reservas -->
                </div>
                <div class="reservations-list">
                    <h2>Reservas Pendentes</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Área</th>
                                <th>Solicitante</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Reservas serão preenchidas dinamicamente -->
                        </tbody>
                    </table>
                </div>
                <div class="common-areas-actions">
                    <button class="button button-primary">Cadastrar Nova Área</button>
                    <button class="button button-secondary">Configurar Regras</button>
                </div>
            </div>
        </div>
        <?php
    }
}
