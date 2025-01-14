<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\ValidatorInterface;
use CondMan\Domain\Interfaces\TransformerInterface;
use CondMan\Infrastructure\Repositories\CondominiumRepository;
use CondMan\Infrastructure\Repositories\UnitRepository;
use Psr\Log\LoggerInterface;

class CondominiumPersistenceService extends AbstractPersistenceService {
    private UnitRepository $unitRepository;

    public function __construct(
        CondominiumRepository $repository, 
        UnitRepository $unitRepository,
        LoggerInterface $logger, 
        ?ValidatorInterface $validator = null, 
        ?TransformerInterface $transformer = null
    ) {
        parent::__construct($repository, $logger, $validator, $transformer);
        $this->unitRepository = $unitRepository;
    }

    /**
     * Busca condomínios por CNPJ
     * @param string $cnpj CNPJ do condomínio
     * @return array|null Dados do condomínio
     */
    public function findByCNPJ(string $cnpj): ?array {
        try {
            /** @var CondominiumRepository $repository */
            $repository = $this->repository;
            return $repository->findByCNPJ($cnpj);
        } catch (\Exception $e) {
            $this->logger->error('Error finding condominium by CNPJ', [
                'exception' => $e->getMessage(),
                'cnpj' => $cnpj
            ]);
            throw $e;
        }
    }

    /**
     * Busca condomínios por nome
     * @param string $name Nome do condomínio
     * @param bool $partial Busca parcial
     * @return array Lista de condomínios
     */
    public function findByName(string $name, bool $partial = false): array {
        try {
            /** @var CondominiumRepository $repository */
            $repository = $this->repository;
            return $repository->findByName($name, $partial);
        } catch (\Exception $e) {
            $this->logger->error('Error finding condominiums by name', [
                'exception' => $e->getMessage(),
                'name' => $name,
                'partial' => $partial
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza a contagem de unidades de um condomínio
     * @param int $condominiumId ID do condomínio
     * @return bool Indica se a atualização foi bem-sucedida
     */
    public function updateUnitCount(int $condominiumId): bool {
        try {
            // Conta unidades por status
            $unitStatusCount = $this->unitRepository->countUnitsByStatus($condominiumId);
            
            $totalUnits = 0;
            $occupiedUnits = 0;

            foreach ($unitStatusCount as $statusCount) {
                $totalUnits += $statusCount['count'];
                if (in_array($statusCount['status'], ['OCCUPIED', 'OWNER', 'TENANT'])) {
                    $occupiedUnits += $statusCount['count'];
                }
            }

            /** @var CondominiumRepository $repository */
            $repository = $this->repository;
            return $repository->updateUnitCount($condominiumId, $totalUnits, $occupiedUnits);
        } catch (\Exception $e) {
            $this->logger->error('Error updating condominium unit count', [
                'exception' => $e->getMessage(),
                'condominiumId' => $condominiumId
            ]);
            throw $e;
        }
    }

    /**
     * Encontra condomínios com número mínimo de unidades
     * @param int $minUnits Número mínimo de unidades
     * @return array Lista de condomínios
     */
    public function findByMinUnits(int $minUnits): array {
        try {
            /** @var CondominiumRepository $repository */
            $repository = $this->repository;
            return $repository->findByMinUnits($minUnits);
        } catch (\Exception $e) {
            $this->logger->error('Error finding condominiums by minimum units', [
                'exception' => $e->getMessage(),
                'minUnits' => $minUnits
            ]);
            throw $e;
        }
    }
}
