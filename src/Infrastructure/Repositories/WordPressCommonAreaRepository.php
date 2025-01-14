<?php
namespace CondMan\Infrastructure\Repositories;

use CondMan\Domain\Repositories\CommonAreaRepositoryInterface;
use CondMan\Domain\Entities\CommonArea;
use CondMan\Domain\Interfaces\LoggerInterface;
use DateTime;
use wpdb;
use Exception;

class WordPressCommonAreaRepository implements CommonAreaRepositoryInterface {
    private wpdb $wpdb;
    private LoggerInterface $logger;
    private string $commonAreaTable;
    private string $reservationTable;

    public function __construct(
        wpdb $wpdb, 
        LoggerInterface $logger,
        ?string $commonAreaTable = null,
        ?string $reservationTable = null
    ) {
        $this->wpdb = $wpdb;
        $this->logger = $logger;
        $this->commonAreaTable = $commonAreaTable ?? $this->wpdb->prefix . 'common_areas';
        $this->reservationTable = $reservationTable ?? $this->wpdb->prefix . 'area_reservations';
    }

    public function save(CommonArea $commonArea): CommonArea {
        try {
            $data = [
                'name' => $commonArea->getName(),
                'description' => $commonArea->getDescription(),
                'type' => $commonArea->getType(),
                'capacity' => $commonArea->getCapacity(),
                'area' => $commonArea->getArea(),
                'amenities' => json_encode($commonArea->getAmenities()),
                'is_reservable' => $commonArea->isReservable() ? 1 : 0,
                'hourly_rate' => $commonArea->getHourlyRate(),
                'operating_hours' => json_encode($commonArea->getOperatingHours()),
                'restrictions' => json_encode($commonArea->getRestrictions())
            ];

            if ($commonArea->getId() === null) {
                // Inserir nova área comum
                $this->wpdb->insert($this->commonAreaTable, $data);
                $commonAreaId = $this->wpdb->insert_id;
            } else {
                // Atualizar área comum existente
                $this->wpdb->update(
                    $this->commonAreaTable, 
                    $data, 
                    ['id' => $commonArea->getId()]
                );
                $commonAreaId = $commonArea->getId();
            }

            $this->logger->info('Área comum salva', [
                'common_area_id' => $commonAreaId,
                'name' => $commonArea->getName()
            ]);

            return $this->findById($commonAreaId);
        } catch (Exception $e) {
            $this->logger->error('Erro ao salvar área comum', [
                'error' => $e->getMessage(),
                'name' => $commonArea->getName()
            ]);
            throw $e;
        }
    }

    public function findById(int $id): ?CommonArea {
        try {
            $result = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->commonAreaTable} WHERE id = %d", 
                    $id
                ), 
                ARRAY_A
            );

            return $result ? $this->createCommonAreaFromDbResult($result) : null;
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar área comum por ID', [
                'error' => $e->getMessage(),
                'common_area_id' => $id
            ]);
            throw $e;
        }
    }

    public function findByFilters(array $filters): array {
        try {
            $conditions = [];
            $values = [];

            if (isset($filters['type'])) {
                $conditions[] = 'type = %s';
                $values[] = $filters['type'];
            }

            if (isset($filters['is_reservable'])) {
                $conditions[] = 'is_reservable = %d';
                $values[] = $filters['is_reservable'] ? 1 : 0;
            }

            if (isset($filters['min_capacity'])) {
                $conditions[] = 'capacity >= %d';
                $values[] = $filters['min_capacity'];
            }

            $query = "SELECT * FROM {$this->commonAreaTable}";
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
                fn($result) => $this->createCommonAreaFromDbResult($result), 
                $results
            );
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar áreas comuns', [
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

            if (isset($filters['type'])) {
                $conditions[] = 'type = %s';
                $values[] = $filters['type'];
            }

            if (isset($filters['is_reservable'])) {
                $conditions[] = 'is_reservable = %d';
                $values[] = $filters['is_reservable'] ? 1 : 0;
            }

            if (isset($filters['min_capacity'])) {
                $conditions[] = 'capacity >= %d';
                $values[] = $filters['min_capacity'];
            }

            $query = "SELECT COUNT(*) FROM {$this->commonAreaTable}";
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }

            return (int) $this->wpdb->get_var(
                $this->wpdb->prepare($query, $values)
            );
        } catch (Exception $e) {
            $this->logger->error('Erro ao contar áreas comuns', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    public function checkAvailability(int $areaId, DateTime $startTime, DateTime $endTime): bool {
        try {
            $count = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->reservationTable} 
                     WHERE area_id = %d 
                     AND (
                         (start_time <= %s AND end_time >= %s) OR
                         (start_time <= %s AND end_time >= %s) OR
                         (start_time >= %s AND end_time <= %s)
                     )",
                    $areaId,
                    $startTime->format('Y-m-d H:i:s'),
                    $startTime->format('Y-m-d H:i:s'),
                    $endTime->format('Y-m-d H:i:s'),
                    $endTime->format('Y-m-d H:i:s'),
                    $startTime->format('Y-m-d H:i:s'),
                    $endTime->format('Y-m-d H:i:s')
                )
            );

            $this->logger->info('Verificação de disponibilidade de área comum', [
                'area_id' => $areaId,
                'start_time' => $startTime->format('Y-m-d H:i:s'),
                'end_time' => $endTime->format('Y-m-d H:i:s'),
                'is_available' => $count == 0
            ]);

            return $count == 0;
        } catch (Exception $e) {
            $this->logger->error('Erro ao verificar disponibilidade de área comum', [
                'error' => $e->getMessage(),
                'area_id' => $areaId
            ]);
            throw $e;
        }
    }

    public function delete(int $commonAreaId): bool {
        try {
            $result = $this->wpdb->delete(
                $this->commonAreaTable, 
                ['id' => $commonAreaId]
            );

            $this->logger->info('Área comum removida', [
                'common_area_id' => $commonAreaId
            ]);

            return $result !== false;
        } catch (Exception $e) {
            $this->logger->error('Erro ao remover área comum', [
                'error' => $e->getMessage(),
                'common_area_id' => $commonAreaId
            ]);
            throw $e;
        }
    }

    public function findReservableAreas(): array {
        try {
            $results = $this->wpdb->get_results(
                "SELECT * FROM {$this->commonAreaTable} WHERE is_reservable = 1", 
                ARRAY_A
            );

            return array_map(
                fn($result) => $this->createCommonAreaFromDbResult($result), 
                $results
            );
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar áreas reserváveis', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function createCommonAreaFromDbResult(array $result): CommonArea {
        return new CommonArea(
            (int) $result['id'],
            $result['name'],
            $result['description'],
            $result['type'],
            (int) $result['capacity'],
            (float) $result['area'],
            json_decode($result['amenities'] ?? '[]', true),
            (bool) $result['is_reservable'],
            $result['hourly_rate'] ? (float) $result['hourly_rate'] : null,
            json_decode($result['operating_hours'] ?? '{}', true),
            json_decode($result['restrictions'] ?? '[]', true),
            new DateTime($result['created_at']),
            $result['updated_at'] ? new DateTime($result['updated_at']) : null
        );
    }
}
