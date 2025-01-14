<?php
namespace CondMan\Pages;

class ReportsAdminPage {
    public function render(): void {
        ?>
        <div class="wrap">
            <h1>Relatórios Gerenciais</h1>
            <div class="reports-container">
                <div class="reports-categories">
                    <h2>Categorias de Relatórios</h2>
                    <div class="reports-grid">
                        <div class="report-card">
                            <h3>Financeiros</h3>
                            <ul>
                                <li>Fluxo de Caixa</li>
                                <li>Receitas e Despesas</li>
                                <li>Inadimplência</li>
                            </ul>
                            <button class="button">Gerar</button>
                        </div>
                        <div class="report-card">
                            <h3>Operacionais</h3>
                            <ul>
                                <li>Ocorrências</li>
                                <li>Reservas de Áreas</li>
                                <li>Comunicação</li>
                            </ul>
                            <button class="button">Gerar</button>
                        </div>
                        <div class="report-card">
                            <h3>Gestão</h3>
                            <ul>
                                <li>Indicadores</li>
                                <li>Desempenho</li>
                                <li>Projeções</li>
                            </ul>
                            <button class="button">Gerar</button>
                        </div>
                    </div>
                </div>
                <div class="custom-reports-section">
                    <h2>Relatórios Personalizados</h2>
                    <div class="custom-reports-filters">
                        <select>
                            <option>Selecione a Categoria</option>
                            <option>Financeiro</option>
                            <option>Operacional</option>
                            <option>Gestão</option>
                        </select>
                        <input type="date" placeholder="Data Inicial">
                        <input type="date" placeholder="Data Final">
                        <button class="button button-primary">Gerar Relatório</button>
                    </div>
                </div>
                <div class="recent-reports">
                    <h2>Relatórios Recentes</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Categoria</th>
                                <th>Data</th>
                                <th>Formato</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Relatórios recentes serão preenchidos dinamicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}
