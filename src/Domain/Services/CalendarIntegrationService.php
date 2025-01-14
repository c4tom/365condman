<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\CalendarIntegrationInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use DateTime;
use DateTimeZone;
use Exception;

class CalendarIntegrationService implements CalendarIntegrationInterface {
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function exportEvents(array $events, string $format = 'ics'): string {
        try {
            switch ($format) {
                case 'ics':
                    return $this->exportToICalendar($events);
                case 'google':
                    return $this->exportToGoogleCalendar($events);
                default:
                    throw new Exception("Formato de exportação não suportado: {$format}");
            }
        } catch (Exception $e) {
            $this->logger->error('Erro ao exportar eventos', [
                'error' => $e->getMessage(),
                'format' => $format
            ]);
            throw $e;
        }
    }

    public function importEvents(string $source, array $filters = []): array {
        try {
            $fileExtension = strtolower(pathinfo($source, PATHINFO_EXTENSION));

            switch ($fileExtension) {
                case 'ics':
                    return $this->importFromICalendar($source, $filters);
                case 'csv':
                    return $this->importFromCsv($source, $filters);
                default:
                    throw new Exception("Formato de importação não suportado: {$fileExtension}");
            }
        } catch (Exception $e) {
            $this->logger->error('Erro ao importar eventos', [
                'error' => $e->getMessage(),
                'source' => $source
            ]);
            throw $e;
        }
    }

    public function convertEvents(
        array $events, 
        string $sourceFormat, 
        string $targetFormat
    ): array {
        try {
            $convertedEvents = [];
            foreach ($events as $event) {
                $convertedEvent = match($sourceFormat . '_to_' . $targetFormat) {
                    'ics_to_google' => $this->convertIcsToGoogle($event),
                    'google_to_ics' => $this->convertGoogleToIcs($event),
                    default => throw new Exception("Conversão não suportada: {$sourceFormat} para {$targetFormat}")
                };
                $convertedEvents[] = $convertedEvent;
            }
            return $convertedEvents;
        } catch (Exception $e) {
            $this->logger->error('Erro ao converter eventos', [
                'error' => $e->getMessage(),
                'source_format' => $sourceFormat,
                'target_format' => $targetFormat
            ]);
            throw $e;
        }
    }

    public function validateCalendarSource(string $source): bool {
        try {
            $fileExtension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, ['ics', 'csv'])) {
                return false;
            }

            if (!file_exists($source)) {
                return false;
            }

            $fileContent = file_get_contents($source);
            
