<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\TranslationManagementInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use CondMan\Domain\Interfaces\InternationalizationInterface;
use Symfony\Component\Yaml\Yaml;
use Exception;

class TranslationManagementService implements TranslationManagementInterface {
    private LoggerInterface $logger;
    private InternationalizationInterface $internationalization;
    private string $translationBaseDir;

    public function __construct(
        LoggerInterface $logger,
        InternationalizationInterface $internationalization,
        string $translationBaseDir = null
    ) {
        $this->logger = $logger;
        $this->internationalization = $internationalization;
        $this->translationBaseDir = $translationBaseDir ?? 
            dirname(__DIR__, 2) . '/resources/translations/';
    }

    public function listTranslationDomains(): array {
        try {
            $domains = [];
            $supportedLocales = $this->internationalization->getSupportedLanguages();

            foreach (array_keys($supportedLocales) as $locale) {
                $localeDir = $this->translationBaseDir . $locale . '/';
                
                if (is_dir($localeDir)) {
                    $files = glob($localeDir . '*.yaml');
                    foreach ($files as $file) {
                        $domain = basename($file, '.yaml');
                        $domains[] = str_replace(['condman_', '_domains', '_entities', '_messages'], '', $domain);
                    }
                }
            }

            return array_unique($domains);
        } catch (Exception $e) {
            $this->logger->error('Erro ao listar domínios de tradução', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getTranslations(string $domain, string $locale): array {
        try {
            $translations = [];
            $translationFiles = [
                "{$domain}_domains.yaml",
                "{$domain}_entities.yaml",
                "{$domain}_messages.yaml"
            ];

            foreach ($translationFiles as $filename) {
                $filePath = $this->translationBaseDir . $locale . '/' . $filename;
                
                if (file_exists($filePath)) {
                    $fileTranslations = Yaml::parseFile($filePath);
                    $translations = array_merge($translations, $fileTranslations);
                }
            }

            return $translations;
        } catch (Exception $e) {
            $this->logger->error('Erro ao obter traduções', [
                'domain' => $domain,
                'locale' => $locale,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function updateTranslation(
        string $domain, 
        string $locale, 
        string $key, 
        string $translation
    ): bool {
        try {
            $translationFiles = [
                "{$domain}_domains.yaml",
                "{$domain}_entities.yaml",
                "{$domain}_messages.yaml"
            ];

            $updated = false;
            foreach ($translationFiles as $filename) {
                $filePath = $this->translationBaseDir . $locale . '/' . $filename;
                
                if (file_exists($filePath)) {
                    $translations = Yaml::parseFile($filePath);
                    
                    if (array_key_exists($key, $translations)) {
                        $translations[$key] = $translation;
                        file_put_contents(
                            $filePath, 
                            Yaml::dump($translations, 4, 2)
                        );
                        $updated = true;
                    }
                }
            }

            if ($updated) {
                $this->logger->info('Tradução atualizada', [
                    'domain' => $domain,
                    'locale' => $locale,
                    'key' => $key
                ]);
            }

            return $updated;
        } catch (Exception $e) {
            $this->logger->error('Erro ao atualizar tradução', [
                'domain' => $domain,
                'locale' => $locale,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function removeTranslation(
        string $domain, 
        string $locale, 
        string $key
    ): bool {
        try {
            $translationFiles = [
                "{$domain}_domains.yaml",
                "{$domain}_entities.yaml",
                "{$domain}_messages.yaml"
            ];

            $removed = false;
            foreach ($translationFiles as $filename) {
                $filePath = $this->translationBaseDir . $locale . '/' . $filename;
                
                if (file_exists($filePath)) {
                    $translations = Yaml::parseFile($filePath);
                    
                    if (array_key_exists($key, $translations)) {
                        unset($translations[$key]);
                        file_put_contents(
                            $filePath, 
                            Yaml::dump($translations, 4, 2)
                        );
                        $removed = true;
                    }
                }
            }

            if ($removed) {
                $this->logger->info('Tradução removida', [
                    'domain' => $domain,
                    'locale' => $locale,
                    'key' => $key
                ]);
            }

            return $removed;
        } catch (Exception $e) {
            $this->logger->error('Erro ao remover tradução', [
                'domain' => $domain,
                'locale' => $locale,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function exportTranslations(
        string $domain, 
        string $locale, 
        string $format = 'yaml'
    ): string {
        try {
            $translations = $this->getTranslations($domain, $locale);
            
            $exportDir = $this->translationBaseDir . 'exports/';
            if (!is_dir($exportDir)) {
                mkdir($exportDir, 0755, true);
            }

            $filename = "{$domain}_{$locale}_export." . $format;
            $exportPath = $exportDir . $filename;

            switch ($format) {
                case 'json':
                    file_put_contents($exportPath, json_encode($translations, JSON_PRETTY_PRINT));
                    break;
                default:
                    file_put_contents($exportPath, Yaml::dump($translations, 4, 2));
            }

            $this->logger->info('Traduções exportadas', [
                'domain' => $domain,
                'locale' => $locale,
                'format' => $format,
                'path' => $exportPath
            ]);

            return $exportPath;
        } catch (Exception $e) {
            $this->logger->error('Erro ao exportar traduções', [
                'domain' => $domain,
                'locale' => $locale,
                'format' => $format,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    public function importTranslations(
        string $filePath, 
        string $domain, 
        string $locale
    ): bool {
        try {
            $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
            
            switch ($fileExtension) {
                case 'json':
                    $translations = json_decode(file_get_contents($filePath), true);
                    break;
                case 'yaml':
                case 'yml':
                    $translations = Yaml::parseFile($filePath);
                    break;
                default:
                    throw new Exception('Formato de arquivo não suportado');
            }

            $translationFiles = [
                "{$domain}_domains.yaml",
                "{$domain}_entities.yaml",
                "{$domain}_messages.yaml"
            ];

            foreach ($translationFiles as $filename) {
                $targetPath = $this->translationBaseDir . $locale . '/' . $filename;
                
                if (file_exists($targetPath)) {
                    $existingTranslations = Yaml::parseFile($targetPath);
                    $mergedTranslations = array_merge($existingTranslations, $translations);
                    
                    file_put_contents(
                        $targetPath, 
                        Yaml::dump($mergedTranslations, 4, 2)
                    );
                }
            }

            $this->logger->info('Traduções importadas', [
                'domain' => $domain,
                'locale' => $locale,
                'filePath' => $filePath
            ]);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Erro ao importar traduções', [
                'domain' => $domain,
                'locale' => $locale,
                'filePath' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function validateTranslations(string $domain): array {
        try {
            $supportedLocales = $this->internationalization->getSupportedLanguages();
            $validationReport = [];

            foreach (array_keys($supportedLocales) as $sourceLocale) {
                foreach (array_keys($supportedLocales) as $targetLocale) {
                    if ($sourceLocale !== $targetLocale) {
                        $missingTranslations = $this->findMissingTranslations(
                            $domain, 
                            $sourceLocale, 
                            $targetLocale
                        );

                        $validationReport[] = [
                            'source_locale' => $sourceLocale,
                            'target_locale' => $targetLocale,
                            'missing_translations' => $missingTranslations,
                            'missing_count' => count($missingTranslations)
                        ];
                    }
                }
            }

            return $validationReport;
        } catch (Exception $e) {
            $this->logger->error('Erro ao validar traduções', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function generateTranslationCoverageReport(string $domain): array {
        try {
            $supportedLocales = $this->internationalization->getSupportedLanguages();
            $coverageReport = [];

            foreach (array_keys($supportedLocales) as $locale) {
                $translations = $this->getTranslations($domain, $locale);
                
                $coverageReport[$locale] = [
                    'total_translations' => count($translations),
                    'coverage_percentage' => 100
                ];
            }

            return $coverageReport;
        } catch (Exception $e) {
            $this->logger->error('Erro ao gerar relatório de cobertura', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function findMissingTranslations(
        string $domain, 
        string $sourceLocale, 
        string $targetLocale
    ): array {
        try {
            $sourceTranslations = $this->getTranslations($domain, $sourceLocale);
            $targetTranslations = $this->getTranslations($domain, $targetLocale);

            $missingTranslations = [];
            foreach ($sourceTranslations as $key => $value) {
                if (!array_key_exists($key, $targetTranslations)) {
                    $missingTranslations[$key] = $value;
                }
            }

            return $missingTranslations;
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar traduções faltantes', [
                'domain' => $domain,
                'source_locale' => $sourceLocale,
                'target_locale' => $targetLocale,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
