<?php
namespace CondMan\Infrastructure\Logging;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\WebProcessor;
use CondMan\Domain\Interfaces\ConfigurationInterface;

class LoggingService {
    private const LOG_CHANNEL = '365condman';
    private const LOG_DIR = WP_CONTENT_DIR . '/logs/365condman/';

    private $config;
    private $logger;

    public function __construct(ConfigurationInterface $config) {
        $this->config = $config;
        $this->logger = $this->createLogger();
    }

    /**
     * Cria logger personalizado
     * @return Logger
     */
    private function createLogger(): Logger {
        // Criar diretório de logs se não existir
        if (!file_exists(self::LOG_DIR)) {
            wp_mkdir_p(self::LOG_DIR);
        }

        $logger = new Logger(self::LOG_CHANNEL);

        // Configurar formatador de log
        $dateFormat = 'Y-m-d H:i:s';
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $formatter = new LineFormatter($output, $dateFormat);

        // Handler de arquivo rotativo
        $rotatingHandler = new RotatingFileHandler(
            self::LOG_DIR . 'plugin.log', 
            $this->config->get('log_max_files', 30),  // Máximo de 30 arquivos por padrão
            $this->getLogLevel()
        );
        $rotatingHandler->setFormatter($formatter);

        // Adicionar processadores
        $logger->pushProcessor(new MemoryUsageProcessor());
        $logger->pushProcessor(new WebProcessor());

        $logger->pushHandler($rotatingHandler);

        return $logger;
    }

    /**
     * Obtém nível de log baseado na configuração
     * @return int Nível de log do Monolog
     */
    private function getLogLevel(): int {
        $logLevelMap = [
            'debug' => Logger::DEBUG,
            'info' => Logger::INFO,
            'notice' => Logger::NOTICE,
            'warning' => Logger::WARNING,
            'error' => Logger::ERROR,
            'critical' => Logger::CRITICAL,
            'alert' => Logger::ALERT,
            'emergency' => Logger::EMERGENCY
        ];

        $configLevel = $this->config->get('log_level', 'info');
        return $logLevelMap[strtolower($configLevel)] ?? Logger::INFO;
    }

    /**
     * Registra log de depuração
     * @param string $message Mensagem de log
     * @param array $context Contexto adicional
     */
    public function debug(string $message, array $context = []): void {
        $this->logger->debug($message, $this->sanitizeContext($context));
    }

    /**
     * Registra log de informação
     * @param string $message Mensagem de log
     * @param array $context Contexto adicional
     */
    public function info(string $message, array $context = []): void {
        $this->logger->info($message, $this->sanitizeContext($context));
    }

    /**
     * Registra log de aviso
     * @param string $message Mensagem de log
     * @param array $context Contexto adicional
     */
    public function warning(string $message, array $context = []): void {
        $this->logger->warning($message, $this->sanitizeContext($context));
    }

    /**
     * Registra log de erro
     * @param string $message Mensagem de log
     * @param array $context Contexto adicional
     */
    public function error(string $message, array $context = []): void {
        $this->logger->error($message, $this->sanitizeContext($context));
    }

    /**
     * Registra log crítico
     * @param string $message Mensagem de log
     * @param array $context Contexto adicional
     */
    public function critical(string $message, array $context = []): void {
        $this->logger->critical($message, $this->sanitizeContext($context));
    }

    /**
     * Registra exceção
     * @param \Throwable $exception Exceção
     * @param array $context Contexto adicional
     */
    public function logException(\Throwable $exception, array $context = []): void {
        $context['exception'] = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];

        $this->error('Exceção capturada', $context);
    }

    /**
     * Sanitiza contexto de log para evitar dados sensíveis
     * @param array $context Contexto original
     * @return array Contexto sanitizado
     */
    private function sanitizeContext(array $context): array {
        $sensitiveKeys = [
            'password', 'token', 'api_key', 'secret', 
            'credentials', 'authorization', 'access_token'
        ];

        return array_map(function($value) {
            if (is_string($value)) {
                // Substituir valores sensíveis
                foreach ($sensitiveKeys as $sensitiveKey) {
                    if (stripos($value, $sensitiveKey) !== false) {
                        return '[REDACTED]';
                    }
                }
            }
            return $value;
        }, $context);
    }

    /**
     * Limpa logs antigos
     * @param int $daysToKeep Dias para manter logs
     */
    public function cleanupLogs(int $daysToKeep = 30): void {
        $logFiles = glob(self::LOG_DIR . '*.log');
        $cutoffTimestamp = strtotime("-{$daysToKeep} days");

        foreach ($logFiles as $logFile) {
            if (filemtime($logFile) < $cutoffTimestamp) {
                unlink($logFile);
            }
        }

        $this->info('Limpeza de logs concluída', [
            'days_kept' => $daysToKeep,
            'files_processed' => count($logFiles)
        ]);
    }
}
