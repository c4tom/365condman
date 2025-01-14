<?php
namespace CondMan\Infrastructure\Providers;

use CondMan\Domain\Interfaces\CondominiumInterface;
use CondMan\Domain\Interfaces\UnitInterface;
use CondMan\Domain\Interfaces\InvoiceInterface;
use CondMan\Domain\Interfaces\CommunicationInterface;
use CondMan\Domain\Services\EntityMappingService;
use Doctrine\ORM\EntityManagerInterface;

class EntityMappingProvider {
    private EntityMappingService $mappingService;
    private array $mappableEntities;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->mappingService = new EntityMappingService($entityManager);
        $this->mappableEntities = [
            CondominiumInterface::class,
            UnitInterface::class,
            InvoiceInterface::class,
            CommunicationInterface::class
        ];
    }

    /**
     * Gera mapas para todas as entidades
     * @return array Mapas de entidades
     */
    public function generateAllEntityMaps(): array {
        $entityMaps = [];

        foreach ($this->mappableEntities as $entityClass) {
            $entityMaps[$entityClass] = $this->mappingService->generateEntityMap($entityClass);
        }

        return $entityMaps;
    }

    /**
     * Gera relatórios de mapeamento para todas as entidades
     * @return array Relatórios de mapeamento
     */
    public function generateMappingReports(): array {
        $mappingReports = [];

        foreach ($this->mappableEntities as $entityClass) {
            $mappingReports[$entityClass] = $this->mappingService->generateMappingReport($entityClass);
        }

        return $mappingReports;
    }

    /**
     * Valida o mapeamento de todas as entidades
     * @return bool Indica se todas as entidades estão corretamente mapeadas
     */
    public function validateAllEntityMappings(): bool {
        foreach ($this->mappableEntities as $entityClass) {
            if (!$this->mappingService->validateEntityMapping($entityClass)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Adiciona uma nova entidade mapeável
     * @param string $entityClass Classe da entidade
     */
    public function addMappableEntity(string $entityClass): void {
        if (!in_array($entityClass, $this->mappableEntities)) {
            $this->mappableEntities[] = $entityClass;
        }
    }

    /**
     * Remove uma entidade mapeável
     * @param string $entityClass Classe da entidade
     */
    public function removeMappableEntity(string $entityClass): void {
        $key = array_search($entityClass, $this->mappableEntities);
        if ($key !== false) {
            unset($this->mappableEntities[$key]);
        }
    }
}
