<?php
namespace CondMan\Pages;

class DashboardAdminPage {
    public function render(): void {
        ?>
        <div class="wrap">
            <h1>Dashboard 365 Cond Man</h1>
            <div class="dashboard-widgets">
                <div class="dashboard-widget">
                    <h2>Resumo Geral</h2>
                    <!-- Widgets de resumo serão adicionados aqui -->
                </div>
                <div class="dashboard-widget">
                    <h2>Últimas Atividades</h2>
                    <!-- Log de atividades recentes -->
                </div>
                <div class="dashboard-widget">
                    <h2>Indicadores Principais</h2>
                    <!-- Gráficos e métricas importantes -->
                </div>
            </div>
        </div>
        <?php
    }
}
