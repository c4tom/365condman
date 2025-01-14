use CondMan\Domain\Interfaces\InternationalizationInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use NumberFormatter;
use IntlDateFormatter;
use Exception;

class InternationalizationService implements InternationalizationInterface {
    private Translator $translator;
    private LoggerInterface $logger;
    private string $defaultLocale;
    private array $supportedLocales;
    private string $translationBaseDir;

    public function __construct(
        LoggerInterface $logger,
        string $defaultLocale = 'pt_BR',
        string $translationBaseDir = null
    ) {
        $this->logger = $logger;
        $this->defaultLocale = $defaultLocale;
        $this->translationBaseDir = $translationBaseDir ?? 
            dirname(__DIR__, 2) . '/resources/translations/';

        $this->supportedLocales = [
            'pt_BR' => 'Português (Brasil)',
            'en_US' => 'English (United States)',
            'es_ES' => 'Español (España)',
            'fr_FR' => 'Français (France)',
            'de_DE' => 'Deutsch (Deutschland)'
        ];

        $this->translator = new Translator($this->defaultLocale);
        $this->translator->addLoader('array', new ArrayLoader());
        $this->translator->addLoader('yaml', new YamlFileLoader());

        $this->loadTranslations();
    }

    public function loadTranslations(string $locale = null, string $domain = 'condman'): bool {
        try {
            $locales = $locale ? [$locale] : array_keys($this->supportedLocales);

            foreach ($locales as $currentLocale) {
                $this->loadLocaleTranslations($this->translationBaseDir, $currentLocale, $domain);
            }

            $this->logger->info('Traduções carregadas', [
                'locales' => $locales,
                'domain' => $domain
            ]);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Erro ao carregar traduções', [
                'error' => $e->getMessage(),
                'locale' => $locale,
                'domain' => $domain
            ]);
            return false;
        }
    }

    private function loadLocaleTranslations(
        string $baseDir, 
        string $locale, 
        string $domain = 'condman'
    ): void {
        $translationFiles = [
            "{$domain}_domains.yaml",
            "{$domain}_entities.yaml",
            "{$domain}_messages.yaml"
        ];

        foreach ($translationFiles as $file) {
            $filePath = $baseDir . $locale . '/' . $file;
            if (file_exists($filePath)) {
                $this->translator->addResource('yaml', $filePath, $locale, $domain);
            }
        }
    }

    public function translate(string $text, string $domain = 'condman'): string {
        try {
            return $this->translator->trans($text, [], $domain);
        } catch (Exception $e) {
            $this->logger->warning('Falha na tradução', [
                'text' => $text,
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return $text;
        }
    }

    public function translatePlural(
        string $singular, 
        string $plural, 
        int $count, 
        string $domain = 'condman'
    ): string {
        try {
            return $this->translator->transChoice(
                $count === 1 ? $singular : $plural, 
                $count, 
                ['%count%' => $count], 
                $domain
            );
        } catch (Exception $e) {
            $this->logger->warning('Falha na tradução plural', [
                'singular' => $singular,
                'plural' => $plural,
                'count' => $count,
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return $count === 1 ? $singular : $plural;
        }
    }

    public function getSupportedLanguages(): array {
        return $this->supportedLocales;
    }

    public function detectBrowserLanguage(): string {
        $httpAcceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? $this->defaultLocale;
        $preferredLocales = explode(',', $httpAcceptLanguage);
        
        foreach ($preferredLocales as $locale) {
            $locale = strtolower(substr($locale, 0, 2));
            $matchingLocale = array_filter($this->supportedLocales, function($key) use ($locale) {
                return strtolower(substr($key, 0, 2)) === $locale;
            }, ARRAY_FILTER_USE_KEY);

            if (!empty($matchingLocale)) {
                return key($matchingLocale);
            }
        }

        return $this->defaultLocale;
    }

    public function formatDate(
        \DateTime $date, 
        string $locale = 'pt_BR', 
        string $format = 'full'
    ): string {
        try {
            $formatter = new IntlDateFormatter(
                $locale, 
                match($format) {
                    'short' => IntlDateFormatter::SHORT,
                    'medium' => IntlDateFormatter::MEDIUM,
                    'long' => IntlDateFormatter::LONG,
                    default => IntlDateFormatter::FULL
                },
                IntlDateFormatter::NONE
            );
            return $formatter->format($date);
        } catch (Exception $e) {
            $this->logger->warning('Erro na formatação de data', [
                'locale' => $locale,
                'format' => $format,
                'error' => $e->getMessage()
            ]);
            return $date->format('Y-m-d');
        }
    }

    public function formatCurrency(
        float $value, 
        string $locale = 'pt_BR', 
        string $currency = 'BRL'
    ): string {
        try {
            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            return $formatter->formatCurrency($value, $currency);
        } catch (Exception $e) {
            $this->logger->warning('Erro na formatação de moeda', [
                'value' => $value,
                'locale' => $locale,
                'currency' => $currency,
                'error' => $e->getMessage()
            ]);
            return number_format($value, 2, ',', '.');
        }
    }

    public function registerTranslationDomain(string $domain, string $path): bool {
        try {
            foreach (array_keys($this->supportedLocales) as $locale) {
                $this->translator->addResource('yaml', $path, $locale, $domain);
            }

            $this->logger->info('Domínio de tradução registrado', [
                'domain' => $domain,
                'path' => $path
            ]);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Erro ao registrar domínio de tradução', [
                'domain' => $domain,
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
