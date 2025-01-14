<?php
namespace CondMan\Infrastructure\Repositories;

use CondMan\Domain\Repositories\CommonAreaReservationRepositoryInterface;
use CondMan\Domain\Entities\CommonAreaReservation;
use CondMan\Domain\Interfaces\LoggerInterface;
use DateTime;
use wpdb;
use Exception;

class WordPressCommonAreaReservationRepository implements CommonAreaReservationRepositoryInterface {
    private wpdb $wpdb;
    private LoggerInterface $logger;
    private string $reservationTable;
    private string $commonAreaTable;

    public function __construct(
        wpdb $wpdb, 
        LoggerInterface $logger,
        ?string $reservationTable = null,
        ?string $commonAreaTable = null
    ) {
        $this->wpdb = $wpdb;
        $this->logger = $logger;
        $this->reservationTable = $reservationTable ?? $this->wpdb->prefix . 'common_area_reservations';
        $this->commonAreaTable = $commonAreaTable ?? $this->wpdb->prefix . 'common_areas';
    }

    public function save(CommonAreaReservation $reservation): CommonAreaReservation {
        try {
            $data = [
                'common_area_id' => $reservation->getCommonAreaId(),
                'user_id' => $reservation->getUserId(),
                'start_time' => $reservation->getStartTime()->format('Y-m-d H:i:s'),
                'end_time' => $reservation->getEndTime()->format('Y-m-d H:i:s'),
                'status' => $reservation->getStatus(),
                'total_cost' => $reservation->getTotalCost(),
                'additional_details' => json_encode($reservation->getAdditionalDetails())
            ];

            if ($reservation->getId() === null) {
                // Inserir nova reserva
                $this->wpdb->insert($this->reservationTable, $data);
                $reservationId = $this->wpdb->insert_id;
            } else {
                // Atualizar reserva existente
                $this->wpdb->update(
                    $this->reservationTable, 
                    $data, 
                    ['id' => $reservation->getId()]
                );
                $reservationId = $reservation->getId();
            }

            $this->logger->info('Reserva de área comum salva', [
                'reservation_id' => $reservationId,
                'common_area_id' => $reservation->getCommonAreaId()
            ]);

            return $this->findById($reservationId);
        } catch (Exception $e) {
            $this->logger->error('Erro ao salvar reserva de área comum', [
                'error' => $e->getMessage(),
                'common_area_id' => $reservation->getCommonAreaId()
            ]);
            throw $e;
        }
    }

