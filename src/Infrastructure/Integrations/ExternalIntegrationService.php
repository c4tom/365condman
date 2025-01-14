<?php
namespace CondMan\Infrastructure\Integrations;

use CondMan\Domain\Interfaces\IntegrationInterface;
use CondMan\Domain\Interfaces\ConfigurationInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class ExternalIntegrationService implements IntegrationInterface {
    private $config;
    private $logger;
    private $httpClient;

    public function __construct(
        ConfigurationInterface $config,
        LoggerInterface $logger = null,
        Client $httpClient = null
    ) {
        $this->config = $config;
        $this->logger = $logger ?? new NullLogger();
        $this->httpClient = $httpClient ?? new Client();
    }

    /**
     * Realiza integração com sistema externo
     * @param array $data Dados para integração
     * @return bool Resultado da integração
     */
    public function integrate(array $data): bool {
        if (!$this->validate($data)) {
            return false;
        }

        try {
            $endpoint = $this->config->get('external_integration_url');
            
            $response = $this->httpClient->post($endpoint, [
                'json' => $data,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config->get('external_integration_token'),
                    'Content-Type' => 'application/json'
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $success = $statusCode >= 200 && $statusCode < 300;

            $this->log($data, $success);
            return $success;
        } catch (RequestException $e) {
            $this->logger->error('Falha na integração externa', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);
            return false;
        }
    }

    /**
     * Valida dados antes da integração
     * @param array $data Dados a serem validados
     * @return bool Status da validação
     */
    public function validate(array $data): bool {
        $requiredFields = [
            'condominium_id',
            'unit_id',
            'type'
        ];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $this->logger->warning("Campo obrigatório ausente: {$field}");
                return false;
            }
        }

        return true;
    }

    /**
     * Registra log de integração
     * @param array $data Dados integrados
     * @param bool $success Status da integração
     */
    private function log(array $data, bool $success): void {
        $logMethod = $success ? 'info' : 'error';
        $this->logger->$logMethod('Integração externa', [
            'success' => $success,
            'condominium_id' => $data['condominium_id'] ?? null,
            'unit_id' => $data['unit_id'] ?? null,
            'type' => $data['type'] ?? null
        ]);
    }
}
