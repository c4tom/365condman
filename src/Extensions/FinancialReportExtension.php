<?php
namespace CondMan\Extensions;

use CondMan\Domain\Interfaces\PluginExtensionInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use CondMan\Domain\Services\FinancialReportService;

class FinancialReportExtension {
    private PluginExtensionInterface $extensionService;
    private LoggerInterface $logger;
    private FinancialReportService $reportService;

    public function __construct(
        PluginExtensionInterface $extensionService,
        LoggerInterface $logger,
        FinancialReportService $reportService
    ) {
        $this->extensionService = $extensionService;
        $this->logger = $logger;
        $this->reportService = $reportService;
    }

    public function registerHooks(): void {
        try {
            // Registra shortcode para relatórios financeiros
            $this->extensionService->addCustomShortcode(
                'condman_financial_report', 
                [$this, 'renderFinancialReportShortcode']
            );

            // Registra ponto de extensão para relatórios personalizados
            $this->extensionService->addExtensionPoint(
                'financial_report_generation', 
                [$this, 'generateCustomFinancialReport']
            );

            // Adiciona página de menu para relatórios avançados
            $this->extensionService->addCustomAdminPage(
                'Relatórios Financeiros Avançados',
                'Relatórios Financeiros',
                'manage_financial_reports',
                'condman-financial-reports',
                [$this, 'renderFinancialReportsPage']
            );

            $this->logger->info('Hooks da extensão de relatórios financeiros registrados');
        } catch (\Exception $e) {
            $this->logger->error('Erro ao registrar hooks da extensão', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function renderFinancialReportShortcode($atts): string {
        try {
            $atts = shortcode_atts([
                'type' => 'monthly',
                'year' => date('Y'),
                'month' => date('m')
            ], $atts);

            $report = $this->reportService->generateReport(
                $atts['type'], 
                $atts['year'], 
                $atts['month']
            );

            ob_start();
            // Renderiza relatório usando template
            include CONDMAN_PLUGIN_DIR . '/templates/financial-report-shortcode.php';
            return ob_get_clean();
        } catch (\Exception $e) {
            $this->logger->error('Erro ao renderizar shortcode de relatório', [
                'error' => $e->getMessage()
            ]);
            return "Erro ao gerar relatório";
        }
    }

    public function generateCustomFinancialReport(array $args): void {
        try {
            $customReport = $this->reportService->generateCustomReport($args);
            
            do_action('condman_custom_report_generated', $customReport);

            $this->logger->info('Relatório financeiro personalizado gerado', [
                'report_args' => $args
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao gerar relatório personalizado', [
                'error' => $e->getMessage(),
                'args' => $args
            ]);
        }
    }

    public function renderFinancialReportsPage(): void {
        try {
            // Verifica permissões
            if (!current_user_can('manage_financial_reports')) {
                wp_die('Você não tem permissão para acessar esta página.');
            }

            // Recupera relatórios
            $reports = $this->reportService->listReports([
                'limit' => 10,
                'order' => 'DESC'
            ]);

            // Renderiza página de relatórios
            include CONDMAN_PLUGIN_DIR . '/templates/admin/financial-reports-page.php';
        } catch (\Exception $e) {
            $this->logger->error('Erro ao renderizar página de relatórios', [
                'error' => $e->getMessage()
            ]);
            wp_die('Erro ao carregar página de relatórios');
        }
    }
}
