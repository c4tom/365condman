<?php
namespace CondMan\Infrastructure\Repositories;

use CondMan\Domain\Repositories\InternalCommunicationRepositoryInterface;
use CondMan\Domain\Entities\InternalCommunication;
use CondMan\Domain\Interfaces\LoggerInterface;
use DateTime;
use wpdb;
use Exception;

class WordPressInternalCommunicationRepository implements InternalCommunicationRepositoryInterface {
    private wpdb $wpdb;
    private LoggerInterface $logger;
    private string $communicationTable;
    private string $recipientsTable;
    private string $readConfirmationTable;

    public function __construct(
        wpdb $wpdb, 
        LoggerInterface $logger,
        ?string $communicationTable = null,
        ?string $recipientsTable = null,
        ?string $readConfirmationTable = null
    ) {
        $this->wpdb = $wpdb;
        $this->logger = $logger;
        $this->communicationTable = $communicationTable ?? $this->wpdb->prefix . 'internal_communications';
        $this->recipientsTable = $recipientsTable ?? $this->wpdb->prefix . 'communication_recipients';
        $this->readConfirmationTable = $readConfirmationTable ?? $this->wpdb->prefix . 'communication_read_confirmations';
    }

    public function save(InternalCommunication $communication): InternalCommunication {
        try {
            $data = [
                'author_id' => $communication->getAuthorId(),
                'title' => $communication->getTitle(),
                'content' => $communication->getContent(),
                'type' => $communication->getType(),
                'status' => $communication->getStatus(),
                'scheduled_for' => $communication->getScheduledFor() ? $communication->getScheduledFor()->format('Y-m-d H:i:s') : null,
                'sent_at' => $communication->getSentAt() ? $communication->getSentAt()->format('Y-m-d H:i:s') : null,
                'metadata' => json_encode($communication->getMetadata())
            ];

            if ($communication->getId() === null) {
                $this->wpdb->insert($this->communicationTable, $data);
                $communicationId = $this->wpdb->insert_id;

                // Adicionar destinatários
                $this->updateRecipients($communicationId, $communication->getRecipients());
            } else {
                $this->wpdb->update(
                    $this->communicationTable, 
                    $data, 
                    ['id' => $communication->getId()]
                );
                $communicationId = $communication->getId();
            }

            $this->logger->info('Comunicado salvo com sucesso', [
                'communication_id' => $communicationId
            ]);

            return $this->findById($communicationId);
        } catch (Exception $e) {
            $this->logger->error('Erro ao salvar comunicado', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function findById(int $id): ?InternalCommunication {
        try {
            $result = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->communicationTable} WHERE id = %d", 
                    $id
                ), 
                ARRAY_A
            );

            if (!$result) {
                return null;
            }

            return $this->createCommunicationFromDbResult($result);
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar comunicado por ID', [
                'error' => $e->getMessage(),
                'communication_id' => $id
            ]);
            throw $e;
        }
    }

    public function findByFilters(array $filters): array {
        try {
            $conditions = [];
            $values = [];

            if (isset($filters['author_id'])) {
                $conditions[] = 'author_id = %d';
                $values[] = $filters['author_id'];
            }

            if (isset($filters['type'])) {
                $conditions[] = 'type = %s';
                $values[] = $filters['type'];
            }

            if (isset($filters['status'])) {
                $conditions[] = 'status = %s';
                $values[] = $filters['status'];
            }

            if (isset($filters['start_date'])) {
                $conditions[] = 'sent_at >= %s';
                $values[] = $filters['start_date']->format('Y-m-d H:i:s');
            }

            if (isset($filters['end_date'])) {
                $conditions[] = 'sent_at <= %s';
                $values[] = $filters['end_date']->format('Y-m-d H:i:s');
            }

            $query = "SELECT * FROM {$this->communicationTable}";
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }

            $query .= " ORDER BY sent_at DESC";

            if (isset($filters['limit'])) {
                $query .= $this->wpdb->prepare(" LIMIT %d", $filters['limit']);
            }

            $results = $this->wpdb->get_results(
                $this->wpdb->prepare($query, $values), 
                ARRAY_A
            );

            return array_map(
                fn($result) => $this->createCommunicationFromDbResult($result), 
                $results
            );
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar comunicados', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    public function countByFilters(array $filters): int {
        try {
            $conditions = [];
            $values = [];

            if (isset($filters['author_id'])) {
                $conditions[] = 'author_id = %d';
                $values[] = $filters['author_id'];
            }

            if (isset($filters['status'])) {
                $conditions[] = 'status = %s';
                $values[] = $filters['status'];
            }

            $query = "SELECT COUNT(*) FROM {$this->communicationTable}";
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }

            return (int) $this->wpdb->get_var(
                $this->wpdb->prepare($query, $values)
            );
        } catch (Exception $e) {
            $this->logger->error('Erro ao contar comunicados', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    public function schedule(InternalCommunication $communication): bool {
        try {
            $result = $this->wpdb->update(
                $this->communicationTable,
                [
                    'status' => 'scheduled',
                    'scheduled_for' => $communication->getScheduledFor()->format('Y-m-d H:i:s')
                ],
                ['id' => $communication->getId()]
            );

            $this->logger->info('Comunicado agendado', [
                'communication_id' => $communication->getId(),
                'scheduled_for' => $communication->getScheduledFor()->format('Y-m-d H:i:s')
            ]);

            return $result !== false;
        } catch (Exception $e) {
            $this->logger->error('Erro ao agendar comunicado', [
                'error' => $e->getMessage(),
                'communication_id' => $communication->getId()
            ]);
            throw $e;
        }
    }

    public function markAsSent(int $communicationId): bool {
        try {
            $result = $this->wpdb->update(
                $this->communicationTable,
                [
                    'status' => 'sent',
                    'sent_at' => current_time('mysql')
                ],
                ['id' => $communicationId]
            );

            $this->logger->info('Comunicado marcado como enviado', [
                'communication_id' => $communicationId
            ]);

            return $result !== false;
        } catch (Exception $e) {
            $this->logger->error('Erro ao marcar comunicado como enviado', [
                'error' => $e->getMessage(),
                'communication_id' => $communicationId
            ]);
            throw $e;
        }
    }

    public function registerReadConfirmation(int $communicationId, int $recipientId): bool {
        try {
            $result = $this->wpdb->insert(
                $this->readConfirmationTable,
                [
                    'communication_id' => $communicationId,
                    'recipient_id' => $recipientId,
                    'read_at' => current_time('mysql')
                ]
            );

            $this->logger->info('Confirmação de leitura registrada', [
                'communication_id' => $communicationId,
                'recipient_id' => $recipientId
            ]);

            return $result !== false;
        } catch (Exception $e) {
            $this->logger->error('Erro ao registrar confirmação de leitura', [
                'error' => $e->getMessage(),
                'communication_id' => $communicationId,
                'recipient_id' => $recipientId
            ]);
            throw $e;
        }
    }

    public function getReadStatistics(int $communicationId): array {
        try {
            $totalRecipients = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->recipientsTable} WHERE communication_id = %d", 
                    $communicationId
                )
            );

            $readCount = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->readConfirmationTable} WHERE communication_id = %d", 
                    $communicationId
                )
            );

            $readRate = $totalRecipients > 0 ? ($readCount / $totalRecipients) * 100 : 0;

            return [
                'total_recipients' => (int) $totalRecipients,
                'read_count' => (int) $readCount,
                'read_rate' => round($readRate, 2)
            ];
        } catch (Exception $e) {
            $this->logger->error('Erro ao recuperar estatísticas de leitura', [
                'error' => $e->getMessage(),
                'communication_id' => $communicationId
            ]);
            throw $e;
        }
    }

    private function updateRecipients(int $communicationId, array $recipients): void {
        // Remove destinatários existentes
        $this->wpdb->delete(
            $this->recipientsTable, 
            ['communication_id' => $communicationId]
        );

        // Adiciona novos destinatários
        $recipientData = array_map(
            fn($recipientId) => [
                'communication_id' => $communicationId, 
                'recipient_id' => $recipientId
            ], 
            $recipients
        );

        if (!empty($recipientData)) {
            $this->wpdb->insert_multiple(
                $this->recipientsTable, 
                $recipientData
            );
        }
    }

    private function createCommunicationFromDbResult(array $result): InternalCommunication {
        // Recuperar destinatários
        $recipients = $this->wpdb->get_col(
            $this->wpdb->prepare(
                "SELECT recipient_id FROM {$this->recipientsTable} WHERE communication_id = %d", 
                $result['id']
            )
        );

        // Recuperar confirmações de leitura
        $readConfirmations = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT recipient_id, read_at FROM {$this->readConfirmationTable} WHERE communication_id = %d", 
                $result['id']
            ), 
            ARRAY_A
        );

        $readConfirmationMap = array_combine(
            array_column($readConfirmations, 'recipient_id'),
            array_map(fn($item) => new DateTime($item['read_at']), $readConfirmations)
        );

        return new InternalCommunication(
            (int) $result['id'],
            (int) $result['author_id'],
            $result['title'],
            $result['content'],
            $result['type'],
            $recipients,
            $result['scheduled_for'] ? new DateTime($result['scheduled_for']) : null,
            $result['sent_at'] ? new DateTime($result['sent_at']) : null,
            $result['status'],
            $readConfirmationMap,
            json_decode($result['metadata'] ?? '{}', true)
        );
    }
}
