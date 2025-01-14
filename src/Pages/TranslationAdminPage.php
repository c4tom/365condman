<?php
namespace CondMan\Pages;

use CondMan\Domain\Interfaces\TranslationManagementInterface;
use CondMan\Domain\Interfaces\InternationalizationInterface;
use CondMan\Domain\Interfaces\LoggerInterface;

class TranslationAdminPage {
    private TranslationManagementInterface $translationManager;
    private InternationalizationInterface $internationalization;
    private LoggerInterface $logger;

    public function __construct(
        TranslationManagementInterface $translationManager,
        InternationalizationInterface $internationalization,
        LoggerInterface $logger
    ) {
        $this->translationManager = $translationManager;
        $this->internationalization = $internationalization;
        $this->logger = $logger;

        add_action('admin_menu', [$this, 'addTranslationMenu']);
        add_action('admin_init', [$this, 'registerTranslationSettings']);
    }

    public function addTranslationMenu(): void {
        add_menu_page(
            'Gerenciamento de Traduções',
            'Traduções',
            'manage_options',
            'condman-translations',
            [$this, 'renderTranslationPage'],
            'dashicons-translation',
            30
        );
    }

    public function registerTranslationSettings(): void {
        register_setting('condman_translation_settings', 'condman_translation_options');

        add_settings_section(
            'translation_general_settings',
            'Configurações Gerais de Tradução',
            [$this, 'generalSettingsSection'],
            'condman-translations'
        );

        add_settings_field(
            'default_language',
            'Idioma Padrão',
            [$this, 'renderDefaultLanguageField'],
            'condman-translations',
            'translation_general_settings'
        );
    }

    public function generalSettingsSection(): void {
        echo '<p>Configurações globais para o sistema de traduções.</p>';
    }

    public function renderDefaultLanguageField(): void {
        $supportedLanguages = $this->internationalization->getSupportedLanguages();
        $currentLanguage = get_option('condman_default_language', 'pt_BR');

        echo '<select name="condman_default_language">';
        foreach ($supportedLanguages as $code => $name) {
            $selected = selected($currentLanguage, $code, false);
            echo "<option value='{$code}' {$selected}>{$name}</option>";
        }
        echo '</select>';
    }

