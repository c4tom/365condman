<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\LoggerInterface;
use CondMan\Domain\Repositories\CommonAreaReservationRepositoryInterface;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use DateTime;
use Exception;

class CalendarSyncService {
    private LoggerInterface $logger;
    private CommonAreaReservationRepositoryInterface $reservationRepository;
    private ?Google_Client $googleClient;

    public function __construct(
        LoggerInterface $logger,
        CommonAreaReservationRepositoryInterface $reservationRepository,
        ?Google_Client $googleClient = null
    ) {
        $this->logger = $logger;
        $this->reservationRepository = $reservationRepository;
        $this->googleClient = $googleClient;
    }

    /**
     * Exporta reservas de área comum para Google Calendar
     * @param int $userId ID do usuário
     * @param string $calendarId ID do calendário Google
     * @param DateTime $startDate Data inicial para busca de reservas
     * @param DateTime $endDate Data final para busca de reservas
     */
    public function exportReservationsToGoogleCalendar(
        int $userId, 
        string $calendarId, 
        DateTime $startDate, 
        DateTime $endDate
    ): array {
        try {
            if (!$this->googleClient) {
                throw new Exception("Cliente Google Calendar não configurado");
            }

            $this->googleClient->setScopes(Google_Service_Calendar::CALENDAR_EVENTS);
            $calendarService = new Google_Service_Calendar($this->googleClient);

            $filters = [
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ];

            $reservations = $this->reservationRepository->findByFilters($filters);
            $exportedEvents = [];

            foreach ($reservations as $reservation) {
                $event = new Google_Service_Calendar_Event([
                    'summary' => "Reserva de Área Comum",
                    'location' => $this->getCommonAreaName($reservation->getCommonAreaId()),
                    'description' => $this->getReservationDescription($reservation),
                    'start' => [
                        'dateTime' => $reservation->getStartTime()->format(DateTime::RFC3339),
                        'timeZone' => 'America/Sao_Paulo',
                    ],
                    'end' => [
                        'dateTime' => $reservation->getEndTime()->format(DateTime::RFC3339),
                        'timeZone' => 'America/Sao_Paulo',
                    ],
                ]);

                $createdEvent = $calendarService->events->insert($calendarId, $event);
                $exportedEvents[] = $createdEvent->getId();

                $this->logger->info('Reserva exportada para Google Calendar', [
                    'reservation_id' => $reservation->getId(),
                    'google_event_id' => $createdEvent->getId()
                ]);
            }

            return $exportedEvents;
        } catch (Exception $e) {
            $this->logger->error('Erro ao exportar reservas para Google Calendar', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * Importa eventos do Google Calendar para reservas
     * @param int $userId ID do usuário
     * @param string $calendarId ID do calendário Google
     * @param DateTime $startDate Data inicial para importação
     * @param DateTime $endDate Data final para importação
     */
    public function importEventsFromGoogleCalendar(
        int $userId, 
        string $calendarId, 
        DateTime $startDate, 
        DateTime $endDate
    ): array {
        try {
            if (!$this->googleClient) {
                throw new Exception("Cliente Google Calendar não configurado");
            }

            $this->googleClient->setScopes(Google_Service_Calendar::CALENDAR_EVENTS_READONLY);
            $calendarService = new Google_Service_Calendar($this->googleClient);

            $events = $calendarService->events->listEvents($calendarId, [
                'timeMin' => $startDate->format(DateTime::RFC3339),
                'timeMax' => $endDate->format(DateTime::RFC3339),
                'singleEvents' => true,
                'orderBy' => 'startTime'
            ]);

            $importedReservations = [];

            foreach ($events->getItems() as $event) {
                $commonAreaId = $this->findCommonAreaByName($event->getLocation());

                if ($commonAreaId) {
                    $reservation = $this->createReservationFromEvent(
                        $userId, 
                        $commonAreaId, 
                        $event
                    );

                    $importedReservations[] = $reservation;

                    $this->logger->info('Evento importado do Google Calendar', [
                        'google_event_id' => $event->getId(),
                        'reservation_id' => $reservation->getId()
                    ]);
                }
            }

            return $importedReservations;
        } catch (Exception $e) {
            $this->logger->error('Erro ao importar eventos do Google Calendar', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * Sincroniza reservas com Google Calendar
     * @param int $userId ID do usuário
     * @param string $calendarId ID do calendário Google
     * @param DateTime $startDate Data inicial para sincronização
     * @param DateTime $endDate Data final para sincronização
     */
    public function syncReservationsWithGoogleCalendar(
        int $userId, 
        string $calendarId, 
        DateTime $startDate, 
        DateTime $endDate
    ): array {
        try {
            $exportedEvents = $this->exportReservationsToGoogleCalendar(
                $userId, 
                $calendarId, 
                $startDate, 
                $endDate
            );

            $importedReservations = $this->importEventsFromGoogleCalendar(
                $userId, 
                $calendarId, 
                $startDate, 
                $endDate
            );

            $this->logger->info('Sincronização com Google Calendar concluída', [
                'user_id' => $userId,
                'exported_events' => count($exportedEvents),
                'imported_reservations' => count($importedReservations)
            ]);

            return [
                'exported_events' => $exportedEvents,
                'imported_reservations' => $importedReservations
            ];
        } catch (Exception $e) {
            $this->logger->error('Erro na sincronização com Google Calendar', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * Obtém nome da área comum
     */
    private function getCommonAreaName(int $commonAreaId): string {
        // Implementação fictícia - substituir por chamada real ao repositório
        return "Área Comum #{$commonAreaId}";
    }

    /**
     * Obtém descrição detalhada da reserva
     */
    private function getReservationDescription(
        CommonAreaReservation $reservation
    ): string {
        return sprintf(
            "Reserva de área comum\nStatus: %s\nCusto: R$ %.2f",
            $reservation->getStatus(),
            $reservation->getTotalCost() ?? 0
        );
    }

    /**
     * Encontra área comum pelo nome
     */
    private function findCommonAreaByName(string $name): ?int {
        // Implementação fictícia - substituir por chamada real ao repositório
        return preg_match('/Área Comum #(\d+)/', $name, $matches) 
            ? (int) $matches[1] 
            : null;
    }

    /**
     * Cria reserva a partir de evento do Google Calendar
     */
    private function createReservationFromEvent(
        int $userId, 
        int $commonAreaId, 
        Google_Service_Calendar_Event $event
    ): CommonAreaReservation {
        $startTime = new DateTime($event->getStart()->dateTime);
        $endTime = new DateTime($event->getEnd()->dateTime);

        return $this->reservationRepository->save(
            new CommonAreaReservation(
                null,
                $commonAreaId,
                $userId,
                $startTime,
                $endTime,
                'imported',
                null,
                [
                    'google_event_id' => $event->getId(),
                    'description' => $event->getDescription()
                ]
            )
        );
    }
}