    public function findById(int $id): ?CommonAreaReservation {
        try {
            $result = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->reservationTable} WHERE id = %d", 
                    $id
                ), 
                ARRAY_A
            );

            return $result ? $this->createReservationFromDbResult($result) : null;
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar reserva de área comum por ID', [
                'error' => $e->getMessage(),
                'reservation_id' => $id
            ]);
            throw $e;
        }
    }

    public function findByFilters(array $filters): array {
        try {
            $conditions = [];
            $values = [];

            if (isset($filters['common_area_id'])) {
                $conditions[] = 'common_area_id = %d';
                $values[] = $filters['common_area_id'];
            }

            if (isset($filters['user_id'])) {
                $conditions[] = 'user_id = %d';
                $values[] = $filters['user_id'];
            }

            if (isset($filters['status'])) {
                $conditions[] = 'status = %s';
                $values[] = $filters['status'];
            }

            if (isset($filters['start_date'])) {
                $conditions[] = 'start_time >= %s';
                $values[] = $filters['start_date']->format('Y-m-d H:i:s');
            }

            if (isset($filters['end_date'])) {
                $conditions[] = 'end_time <= %s';
                $values[] = $filters['end_date']->format('Y-m-d H:i:s');
            }

            $query = "SELECT * FROM {$this->reservationTable}";
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }

            $query .= " ORDER BY start_time DESC";

            if (isset($filters['limit'])) {
                $query .= $this->wpdb->prepare(" LIMIT %d", $filters['limit']);
            }

            $results = $this->wpdb->get_results(
                $this->wpdb->prepare($query, $values), 
                ARRAY_A
            );

            return array_map(
                fn($result) => $this->createReservationFromDbResult($result), 
                $results
            );
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar reservas de área comum', [
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

            if (isset($filters['common_area_id'])) {
                $conditions[] = 'common_area_id = %d';
                $values[] = $filters['common_area_id'];
            }

            if (isset($filters['user_id'])) {
                $conditions[] = 'user_id = %d';
                $values[] = $filters['user_id'];
            }

            if (isset($filters['status'])) {
                $conditions[] = 'status = %s';
                $values[] = $filters['status'];
            }

            if (isset($filters['start_date'])) {
                $conditions[] = 'start_time >= %s';
                $values[] = $filters['start_date']->format('Y-m-d H:i:s');
            }

            if (isset($filters['end_date'])) {
                $conditions[] = 'end_time <= %s';
                $values[] = $filters['end_date']->format('Y-m-d H:i:s');
            }

            $query = "SELECT COUNT(*) FROM {$this->reservationTable}";
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }

            return (int) $this->wpdb->get_var(
                $this->wpdb->prepare($query, $values)
            );
        } catch (Exception $e) {
            $this->logger->error('Erro ao contar reservas de área comum', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    public function findConflictingReservations(
        int $commonAreaId, 
        DateTime $startTime, 
        DateTime $endTime
    ): array {
        try {
            $results = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->reservationTable} 
                     WHERE common_area_id = %d 
                     AND status != 'cancelled'
                     AND (
                         (start_time <= %s AND end_time >= %s) OR
                         (start_time <= %s AND end_time >= %s) OR
                         (start_time >= %s AND end_time <= %s)
                     )",
                    $commonAreaId,
                    $startTime->format('Y-m-d H:i:s'),
                    $startTime->format('Y-m-d H:i:s'),
                    $endTime->format('Y-m-d H:i:s'),
                    $endTime->format('Y-m-d H:i:s'),
                    $startTime->format('Y-m-d H:i:s'),
                    $endTime->format('Y-m-d H:i:s')
                ), 
                ARRAY_A
            );

            return array_map(
                fn($result) => $this->createReservationFromDbResult($result), 
                $results
            );
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar reservas conflitantes', [
                'error' => $e->getMessage(),
                'common_area_id' => $commonAreaId
            ]);
            throw $e;
        }
    }

    public function delete(int $reservationId): bool {
        try {
            $result = $this->wpdb->delete(
                $this->reservationTable, 
                ['id' => $reservationId]
            );

            $this->logger->info('Reserva de área comum removida', [
                'reservation_id' => $reservationId
            ]);

            return $result !== false;
        } catch (Exception $e) {
            $this->logger->error('Erro ao remover reserva de área comum', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservationId
            ]);
            throw $e;
        }
    }

    public function findByUser(int $userId): array {
        try {
            $results = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->reservationTable} 
                     WHERE user_id = %d 
                     ORDER BY start_time DESC", 
                    $userId
                ), 
                ARRAY_A
            );

            return array_map(
                fn($result) => $this->createReservationFromDbResult($result), 
                $results
            );
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar reservas do usuário', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    public function findByCommonArea(int $commonAreaId): array {
        try {
            $results = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->reservationTable} 
                     WHERE common_area_id = %d 
                     ORDER BY start_time DESC", 
                    $commonAreaId
                ), 
                ARRAY_A
            );

            return array_map(
                fn($result) => $this->createReservationFromDbResult($result), 
                $results
            );
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar reservas da área comum', [
                'error' => $e->getMessage(),
                'common_area_id' => $commonAreaId
            ]);
            throw $e;
        }
    }

    private function createReservationFromDbResult(array $result): CommonAreaReservation {
        return new CommonAreaReservation(
            (int) $result['id'],
            (int) $result['common_area_id'],
            (int) $result['user_id'],
            new DateTime($result['start_time']),
            new DateTime($result['end_time']),
            $result['status'],
            $result['total_cost'] ? (float) $result['total_cost'] : null,
            json_decode($result['additional_details'] ?? '{}', true),
            new DateTime($result['created_at']),
            $result['updated_at'] ? new DateTime($result['updated_at']) : null
        );
    }
}
