<?php
namespace CondMan\Pages;

class SuppliersAdminPage {
    public function render(): void {
        ?>
        <div class="wrap">
            <h1>Gestão de Fornecedores</h1>
            <div class="suppliers-container">
                <div class="suppliers-list">
                    <h2>Fornecedores Cadastrados</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Categoria</th>
                                <th>CNPJ</th>
                                <th>Contato</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Fornecedores serão preenchidos dinamicamente -->
                        </tbody>
                    </table>
                </div>
                <div class="contracts-section">
                    <h2>Contratos Ativos</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Fornecedor</th>
                                <th>Serviço</th>
                                <th>Valor</th>
                                <th>Vigência</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Contratos serão preenchidos dinamicamente -->
                        </tbody>
                    </table>
                </div>
                <div class="service-history">
                    <h2>Histórico de Serviços</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Fornecedor</th>
                                <th>Serviço</th>
                                <th>Data</th>
                                <th>Avaliação</th>
                                <th>Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Histórico de serviços será preenchido dinamicamente -->
                        </tbody>
                    </table>
                </div>
                <div class="suppliers-actions">
                    <button class="button button-primary">Cadastrar Fornecedor</button>
                    <button class="button button-secondary">Novo Contrato</button>
                </div>
            </div>
        </div>
        <?php
    }
}
