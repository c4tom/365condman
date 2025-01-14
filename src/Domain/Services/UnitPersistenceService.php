<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\ValidatorInterface;
use CondMan\Domain\Interfaces\TransformerInterface;
use CondMan\Infrastructure\Repositories\UnitRepository;
use CondMan\Infrastructure\Repositories\CondominiumRepository;
use Psr\Log\LoggerInterface;

class UnitPersistenceService extends AbstractPersistenceService {
    private CondominiumRepository $condominiumRepository;
    private CondominiumPersistenceService $condominiumPersistenceService;

    public function __construct(
        UnitRepository $repository, 
        CondominiumRepository $condominiumRepository,
        CondominiumPersistenceService $condominiumPersistenceService,
        LoggerInterface $logger, 
        ?ValidatorInterface $validator = null, 
        ?TransformerInterface $transformer = null
    ) {
        parent::__construct($repository, $logger, $validator, $transformer);
        $this->condominiumRepository = $condominiumRepository;
        $this->condominiumPersistenceService = $condominiumPersistenceService;
    }

    /**
     * Salva uma nova unidade e atualiza contagem do condomínio
     * @param array $data Dados da unidade
     * @return int ID da unidade salva
     * @throws \Exception Erro durante a persistência
     */
    public function save(array $data): int {
        try {
            $this->validateData($data);
            $transformedData = $this->transformData($data);

            // Inicia transação
            $this->beginTransaction();

            // Salva a unidade
            $unitId = $this->repository->insert($transformedData);

            // Atualiza contagem de unidades do condomínio
            $this->condominiumPersistenceService->updateUnitCount($transformedData['condominium_id']);

            // Confirma transação
            $this->commit();

            return $unitId;
        } catch (\Exception $e) {
            // Reverte transação em caso de erro
            $this->rollback();

            $this->logger->error('Error saving unit', [
                'exception' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza uma unidade e recalcula contagem do condomínio
     * @param int $id ID da unidade
     * @param array $data Dados atualizados
     * @return bool Indica se a atualização foi bem-sucedida
     * @throws \Exception Erro durante a atualização
     */
    public function update(int $id, array $data): bool {
        try {
            $this->validateData($data);
            $transformedData = $this->transformData($data);

            // Inicia transação
            $this->beginTransaction();

            // Busca unidade atual para verificar mudança de condomínio
            $currentUnit = $this->findById($id);
            $currentCondominiumId = $currentUnit['condominium_id'];

            // Atualiza a unidade
            $updateResult = $this->repository->update($id, $transformedData);

            // Atualiza contagem de unidades dos condomínios
            $this->condominiumPersistenceService->updateUnitCount($currentCondominiumId);
            
            // Se o condomínio mudou, atualiza também o novo condomínio
            if ($currentCondominiumId !== $transformedData['condominium_id']) {
                $this->condominiumPersistenceService->updateUnitCount($transformedData['condominium_id']);
            }

            // Confirma transação
            $this->commit();

            return $updateResult;
        } catch (\Exception $e) {
            // Reverte transação em caso de erro
            $this->rollback();

            $this->logger->error('Error updating unit', [
                'exception' => $e->getMessage(),
                'id' => $id,
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Remove uma unidade e atualiza contagem do condomínio
     * @param int $id ID da unidade
     * @return bool Indica se a remoção foi bem-sucedida
     * @throws \Exception Erro durante a remoção
     */
    public function delete(int $id): bool {
        try {
            // Inicia transação
            $this->beginTransaction();

            // Busca unidade para obter ID do condomínio
            $unit = $this->findById($id);
            $condominiumId = $unit['condominium_id'];

            // Remove a unidade
            $deleteResult = $this->repository->delete($id);

            // Atualiza contagem de unidades do condomínio
            $this->condominiumPersistenceService->updateUnitCount($condominiumId);

            // Confirma transação
            $this->commit();

            return $deleteResult;
        } catch (\Exception $e) {
            // Reverte transação em caso de erro
            $this->rollback();

            $this->logger->error('Error deleting unit', [
                'exception' => $e->getMessage(),
                'id' => $id
            ]);
            throw $e;
        }
    }

    /**
     * Encontra unidades por condomínio
     * @param int $condominiumId ID do condomínio
     * @return array Lista de unidades
     */
    public function findByCondominium(int $condominiumId): array {
        try {
            /** @var UnitRepository $repository */
            $repository = $this->repository;
            return $repository->findByCondominium($condominiumId);
        } catch (\Exception $e) {
            $this->logger->error('Error finding units by condominium', [
                'exception' => $e->getMessage(),
                'condominiumId' => $condominiumId
            ]);
            throw $e;
        }
    }

    /**
     * Encontra unidades por bloco
     * @param int $condominiumId ID do condomínio
     * @param string $block Bloco da unidade
     * @return array Lista de unidades
     */
    public function findByBlock(int $condominiumId, string $block): array {
        try {
            /** @var UnitRepository $repository */
            $repository = $this->repository;
            return $repository->findByBlock($condominiumId, $block);
        } catch (\Exception $e) {
            $this->logger->error('Error finding units by block', [
                'exception' => $e->getMessage(),
                'condominiumId' => $condominiumId,
                'block' => $block
            ]);
            throw $e;
        }
    }

    /**
     * Encontra unidades por status
     * @param int $condominiumId ID do condomínio
     * @param string $status Status da unidade
     * @return array Lista de unidades
     */
    public function findByStatus(int $condominiumId, string $status): array {
        try {
            /** @var UnitRepository $repository */
            $repository = $this->repository;
            return $repository->findByStatus($condominiumId, $status);
        } catch (\Exception $e) {
            $this->logger->error('Error finding units by status', [
                'exception' => $e->getMessage(),
                'condominiumId' => $condominiumId,
                'status' => $status
            ]);
            throw $e;
        }
    }

    /**
     * Encontra unidades por tipo
     * @param int $condominiumId ID do condomínio
     * @param string $type Tipo da unidade
     * @return array Lista de unidades
     */
    public function findByType(int $condominiumId, string $type): array {
        try {
            /** @var UnitRepository $repository */
            $repository = $this->repository;
            return $repository->findByType($condominiumId, $type);
        } catch (\Exception $e) {
            $this->logger->error('Error finding units by type', [
                'exception' => $e->getMessage(),
                'condominiumId' => $condominiumId,
                'type' => $type
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza o status de uma unidade
     * @param int $unitId ID da unidade
     * @param string $status Novo status
     * @return bool Indica se a atualização foi bem-sucedida
     */
    public function updateStatus(int $unitId, string $status): bool {
        try {
            // Inicia transação
            $this->beginTransaction();

            // Busca unidade para obter ID do condomínio
            $unit = $this->findById($unitId);
            $condominiumId = $unit['condominium_id'];

            // Atualiza o status da unidade
            /** @var UnitRepository $repository */
            $repository = $this->repository;
            $updateResult = $repository->updateStatus($unitId, $status);

            // Atualiza contagem de unidades do condomínio
            $this->condominiumPersistenceService->updateUnitCount($condominiumId);

            // Confirma transação
            $this->commit();

            return $updateResult;
        } catch (\Exception $e) {
            // Reverte transação em caso de erro
            $this->rollback();

            $this->logger->error('Error updating unit status', [
                'exception' => $e->getMessage(),
                'unitId' => $unitId,
                'status' => $status
            ]);
            throw $e;
        }
    }
}
