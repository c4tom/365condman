<?php
namespace CondMan\Pages;

class FinancialAdminPage {
    public function render(): void {
        ?>
        <div class="wrap">
            <h1>Gestão Financeira</h1>
            <div class="financial-container">
                <div class="financial-summary">
                    <div class="summary-card">
                        <h3>Saldo Atual</h3>
                        <p class="amount">R$ 125.670,45</p>
                    </div>
                    <div class="summary-card">
                        <h3>Inadimplência</h3>
                        <p class="percentage">12,5%</p>
                    </div>
                    <div class="summary-card">
                        <h3>Despesas do Mês</h3>
                        <p class="amount">R$ 45.320,00</p>
                    </div>
                </div>
                <div class="invoices-section">
                    <h2>Boletos Condominiais</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Referência</th>
                                <th>Valor</th>
                                <th>Vencimento</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Boletos serão preenchidos dinamicamente -->
                        </tbody>
                    </table>
                    <div class="invoices-actions">
                        <button class="button button-primary">Gerar Boletos</button>
                        <button class="button button-secondary">Configurações</button>
                    </div>
                </div>
                <div class="financial-reports">
                    <h2>Relatórios Financeiros</h2>
                    <div class="reports-grid">
                        <div class="report-card">
                            <h3>Fluxo de Caixa</h3>
                            <button class="button">Visualizar</button>
                        </div>
                        <div class="report-card">
                            <h3>Despesas por Categoria</h3>
                            <button class="button">Visualizar</button>
                        </div>
                        <div class="report-card">
                            <h3>Receitas</h3>
                            <button class="button">Visualizar</button>
                        </div>
                    </div>
                </div>
                <div class="delinquency-section">
                    <h2>Controle de Inadimplência</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Unidade</th>
                                <th>Valor Pendente</th>
                                <th>Meses em Atraso</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Inadimplentes serão preenchidos dinamicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}