            return match($fileExtension) {
                'ics' => $this->validateIcsContent($fileContent),
                'csv' => $this->validateCsvContent($fileContent),
                default => false
            };
        } catch (Exception $e) {
            $this->logger->error('Erro ao validar fonte de calendário', [
                'error' => $e->getMessage(),
                'source' => $source
            ]);
            return false;
        }
    }

    public function generateEventUid(array $eventData): string {
        $uniqueString = implode('|', [
            $eventData['title'] ?? '',
            $eventData['start_time'] ?? '',
            $eventData['end_time'] ?? '',
            $eventData['location'] ?? ''
        ]);

        return hash('sha256', $uniqueString);
    }

    private function exportToICalendar(array $events): string {
        $icsContent = "BEGIN:VCALENDAR\r\n";
        $icsContent .= "VERSION:2.0\r\n";
        $icsContent .= "PRODID:-//365 Cond Man//Calendar Export//PT\r\n";

        foreach ($events as $event) {
            $icsContent .= $this->createIcsEvent($event);
        }

        $icsContent .= "END:VCALENDAR\r\n";
        return $icsContent;
    }

    private function createIcsEvent(array $event): string {
        $startTime = new DateTime($event['start_time']);
        $endTime = new DateTime($event['end_time']);

        $icsEvent = "BEGIN:VEVENT\r\n";
        $icsEvent .= "UID:" . $this->generateEventUid($event) . "\r\n";
        $icsEvent .= "SUMMARY:" . $this->escapeIcsValue($event['title'] ?? 'Evento') . "\r\n";
        $icsEvent .= "DTSTART:" . $startTime->format('Ymd\THis\Z') . "\r\n";
        $icsEvent .= "DTEND:" . $endTime->format('Ymd\THis\Z') . "\r\n";
        $icsEvent .= "DESCRIPTION:" . $this->escapeIcsValue($event['description'] ?? '') . "\r\n";
        $icsEvent .= "LOCATION:" . $this->escapeIcsValue($event['location'] ?? '') . "\r\n";
        $icsEvent .= "END:VEVENT\r\n";

        return $icsEvent;
    }

    private function escapeIcsValue(string $value): string {
        return str_replace(
            ["\n", ";", ","],
            ["\\n", "\\;", "\\,"],
            $value
        );
    }

    private function importFromICalendar(string $source, array $filters = []): array {
        $icsContent = file_get_contents($source);
        $events = $this->parseIcsContent($icsContent);

        return $this->filterEvents($events, $filters);
    }

    private function parseIcsContent(string $icsContent): array {
        $events = [];
        $lines = explode("\r\n", $icsContent);
        $currentEvent = [];
        $inEvent = false;

        foreach ($lines as $line) {
            if (trim($line) === 'BEGIN:VEVENT') {
                $inEvent = true;
                $currentEvent = [];
            } elseif (trim($line) === 'END:VEVENT') {
                $events[] = $this->normalizeIcsEvent($currentEvent);
                $inEvent = false;
            } elseif ($inEvent) {
                $this->processIcsLine($line, $currentEvent);
            }
        }

        return $events;
    }

    private function processIcsLine(string $line, array &$currentEvent): void {
        $parts = explode(':', $line, 2);
        if (count($parts) !== 2) return;

        $key = $parts[0];
        $value = $parts[1];

        switch ($key) {
            case 'SUMMARY':
                $currentEvent['title'] = $this->unescapeIcsValue($value);
                break;
            case 'DTSTART':
                $currentEvent['start_time'] = $this->parseIcsDateTime($value);
                break;
            case 'DTEND':
                $currentEvent['end_time'] = $this->parseIcsDateTime($value);
                break;
            case 'DESCRIPTION':
                $currentEvent['description'] = $this->unescapeIcsValue($value);
                break;
            case 'LOCATION':
                $currentEvent['location'] = $this->unescapeIcsValue($value);
                break;
        }
    }

    private function parseIcsDateTime(string $dateTime): string {
        try {
            $dt = new DateTime($dateTime, new DateTimeZone('UTC'));
            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return '';
        }
    }

    private function unescapeIcsValue(string $value): string {
        return str_replace(
            ["\\n", "\\;", "\\,"],
            ["\n", ";", ","],
            $value
        );
    }

    private function normalizeIcsEvent(array $event): array {
        return [
            'title' => $event['title'] ?? '',
            'start_time' => $event['start_time'] ?? '',
            'end_time' => $event['end_time'] ?? '',
            'description' => $event['description'] ?? '',
            'location' => $event['location'] ?? ''
        ];
    }

    private function filterEvents(array $events, array $filters): array {
        return array_filter($events, function($event) use ($filters) {
            foreach ($filters as $key => $value) {
                if (!isset($event[$key]) || $event[$key] !== $value) {
                    return false;
                }
            }
            return true;
        });
    }

    private function importFromCsv(string $source, array $filters = []): array {
        // Implementação simplificada de importação CSV
        $events = [];
        $csvData = array_map('str_getcsv', file($source));
        $headers = array_shift($csvData);

        foreach ($csvData as $row) {
            $event = array_combine($headers, $row);
            $events[] = $this->normalizeCsvEvent($event);
        }

        return $this->filterEvents($events, $filters);
    }

    private function normalizeCsvEvent(array $event): array {
        return [
            'title' => $event['title'] ?? '',
            'start_time' => $event['start_time'] ?? '',
            'end_time' => $event['end_time'] ?? '',
            'description' => $event['description'] ?? '',
            'location' => $event['location'] ?? ''
        ];
    }

    private function validateIcsContent(string $content): bool {
        return strpos($content, 'BEGIN:VCALENDAR') !== false 
            && strpos($content, 'END:VCALENDAR') !== false;
    }

    private function validateCsvContent(string $content): bool {
        $lines = explode("\n", $content);
        return count($lines) > 1 && 
               str_contains($lines[0], 'title') && 
               str_contains($lines[0], 'start_time');
    }

    private function exportToGoogleCalendar(array $events): string {
        // Placeholder para exportação para Google Calendar
        // Na prática, usaria a biblioteca do Google Calendar
        return json_encode($events);
    }

    private function convertIcsToGoogle(array $event): array {
        return [
            'summary' => $event['title'],
            'start' => [
                'dateTime' => $event['start_time'],
                'timeZone' => 'America/Sao_Paulo'
            ],
            'end' => [
                'dateTime' => $event['end_time'],
                'timeZone' => 'America/Sao_Paulo'
            ],
            'description' => $event['description'],
            'location' => $event['location']
        ];
    }

    private function convertGoogleToIcs(array $event): array {
        return [
            'title' => $event['summary'] ?? '',
            'start_time' => $event['start']['dateTime'] ?? '',
            'end_time' => $event['end']['dateTime'] ?? '',
            'description' => $event['description'] ?? '',
            'location' => $event['location'] ?? ''
        ];
    }
}
