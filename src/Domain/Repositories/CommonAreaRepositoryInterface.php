<?php
namespace CondMan\Domain\Repositories;

use CondMan\Domain\Entities\CommonArea;

interface CommonAreaRepositoryInterface {
    /**
     * Salva uma nova área comum ou atualiza uma existente
     */
    public function save(CommonArea $commonArea): CommonArea;

    /**
     * Busca uma área comum por ID
     */
    public function findById(int $id): ?CommonArea;

    /**
     * Busca áreas comuns por filtros
     * @param array $filters Filtros para busca de áreas comuns
     * @return CommonArea[]
     */
    public function findByFilters(array $filters): array;

    /**
     * Conta áreas comuns por filtros
     * @param array $filters Filtros para contagem
     */
    public function countByFilters(array $filters): int;

    /**
     * Verifica disponibilidade de uma área comum
     * @param int $areaId ID da área comum
     * @param DateTime $startTime Início do período
     * @param DateTime $endTime Fim do período
     */
    public function checkAvailability(int $areaId, DateTime $startTime, DateTime $endTime): bool;

    /**
     * Remove área comum
     */
    public function delete(int $commonAreaId): bool;

    /**
     * Busca áreas comuns reserváveis
     */
    public function findReservableAreas(): array;
}
