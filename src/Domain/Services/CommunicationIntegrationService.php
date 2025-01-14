<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\IntegrationInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use CondMan\Infrastructure\Repositories\CommunicationRepository;
use CondMan\Infrastructure\Repositories\UnitRepository;

class CommunicationIntegrationService implements IntegrationInterface {
    private CommunicationRepository $communicationRepository;
    private UnitRepository $unitRepository;
    private LoggerInterface $logger;

    public function __construct(
        CommunicationRepository $communicationRepository,
        UnitRepository $unitRepository,
        LoggerInterface $logger
    ) {
        $this->communicationRepository = $communicationRepository;
        $this->unitRepository = $unitRepository;
        $this->logger = $logger;
    }

    public function validate(array $data): bool {
        // Validações específicas para integração de comunicação
        $requiredFields = [
            'condominium_id', 
            'communication_type', 
            'recipients', 
            'message_template'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->logger->warning("Missing required field for communication integration", [
                    'field' => $field,
                    'data' => $data
                ]);
                return false;
            }
        }

        // Valida tipo de comunicação
        $validTypes = ['EMAIL', 'SMS', 'PUSH_NOTIFICATION', 'LETTER'];
        if (!in_array($data['communication_type'], $validTypes)) {
            $this->logger->warning("Invalid communication type", [
                'type' => $data['communication_type']
            ]);
            return false;
        }

        // Valida destinatários
        if (!$this->validateRecipients($data['recipients'], $data['condominium_id'])) {
            return false;
        }

        return true;
    }

    private function validateRecipients(array $recipients, int $condominiumId): bool {
        foreach ($recipients as $unitId) {
            $unit = $this->unitRepository->findById($unitId);
            if (!$unit || $unit['condominium_id'] !== $condominiumId) {
                $this->logger->warning("Invalid recipient for communication", [
                    'unit_id' => $unitId,
                    'condominium_id' => $condominiumId
                ]);
                return false;
            }
        }
        return true;
    }

    public function integrate(array $data): bool {
        try {
            // Valida dados antes da integração
            if (!$this->validate($data)) {
                return false;
            }

            // Inicia transação
            $this->communicationRepository->beginTransaction();

            // Cria comunicação
            $communicationId = $this->communicationRepository->insert([
                'condominium_id' => $data['condominium_id'],
                'communication_type' => $data['communication_type'],
                'status' => 'PENDING',
                'template_id' => $data['message_template']
            ]);

            // Adiciona destinatários
            foreach ($data['recipients'] as $unitId) {
                $this->communicationRepository->addCommunicationRecipient($communicationId, $unitId);
            }

            // Adiciona log de comunicação
            $this->communicationRepository->addCommunicationLog($communicationId, [
                'status' => 'CREATED',
                'description' => 'Communication created and queued for sending'
            ]);

            // Confirma transação
            $this->communicationRepository->commit();

            $this->logger->info('Communication integration successful', [
                'communication_id' => $communicationId,
                'condominium_id' => $data['condominium_id'],
                'type' => $data['communication_type']
            ]);

            return true;
        } catch (\Exception $e) {
            // Reverte transação em caso de erro
            $this->communicationRepository->rollback();

            $this->logger->error('Communication integration failed', [
                'exception' => $e->getMessage(),
                'data' => $data
            ]);

            return false;
        }
    }

    /**
     * Busca comunicações pendentes para envio
     * @param int $condominiumId ID do condomínio
     * @return array Comunicações pendentes
     */
    public function findPendingCommunications(int $condominiumId): array {
        return $this->communicationRepository->findByCondominium($condominiumId, [
            'status' => 'PENDING'
        ]);
    }

    /**
     * Atualiza status da comunicação
     * @param int $communicationId ID da comunicação
     * @param string $status Novo status
     * @return bool Indica se a atualização foi bem-sucedida
     */
    public function updateCommunicationStatus(int $communicationId, string $status): bool {
        try {
            $result = $this->communicationRepository->update($communicationId, [
                'status' => $status
            ]);

            $this->communicationRepository->addCommunicationLog($communicationId, [
                'status' => $status,
                'description' => "Communication status updated to {$status}"
            ]);

            $this->logger->info('Communication status updated', [
                'communication_id' => $communicationId,
                'status' => $status
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error updating communication status', [
                'exception' => $e->getMessage(),
                'communication_id' => $communicationId,
                'status' => $status
            ]);

            return false;
        }
    }
}
