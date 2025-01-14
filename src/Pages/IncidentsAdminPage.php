<?php
namespace CondMan\Pages;

class IncidentsAdminPage {
    public function render(): void {
        ?>
        <div class="wrap">
            <h1>Registro de Ocorrências</h1>
            <div class="incidents-container">
                <div class="incidents-filters">
                    <select>
                        <option>Todas as Categorias</option>
                        <option>Manutenção</option>
                        <option>Segurança</option>
                        <option>Infraestrutura</option>
                    </select>
                    <select>
                        <option>Todos os Status</option>
                        <option>Aberto</option>
                        <option>Em Andamento</option>
                        <option>Resolvido</option>
                    </select>
                </div>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Categoria</th>
                            <th>Descrição</th>
                            <th>Unidade</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Ocorrências serão preenchidas dinamicamente -->
                    </tbody>
                </table>
                <div class="incidents-actions">
                    <button class="button button-primary">Registrar Nova Ocorrência</button>
                </div>
            </div>
        </div>
        <?php
    }
}
