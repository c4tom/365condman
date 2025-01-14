<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Entities\CommonArea;
use CondMan\Domain\Repositories\CommonAreaRepositoryInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use DateTime;
use Exception;

class CommonAreaService {
    private CommonAreaRepositoryInterface $repository;
    private LoggerInterface $logger;

    public function __construct(
        CommonAreaRepositoryInterface $repository,
        LoggerInterface $logger
    ) {
        $this->repository = $repository;
        $this->logger = $logger;
    }

    /**
     * Cria uma nova área comum
     */
    public function createCommonArea(
        string $name,
        string $description,
        string $type = 'multipurpose',
        int $capacity = 0,
        float $area = 0.0,
        array $amenities = [],
        bool $isReservable = false,
        ?float $hourlyRate = null,
        array $operatingHours = [],
        array $restrictions = []
    ): CommonArea {
        try {
            $commonArea = new CommonArea(
                null,
                $name,
                $description,
                $type,
                $capacity,
                $area,
                $amenities,
                $isReservable,
                $hourlyRate,
                $operatingHours,
                $restrictions
            );

            $savedCommonArea = $this->repository->save($commonArea);

            $this->logger->info('Área comum criada', [
                'common_area_id' => $savedCommonArea->getId(),
                'name' => $name
            ]);

            return $savedCommonArea;
        } catch (Exception $e) {
            $this->logger->error('Erro ao criar área comum', [
                'error' => $e->getMessage(),
                'name' => $name
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza uma área comum existente
     */
    public function updateCommonArea(
        int $commonAreaId,
        string $name,
        string $description,
        string $type = 'multipurpose',
        int $capacity = 0,
        float $area = 0.0,
        array $amenities = [],
        bool $isReservable = false,
        ?float $hourlyRate = null,
        array $operatingHours = [],
        array $restrictions = []
    ): CommonArea {
        try {
            $commonArea = $this->repository->findById($commonAreaId);

            if (!$commonArea) {
                throw new Exception("Área comum não encontrada");
            }

            $commonArea->updateDetails(
                $name,
                $description,
                $type,
                $capacity,
                $area,
                $amenities,
                $isReservable,
                $hourlyRate,
                $operatingHours,
                $restrictions
            );

            $savedCommonArea = $this->repository->save($commonArea);

            $this->logger->info('Área comum atualizada', [
                'common_area_id' => $commonAreaId,
                'name' => $name
            ]);

            return $savedCommonArea;
        } catch (Exception $e) {
            $this->logger->error('Erro ao atualizar área comum', [
                'error' => $e->getMessage(),
                'common_area_id' => $commonAreaId
            ]);
            throw $e;
        }
    }

    /**
     * Busca área comum por ID
     */
    public function getCommonAreaById(int $commonAreaId): ?CommonArea {
        try {
            $commonArea = $this->repository->findById($commonAreaId);

            $this->logger->info('Área comum recuperada', [
                'common_area_id' => $commonAreaId
            ]);

            return $commonArea;
        } catch (Exception $e) {
            $this->logger->error('Erro ao recuperar área comum', [
                'error' => $e->getMessage(),
                'common_area_id' => $commonAreaId
            ]);
            throw $e;
        }
    }

    /**
     * Busca áreas comuns por filtros
     */
    public function findCommonAreasByFilters(array $filters): array {
        try {
            $commonAreas = $this->repository->findByFilters($filters);

            $this->logger->info('Áreas comuns recuperadas por filtros', [
                'filters' => $filters,
                'count' => count($commonAreas)
            ]);

            return $commonAreas;
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar áreas comuns', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    /**
     * Verifica disponibilidade de área comum
     */
    public function checkCommonAreaAvailability(
        int $commonAreaId, 
        DateTime $startTime, 
        DateTime $endTime
    ): bool {
        try {
            $isAvailable = $this->repository->checkAvailability(
                $commonAreaId, 
                $startTime, 
                $endTime
            );

            $this->logger->info('Disponibilidade de área comum verificada', [
                'common_area_id' => $commonAreaId,
                'start_time' => $startTime->format('Y-m-d H:i:s'),
                'end_time' => $endTime->format('Y-m-d H:i:s'),
                'is_available' => $isAvailable
            ]);

            return $isAvailable;
        } catch (Exception $e) {
            $this->logger->error('Erro ao verificar disponibilidade de área comum', [
                'error' => $e->getMessage(),
                'common_area_id' => $commonAreaId
            ]);
            throw $e;
        }
    }

    /**
     * Busca áreas reserváveis
     */
    public function findReservableAreas(): array {
        try {
            $reservableAreas = $this->repository->findReservableAreas();

            $this->logger->info('Áreas reserváveis recuperadas', [
                'count' => count($reservableAreas)
            ]);

            return $reservableAreas;
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar áreas reserváveis', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Remove área comum
     */
    public function deleteCommonArea(int $commonAreaId): bool {
        try {
            $result = $this->repository->delete($commonAreaId);

            $this->logger->info('Área comum removida', [
                'common_area_id' => $commonAreaId
            ]);

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Erro ao remover área comum', [
                'error' => $e->getMessage(),
                'common_area_id' => $commonAreaId
            ]);
            throw $e;
        }
    }

    /**
     * Adiciona amenidade a uma área comum
     */
    public function addCommonAreaAmenity(int $commonAreaId, string $amenity): CommonArea {
        try {
            $commonArea = $this->repository->findById($commonAreaId);

            if (!$commonArea) {
                throw new Exception("Área comum não encontrada");
            }

            $commonArea->addAmenity($amenity);
            $savedCommonArea = $this->repository->save($commonArea);

            $this->logger->info('Amenidade adicionada à área comum', [
                'common_area_id' => $commonAreaId,
                'amenity' => $amenity
            ]);

            return $savedCommonArea;
        } catch (Exception $e) {
            $this->logger->error('Erro ao adicionar amenidade à área comum', [
                'error' => $e->getMessage(),
                'common_area_id' => $commonAreaId,
                'amenity' => $amenity
            ]);
            throw $e;
        }
    }

    /**
     * Remove amenidade de uma área comum
     */
    public function removeCommonAreaAmenity(int $commonAreaId, string $amenity): CommonArea {
        try {
            $commonArea = $this->repository->findById($commonAreaId);

            if (!$commonArea) {
                throw new Exception("Área comum não encontrada");
            }

            $commonArea->removeAmenity($amenity);
            $savedCommonArea = $this->repository->save($commonArea);

            $this->logger->info('Amenidade removida da área comum', [
                'common_area_id' => $commonAreaId,
                'amenity' => $amenity
            ]);

            return $savedCommonArea;
        } catch (Exception $e) {
            $this->logger->error('Erro ao remover amenidade da área comum', [
                'error' => $e->getMessage(),
                'common_area_id' => $commonAreaId,
                'amenity' => $amenity
            ]);
            throw $e;
        }
    }
}
