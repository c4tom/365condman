<?php
namespace CondMan\Pages;

class UnitsResidentsAdminPage {
    public function render(): void {
        ?>
        <div class="wrap">
            <h1>Unidades e Moradores</h1>
            <div class="units-residents-container">
                <div class="units-section">
                    <h2>Unidades Cadastradas</h2>
                    <!-- Tabela de unidades -->
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Bloco</th>
                                <th>Número</th>
                                <th>Tipo</th>
                                <th>Proprietário</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Linhas de unidades serão preenchidas dinamicamente -->
                        </tbody>
                    </table>
                </div>
                <div class="residents-section">
                    <h2>Moradores</h2>
                    <!-- Lista de moradores -->
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Unidade</th>
                                <th>Contato</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Linhas de moradores serão preenchidas dinamicamente -->
                        </tbody>
                    </table>
                </div>
                <div class="actions-section">
                    <button class="button button-primary">Cadastrar Nova Unidade</button>
                    <button class="button button-primary">Adicionar Morador</button>
                </div>
            </div>
        </div>
        <?php
    }
}
