<?php
namespace CondMan\Pages;

class AdminMenuManager {
    public function __construct() {
        add_action('admin_menu', [$this, 'registerAdminMenus']);
    }

    public function registerAdminMenus(): void {
        // Menu Principal
        add_menu_page(
            '365 Cond Man',           // Título da página
            '365 Cond Man',            // Texto do menu
            'manage_options',          // Capacidade necessária
            'condman-dashboard',       // Slug do menu
            [new DashboardAdminPage(), 'render'], // Função de callback
            'dashicons-building',      // Ícone
            2                          // Posição no menu
        );

        // Submenu: Unidades e Moradores
        add_submenu_page(
            'condman-dashboard',       // Slug do menu pai
            'Unidades e Moradores',    // Título da página
            'Unidades e Moradores',    // Texto do submenu
            'manage_options',          // Capacidade necessária
            'condman-units-residents', 
            [new UnitsResidentsAdminPage(), 'render']
        );

        // Submenu: Ocorrências
        add_submenu_page(
            'condman-dashboard', 
            'Registro de Ocorrências', 
            'Ocorrências', 
            'manage_options', 
            'condman-incidents', 
            [new IncidentsAdminPage(), 'render']
        );

        // Submenu: Áreas Comuns
        add_submenu_page(
            'condman-dashboard', 
            'Reserva de Áreas Comuns', 
            'Áreas Comuns', 
            'manage_options', 
            'condman-common-areas', 
            [new CommonAreasAdminPage(), 'render']
        );

        // Submenu: Comunicação
        add_submenu_page(
            'condman-dashboard', 
            'Central de Comunicação', 
            'Comunicação', 
            'manage_options', 
            'condman-communication', 
            [new CommunicationAdminPage(), 'render']
        );

        // Submenu: Financeiro
        add_submenu_page(
            'condman-dashboard', 
            'Gestão Financeira', 
            'Financeiro', 
            'manage_options', 
            'condman-financial', 
            [new FinancialAdminPage(), 'render']
        );

        // Submenu: Fornecedores
        add_submenu_page(
            'condman-dashboard', 
            'Gestão de Fornecedores', 
            'Fornecedores', 
            'manage_options', 
            'condman-suppliers', 
            [new SuppliersAdminPage(), 'render']
        );

        // Submenu: Relatórios
        add_submenu_page(
            'condman-dashboard', 
            'Relatórios Gerenciais', 
            'Relatórios', 
            'manage_options', 
            'condman-reports', 
            [new ReportsAdminPage(), 'render']
        );

        // Submenu: Configurações
        add_submenu_page(
            'condman-dashboard', 
            'Configurações Avançadas', 
            'Configurações', 
            'manage_options', 
            'condman-settings', 
            [new SettingsAdminPage(), 'render']
        );
    }
}