    public function renderTranslationPage(): void {
        $domains = $this->translationManager->listTranslationDomains();
        $supportedLanguages = $this->internationalization->getSupportedLanguages();
        $currentTab = $_GET['tab'] ?? 'domains';

        ?>
        <div class="wrap">
            <h1>Gerenciamento de Traduções</h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=condman-translations&tab=domains" 
                   class="nav-tab <?= $currentTab === 'domains' ? 'nav-tab-active' : '' ?>">
                    Domínios
                </a>
                <a href="?page=condman-translations&tab=import" 
                   class="nav-tab <?= $currentTab === 'import' ? 'nav-tab-active' : '' ?>">
                    Importar
                </a>
                <a href="?page=condman-translations&tab=export" 
                   class="nav-tab <?= $currentTab === 'export' ? 'nav-tab-active' : '' ?>">
                    Exportar
                </a>
                <a href="?page=condman-translations&tab=reports" 
                   class="nav-tab <?= $currentTab === 'reports' ? 'nav-tab-active' : '' ?>">
                    Relatórios
                </a>
            </nav>

            <div class="tab-content">
                <?php
                switch ($currentTab) {
                    case 'domains':
                        $this->renderDomainTab($domains, $supportedLanguages);
                        break;
                    case 'import':
                        $this->renderImportTab($domains, $supportedLanguages);
                        break;
                    case 'export':
                        $this->renderExportTab($domains, $supportedLanguages);
                        break;
                    case 'reports':
                        $this->renderReportsTab($domains, $supportedLanguages);
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function renderDomainTab(array $domains, array $supportedLanguages): void {
        echo '<div class="domains-container">';
        echo '<h2>Domínios de Tradução</h2>';
        
        if (empty($domains)) {
            echo '<p>Nenhum domínio de tradução encontrado.</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Domínio</th><th>Idiomas</th><th>Ações</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($domains as $domain) {
                echo "<tr>";
                echo "<td>{$domain}</td>";
                echo "<td>" . implode(', ', array_keys($supportedLanguages)) . "</td>";
                echo "<td>
                    <a href='?page=condman-translations&tab=domains&action=edit&domain={$domain}' class='button'>Editar</a>
                    <a href='?page=condman-translations&tab=domains&action=validate&domain={$domain}' class='button'>Validar</a>
                </td>";
                echo "</tr>";
            }
            
            echo '</tbody></table>';
        }
        
        echo '</div>';
    }

    private function renderImportTab(array $domains, array $supportedLanguages): void {
        echo '<div class="import-container">';
        echo '<h2>Importar Traduções</h2>';
        
        echo '<form method="post" enctype="multipart/form-data">';
        echo '<table class="form-table">';
        
        echo '<tr>
            <th>Arquivo de Traduções</th>
            <td><input type="file" name="translation_file" accept=".yaml,.yml,.json" required></td>
        </tr>';
        
        echo '<tr>
            <th>Domínio</th>
            <td>
                <select name="translation_domain" required>';
                foreach ($domains as $domain) {
                    echo "<option value='{$domain}'>{$domain}</option>";
                }
                echo '</select>
            </td>
        </tr>';
        
        echo '<tr>
            <th>Idioma</th>
            <td>
                <select name="translation_locale" required>';
                foreach ($supportedLanguages as $code => $name) {
                    echo "<option value='{$code}'>{$name}</option>";
                }
                echo '</select>
            </td>
        </tr>';
        
        echo '</table>';
        echo '<input type="submit" name="import_translations" value="Importar" class="button button-primary">';
        echo '</form>';
        
        echo '</div>';
    }

    private function renderExportTab(array $domains, array $supportedLanguages): void {
        echo '<div class="export-container">';
        echo '<h2>Exportar Traduções</h2>';
        
        echo '<form method="post">';
        echo '<table class="form-table">';
        
        echo '<tr>
            <th>Domínio</th>
            <td>
                <select name="export_domain" required>';
                foreach ($domains as $domain) {
                    echo "<option value='{$domain}'>{$domain}</option>";
                }
                echo '</select>
            </td>
        </tr>';
        
        echo '<tr>
            <th>Idioma</th>
            <td>
                <select name="export_locale" required>';
                foreach ($supportedLanguages as $code => $name) {
                    echo "<option value='{$code}'>{$name}</option>";
                }
                echo '</select>
            </td>
        </tr>';
        
        echo '<tr>
            <th>Formato</th>
            <td>
                <select name="export_format">
                    <option value="yaml">YAML</option>
                    <option value="json">JSON</option>
                </select>
            </td>
        </tr>';
        
        echo '</table>';
        echo '<input type="submit" name="export_translations" value="Exportar" class="button button-primary">';
        echo '</form>';
        
        echo '</div>';
    }

    private function renderReportsTab(array $domains, array $supportedLanguages): void {
        echo '<div class="reports-container">';
        echo '<h2>Relatórios de Traduções</h2>';
        
        foreach ($domains as $domain) {
            echo "<h3>Domínio: {$domain}</h3>";
            
            $coverageReport = $this->translationManager->generateTranslationCoverageReport($domain);
            $validationReport = $this->translationManager->validateTranslations($domain);
            
            echo '<div class="report-section">';
            echo '<h4>Cobertura de Traduções</h4>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Idioma</th><th>Total de Traduções</th><th>Cobertura</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($coverageReport as $locale => $report) {
                echo "<tr>";
                echo "<td>{$supportedLanguages[$locale]}</td>";
                echo "<td>{$report['total_translations']}</td>";
                echo "<td>{$report['coverage_percentage']}%</td>";
                echo "</tr>";
            }
            
            echo '</tbody></table>';
            echo '</div>';
            
            echo '<div class="report-section">';
            echo '<h4>Validação de Traduções</h4>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Origem</th><th>Destino</th><th>Traduções Faltantes</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($validationReport as $validation) {
                echo "<tr>";
                echo "<td>{$supportedLanguages[$validation['source_locale']]}</td>";
                echo "<td>{$supportedLanguages[$validation['target_locale']]}</td>";
                echo "<td>{$validation['missing_count']}</td>";
                echo "</tr>";
            }
            
            echo '</tbody></table>';
            echo '</div>';
        }
        
        echo '</div>';
    }

    public function handleTranslationActions(): void {
        if (isset($_POST['import_translations'])) {
            $this->handleImportTranslations();
        }

        if (isset($_POST['export_translations'])) {
            $this->handleExportTranslations();
        }
    }

    private function handleImportTranslations(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Você não tem permissão para importar traduções.');
        }

        $domain = sanitize_text_field($_POST['translation_domain']);
        $locale = sanitize_text_field($_POST['translation_locale']);

        if (!empty($_FILES['translation_file']['tmp_name'])) {
            $importResult = $this->translationManager->importTranslations(
                $_FILES['translation_file']['tmp_name'], 
                $domain, 
                $locale
            );

            if ($importResult) {
                add_settings_error(
                    'condman_translations', 
                    'import_success', 
                    'Traduções importadas com sucesso!', 
                    'success'
                );
            } else {
                add_settings_error(
                    'condman_translations', 
                    'import_error', 
                    'Erro ao importar traduções.', 
                    'error'
                );
            }
        }
    }

    private function handleExportTranslations(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Você não tem permissão para exportar traduções.');
        }

        $domain = sanitize_text_field($_POST['export_domain']);
        $locale = sanitize_text_field($_POST['export_locale']);
        $format = sanitize_text_field($_POST['export_format']);

        $exportPath = $this->translationManager->exportTranslations(
            $domain, 
            $locale, 
            $format
        );

        if (!empty($exportPath)) {
            add_settings_error(
                'condman_translations', 
                'export_success', 
                "Traduções exportadas com sucesso para: {$exportPath}", 
                'success'
            );
        } else {
            add_settings_error(
                'condman_translations', 
                'export_error', 
                'Erro ao exportar traduções.', 
                'error'
            );
        }
    }
}
