<?php
namespace CondMan\Domain\Repositories;

use CondMan\Domain\Entities\CommonAreaReservation;
use DateTime;

interface CommonAreaReservationRepositoryInterface {
    /**
     * Salva uma nova reserva ou atualiza uma existente
     */
    public function save(CommonAreaReservation $reservation): CommonAreaReservation;

    /**
     * Busca uma reserva por ID
     */
    public function findById(int $id): ?CommonAreaReservation;

    /**
     * Busca reservas por filtros
     * @param array $filters Filtros para busca de reservas
     * @return CommonAreaReservation[]
     */
    public function findByFilters(array $filters): array;

    /**
     * Conta reservas por filtros
     * @param array $filters Filtros para contagem
     */
    public function countByFilters(array $filters): int;

    /**
     * Verifica conflitos de reserva para uma área comum
     * @param int $commonAreaId ID da área comum
     * @param DateTime $startTime Início do período
     * @param DateTime $endTime Fim do período
     * @return CommonAreaReservation[]
     */
    public function findConflictingReservations(
        int $commonAreaId, 
        DateTime $startTime, 
        DateTime $endTime
    ): array;

    /**
     * Remove reserva
     */
    public function delete(int $reservationId): bool;

    /**
     * Busca reservas de um usuário
     * @param int $userId ID do usuário
     * @return CommonAreaReservation[]
     */
    public function findByUser(int $userId): array;

    /**
     * Busca reservas de uma área comum
     * @param int $commonAreaId ID da área comum
     * @return CommonAreaReservation[]
     */
    public function findByCommonArea(int $commonAreaId): array;
}
