<?php
namespace CondMan\Infrastructure\Repositories;

use CondMan\Domain\Repositories\OccurrenceRepositoryInterface;
use CondMan\Domain\Entities\Occurrence;
use CondMan\Domain\Interfaces\LoggerInterface;
use DateTime;
use wpdb;
use Exception;

class WordPressOccurrenceRepository implements OccurrenceRepositoryInterface {
    private wpdb $wpdb;
    private LoggerInterface $logger;
    private string $tableName;

    public function __construct(
        wpdb $wpdb, 
        LoggerInterface $logger,
        ?string $tableName = null
    ) {
        $this->wpdb = $wpdb;
        $this->logger = $logger;
        $this->tableName = $tableName ?? $this->wpdb->prefix . 'occurrences';
    }

    public function save(Occurrence $occurrence): Occurrence {
        try {
            $data = [
                'condominium_id' => $occurrence->getCondominiumId(),
                'reporter_id' => $occurrence->getReporterId(),
                'title' => $occurrence->getTitle(),
                'description' => $occurrence->getDescription(),
                'category' => $occurrence->getCategory(),
                'status' => $occurrence->getStatus(),
                'assigned_to_id' => $occurrence->getAssignedToId(),
                'metadata' => json_encode($occurrence->getMetadata())
            ];

            if ($occurrence->getId() === null) {
                // Inserir nova ocorrência
                $result = $this->wpdb->insert($this->tableName, $data);
                
                if ($result === false) {
                    throw new Exception("Erro ao inserir ocorrência: " . $this->wpdb->last_error);
                }

                $occurrence = new Occurrence(
                    $this->wpdb->insert_id,
                    $occurrence->getCondominiumId(),
                    $occurrence->getReporterId(),
                    $occurrence->getTitle(),
                    $occurrence->getDescription(),
                    $occurrence->getCategory(),
                    $occurrence->getStatus(),
                    $occurrence->getAssignedToId(),
                    $occurrence->getCreatedAt(),
                    null,
                    null,
                    $occurrence->getMetadata()
                );
            } else {
                // Atualizar ocorrência existente
                $result = $this->wpdb->update(
                    $this->tableName, 
                    $data, 
                    ['id' => $occurrence->getId()]
                );

                if ($result === false) {
                    throw new Exception("Erro ao atualizar ocorrência: " . $this->wpdb->last_error);
                }
            }

            $this->logger->info('Ocorrência salva com sucesso', [
                'occurrence_id' => $occurrence->getId(),
                'condominium_id' => $occurrence->getCondominiumId()
            ]);

            return $occurrence;
        } catch (Exception $e) {
            $this->logger->error('Erro ao salvar ocorrência', [
                'error' => $e->getMessage(),
                'occurrence' => $occurrence->jsonSerialize()
            ]);
            throw $e;
        }
    }

