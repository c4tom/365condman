<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Entities\CommonAreaReservation;
use CondMan\Domain\Repositories\CommonAreaReservationRepositoryInterface;
use CondMan\Domain\Repositories\CommonAreaRepositoryInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use CondMan\Domain\Interfaces\NotificationInterface;
use DateTime;
use Exception;

class CommonAreaReservationService {
    private CommonAreaReservationRepositoryInterface $reservationRepository;
    private CommonAreaRepositoryInterface $commonAreaRepository;
    private LoggerInterface $logger;
    private NotificationInterface $notificationService;

    public function __construct(
        CommonAreaReservationRepositoryInterface $reservationRepository,
        CommonAreaRepositoryInterface $commonAreaRepository,
        LoggerInterface $logger,
        NotificationInterface $notificationService
    ) {
        $this->reservationRepository = $reservationRepository;
        $this->commonAreaRepository = $commonAreaRepository;
        $this->logger = $logger;
        $this->notificationService = $notificationService;
    }

    /**
     * Cria uma nova reserva de área comum
     */
    public function createReservation(
        int $commonAreaId,
        int $userId,
        DateTime $startTime,
        DateTime $endTime,
        array $additionalDetails = []
    ): CommonAreaReservation {
        try {
            // Verificar disponibilidade da área comum
            $commonArea = $this->commonAreaRepository->findById($commonAreaId);
            if (!$commonArea) {
                throw new Exception("Área comum não encontrada");
            }

            if (!$commonArea->isReservable()) {
                throw new Exception("Área comum não é reservável");
            }

            // Verificar conflitos de reserva
            $conflictingReservations = $this->reservationRepository->findConflictingReservations(
                $commonAreaId, 
                $startTime, 
                $endTime
            );

            if (!empty($conflictingReservations)) {
                throw new Exception("Existem reservas conflitantes para este período");
            }

            // Calcular custo total
            $totalCost = null;
            if ($commonArea->getHourlyRate() !== null) {
                $hours = $startTime->diff($endTime)->h;
                $totalCost = $commonArea->getHourlyRate() * $hours;
            }

            // Criar reserva
            $reservation = new CommonAreaReservation(
                null,
                $commonAreaId,
                $userId,
                $startTime,
                $endTime,
                'pending',
                $totalCost,
                $additionalDetails
            );

            $savedReservation = $this->reservationRepository->save($reservation);

            // Notificar usuário
            $this->notificationService->sendNotification(
                $userId,
                'Reserva de Área Comum',
                "Sua reserva para {$commonArea->getName()} foi criada com sucesso.",
                ['dashboard', 'email']
            );

            $this->logger->info('Reserva de área comum criada', [
                'reservation_id' => $savedReservation->getId(),
                'common_area_id' => $commonAreaId,
                'user_id' => $userId
            ]);

            return $savedReservation;
        } catch (Exception $e) {
            $this->logger->error('Erro ao criar reserva de área comum', [
                'error' => $e->getMessage(),
                'common_area_id' => $commonAreaId,
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza uma reserva existente
     */
    public function updateReservation(
        int $reservationId,
        DateTime $startTime,
        DateTime $endTime,
        ?string $status = null,
        array $additionalDetails = []
    ): CommonAreaReservation {
        try {
            $reservation = $this->reservationRepository->findById($reservationId);

            if (!$reservation) {
                throw new Exception("Reserva não encontrada");
            }

            // Verificar conflitos de reserva
            $conflictingReservations = $this->reservationRepository->findConflictingReservations(
                $reservation->getCommonAreaId(), 
                $startTime, 
                $endTime
            );

            $conflictingReservations = array_filter(
                $conflictingReservations, 
                fn($r) => $r->getId() !== $reservationId
            );

            if (!empty($conflictingReservations)) {
                throw new Exception("Existem reservas conflitantes para este período");
            }

            // Calcular custo total
            $commonArea = $this->commonAreaRepository->findById($reservation->getCommonAreaId());
            $totalCost = null;
            if ($commonArea->getHourlyRate() !== null) {
                $hours = $startTime->diff($endTime)->h;
                $totalCost = $commonArea->getHourlyRate() * $hours;
            }

            // Atualizar reserva
            $reservation->updateReservation(
                $startTime,
                $endTime,
                $status ?? $reservation->getStatus(),
                $totalCost,
                array_merge($reservation->getAdditionalDetails(), $additionalDetails)
            );

            $savedReservation = $this->reservationRepository->save($reservation);

            // Notificar usuário
            $this->notificationService->sendNotification(
                $reservation->getUserId(),
                'Atualização de Reserva de Área Comum',
                "Sua reserva para {$commonArea->getName()} foi atualizada.",
                ['dashboard', 'email']
            );

            $this->logger->info('Reserva de área comum atualizada', [
                'reservation_id' => $savedReservation->getId()
            ]);

            return $savedReservation;
        } catch (Exception $e) {
            $this->logger->error('Erro ao atualizar reserva de área comum', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservationId
            ]);
            throw $e;
        }
    }

    /**
     * Cancela uma reserva
     */
    public function cancelReservation(int $reservationId): CommonAreaReservation {
        try {
            $reservation = $this->reservationRepository->findById($reservationId);

            if (!$reservation) {
                throw new Exception("Reserva não encontrada");
            }

            $reservation->updateStatus('cancelled');
            $savedReservation = $this->reservationRepository->save($reservation);

            $commonArea = $this->commonAreaRepository->findById($reservation->getCommonAreaId());

            // Notificar usuário
            $this->notificationService->sendNotification(
                $reservation->getUserId(),
                'Cancelamento de Reserva de Área Comum',
                "Sua reserva para {$commonArea->getName()} foi cancelada.",
                ['dashboard', 'email']
            );

            $this->logger->info('Reserva de área comum cancelada', [
                'reservation_id' => $savedReservation->getId()
            ]);

            return $savedReservation;
        } catch (Exception $e) {
            $this->logger->error('Erro ao cancelar reserva de área comum', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservationId
            ]);
            throw $e;
        }
    }

    /**
     * Busca reserva por ID
     */
    public function getReservationById(int $reservationId): ?CommonAreaReservation {
        try {
            $reservation = $this->reservationRepository->findById($reservationId);

            $this->logger->info('Reserva de área comum recuperada', [
                'reservation_id' => $reservationId
            ]);

            return $reservation;
        } catch (Exception $e) {
            $this->logger->error('Erro ao recuperar reserva de área comum', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservationId
            ]);
            throw $e;
        }
    }

    /**
     * Busca reservas por filtros
     */
    public function findReservationsByFilters(array $filters): array {
        try {
            $reservations = $this->reservationRepository->findByFilters($filters);

            $this->logger->info('Reservas de área comum recuperadas por filtros', [
                'filters' => $filters,
                'count' => count($reservations)
            ]);

            return $reservations;
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar reservas de área comum', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    /**
     * Busca reservas de um usuário
     */
    public function findUserReservations(int $userId): array {
        try {
            $reservations = $this->reservationRepository->findByUser($userId);

            $this->logger->info('Reservas do usuário recuperadas', [
                'user_id' => $userId,
                'count' => count($reservations)
            ]);

            return $reservations;
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar reservas do usuário', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * Busca reservas de uma área comum
     */
    public function findCommonAreaReservations(int $commonAreaId): array {
        try {
            $reservations = $this->reservationRepository->findByCommonArea($commonAreaId);

            $this->logger->info('Reservas da área comum recuperadas', [
                'common_area_id' => $commonAreaId,
                'count' => count($reservations)
            ]);

            return $reservations;
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar reservas da área comum', [
                'error' => $e->getMessage(),
                'common_area_id' => $commonAreaId
            ]);
            throw $e;
        }
    }
}
