<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Entities\InternalCommunication;
use CondMan\Domain\Repositories\InternalCommunicationRepositoryInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use CondMan\Domain\Interfaces\NotificationInterface;
use DateTime;
use Exception;

class InternalCommunicationManagementService {
    private InternalCommunicationRepositoryInterface $repository;
    private LoggerInterface $logger;
    private NotificationInterface $notificationService;

    public function __construct(
        InternalCommunicationRepositoryInterface $repository,
        LoggerInterface $logger,
        NotificationInterface $notificationService
    ) {
        $this->repository = $repository;
        $this->logger = $logger;
        $this->notificationService = $notificationService;
    }

    /**
     * Cria um novo comunicado
     */
    public function createCommunication(
        int $authorId, 
        string $title, 
        string $content, 
        string $type = 'general', 
        array $recipients = [], 
        ?DateTime $scheduledFor = null,
        array $metadata = []
    ): InternalCommunication {
        try {
            $communication = new InternalCommunication(
                null, 
                $authorId, 
                $title, 
                $content, 
                $type, 
                $recipients, 
                $scheduledFor, 
                null, 
                $scheduledFor ? 'scheduled' : 'draft',
                [],
                $metadata
            );

            $savedCommunication = $this->repository->save($communication);

            $this->logger->info('Comunicado criado com sucesso', [
                'communication_id' => $savedCommunication->getId(),
                'title' => $title
            ]);

            return $savedCommunication;
        } catch (Exception $e) {
            $this->logger->error('Erro ao criar comunicado', [
                'error' => $e->getMessage(),
                'title' => $title
            ]);
            throw $e;
        }
    }

    /**
     * Agenda um comunicado para envio futuro
     */
    public function scheduleCommunication(
        int $communicationId, 
        DateTime $scheduledFor
    ): bool {
        try {
            $communication = $this->repository->findById($communicationId);

            if (!$communication) {
                throw new Exception("Comunicado não encontrado");
            }

            $communication->schedule($scheduledFor);
            $this->repository->save($communication);

            $this->logger->info('Comunicado agendado', [
                'communication_id' => $communicationId,
                'scheduled_for' => $scheduledFor->format('Y-m-d H:i:s')
            ]);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Erro ao agendar comunicado', [
                'error' => $e->getMessage(),
                'communication_id' => $communicationId
            ]);
            throw $e;
        }
    }

    /**
     * Envia um comunicado
     */
    public function sendCommunication(int $communicationId): bool {
        try {
            $communication = $this->repository->findById($communicationId);

            if (!$communication) {
                throw new Exception("Comunicado não encontrado");
            }

            // Enviar notificação para cada destinatário
            foreach ($communication->getRecipients() as $recipientId) {
                $this->notificationService->send(
                    (string) $recipientId, 
                    $this->formatCommunicationMessage($communication)
                );
            }

            $communication->send();
            $this->repository->save($communication);

            $this->logger->info('Comunicado enviado', [
                'communication_id' => $communicationId,
                'recipients_count' => count($communication->getRecipients())
            ]);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Erro ao enviar comunicado', [
                'error' => $e->getMessage(),
                'communication_id' => $communicationId
            ]);
            throw $e;
        }
    }

    /**
     * Adiciona destinatários a um comunicado
     */
    public function addRecipients(int $communicationId, array $recipientIds): InternalCommunication {
        try {
            $communication = $this->repository->findById($communicationId);

            if (!$communication) {
                throw new Exception("Comunicado não encontrado");
            }

            foreach ($recipientIds as $recipientId) {
                $communication->addRecipient($recipientId);
            }

            $savedCommunication = $this->repository->save($communication);

            $this->logger->info('Destinatários adicionados', [
                'communication_id' => $communicationId,
                'recipients_added' => count($recipientIds)
            ]);

            return $savedCommunication;
        } catch (Exception $e) {
            $this->logger->error('Erro ao adicionar destinatários', [
                'error' => $e->getMessage(),
                'communication_id' => $communicationId
            ]);
            throw $e;
        }
    }

    /**
     * Registra confirmação de leitura
     */
    public function registerReadConfirmation(
        int $communicationId, 
        int $recipientId
    ): bool {
        try {
            $result = $this->repository->registerReadConfirmation(
                $communicationId, 
                $recipientId
            );

            $this->logger->info('Confirmação de leitura registrada', [
                'communication_id' => $communicationId,
                'recipient_id' => $recipientId
            ]);

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Erro ao registrar confirmação de leitura', [
                'error' => $e->getMessage(),
                'communication_id' => $communicationId,
                'recipient_id' => $recipientId
            ]);
            throw $e;
        }
    }

    /**
     * Recupera estatísticas de leitura
     */
    public function getReadStatistics(int $communicationId): array {
        try {
            $statistics = $this->repository->getReadStatistics($communicationId);

            $this->logger->info('Estatísticas de leitura recuperadas', [
                'communication_id' => $communicationId,
                'statistics' => $statistics
            ]);

            return $statistics;
        } catch (Exception $e) {
            $this->logger->error('Erro ao recuperar estatísticas de leitura', [
                'error' => $e->getMessage(),
                'communication_id' => $communicationId
            ]);
            throw $e;
        }
    }

    /**
     * Formata mensagem de comunicado para envio
     */
    private function formatCommunicationMessage(InternalCommunication $communication): string {
        return sprintf(
            "*Comunicado: %s*\n\n%s\n\nEnviado em: %s",
            $communication->getTitle(),
            $communication->getContent(),
            $communication->getSentAt()->format('d/m/Y H:i')
        );
    }
}