    public function findById(int $id): ?Occurrence {
        try {
            $result = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->tableName} WHERE id = %d", 
                    $id
                ), 
                ARRAY_A
            );

            if (!$result) {
                return null;
            }

            return $this->createOccurrenceFromDbResult($result);
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar ocorrência por ID', [
                'error' => $e->getMessage(),
                'occurrence_id' => $id
            ]);
            throw $e;
        }
    }

    public function findByFilters(array $filters): array {
        try {
            $conditions = [];
            $values = [];

            if (isset($filters['condominium_id'])) {
                $conditions[] = 'condominium_id = %d';
                $values[] = $filters['condominium_id'];
            }

            if (isset($filters['reporter_id'])) {
                $conditions[] = 'reporter_id = %d';
                $values[] = $filters['reporter_id'];
            }

            if (isset($filters['status'])) {
                $conditions[] = 'status = %s';
                $values[] = $filters['status'];
            }

            if (isset($filters['category'])) {
                $conditions[] = 'category = %s';
                $values[] = $filters['category'];
            }

            if (isset($filters['start_date'])) {
                $conditions[] = 'created_at >= %s';
                $values[] = $filters['start_date']->format('Y-m-d H:i:s');
            }

            if (isset($filters['end_date'])) {
                $conditions[] = 'created_at <= %s';
                $values[] = $filters['end_date']->format('Y-m-d H:i:s');
            }

            $query = "SELECT * FROM {$this->tableName}";
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }

            $query .= " ORDER BY created_at DESC";

            if (isset($filters['limit'])) {
                $query .= $this->wpdb->prepare(" LIMIT %d", $filters['limit']);
            }

            $results = $this->wpdb->get_results(
                $this->wpdb->prepare($query, $values), 
                ARRAY_A
            );

            return array_map(
                fn($result) => $this->createOccurrenceFromDbResult($result), 
                $results
            );
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar ocorrências', [
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

            if (isset($filters['condominium_id'])) {
                $conditions[] = 'condominium_id = %d';
                $values[] = $filters['condominium_id'];
            }

            if (isset($filters['status'])) {
                $conditions[] = 'status = %s';
                $values[] = $filters['status'];
            }

            $query = "SELECT COUNT(*) FROM {$this->tableName}";
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }

            return (int) $this->wpdb->get_var(
                $this->wpdb->prepare($query, $values)
            );
        } catch (Exception $e) {
            $this->logger->error('Erro ao contar ocorrências', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    public function updateStatus(int $occurrenceId, string $status): bool {
        try {
            $result = $this->wpdb->update(
                $this->tableName,
                ['status' => $status],
                ['id' => $occurrenceId]
            );

            $this->logger->info('Status da ocorrência atualizado', [
                'occurrence_id' => $occurrenceId,
                'new_status' => $status
            ]);

            return $result !== false;
        } catch (Exception $e) {
            $this->logger->error('Erro ao atualizar status da ocorrência', [
                'error' => $e->getMessage(),
                'occurrence_id' => $occurrenceId,
                'status' => $status
            ]);
            throw $e;
        }
    }

    public function assignOccurrence(int $occurrenceId, int $assignedToId): bool {
        try {
            $result = $this->wpdb->update(
                $this->tableName,
                ['assigned_to_id' => $assignedToId],
                ['id' => $occurrenceId]
            );

            $this->logger->info('Ocorrência atribuída', [
                'occurrence_id' => $occurrenceId,
                'assigned_to_id' => $assignedToId
            ]);

            return $result !== false;
        } catch (Exception $e) {
            $this->logger->error('Erro ao atribuir ocorrência', [
                'error' => $e->getMessage(),
                'occurrence_id' => $occurrenceId,
                'assigned_to_id' => $assignedToId
            ]);
            throw $e;
        }
    }

    public function resolveOccurrence(int $occurrenceId): bool {
        try {
            $result = $this->wpdb->update(
                $this->tableName,
                [
                    'status' => 'resolved',
                    'resolved_at' => current_time('mysql')
                ],
                ['id' => $occurrenceId]
            );

            $this->logger->info('Ocorrência resolvida', [
                'occurrence_id' => $occurrenceId
            ]);

            return $result !== false;
        } catch (Exception $e) {
            $this->logger->error('Erro ao resolver ocorrência', [
                'error' => $e->getMessage(),
                'occurrence_id' => $occurrenceId
            ]);
            throw $e;
        }
    }

    private function createOccurrenceFromDbResult(array $result): Occurrence {
        return new Occurrence(
            (int) $result['id'],
            (int) $result['condominium_id'],
            (int) $result['reporter_id'],
            $result['title'],
            $result['description'],
            $result['category'],
            $result['status'],
            $result['assigned_to_id'] ? (int) $result['assigned_to_id'] : null,
            new DateTime($result['created_at']),
            $result['updated_at'] ? new DateTime($result['updated_at']) : null,
            $result['resolved_at'] ? new DateTime($result['resolved_at']) : null,
            json_decode($result['metadata'] ?? '{}', true)
        );
    }
}
