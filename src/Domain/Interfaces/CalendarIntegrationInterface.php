<?php
namespace CondMan\Domain\Interfaces;

use DateTime;

interface CalendarIntegrationInterface {
    /**
     * Exporta eventos para um formato de calendário específico
     * @param array $events Eventos a serem exportados
     * @param string $format Formato de exportação (ics, google, etc)
     * @return string Conteúdo exportado
     */
    public function exportEvents(array $events, string $format = 'ics'): string;

    /**
     * Importa eventos de um arquivo ou fonte de calendário
     * @param string $source Fonte dos eventos (caminho do arquivo, URL)
     * @param array $filters Filtros para importação
     * @return array Eventos importados
     */
    public function importEvents(string $source, array $filters = []): array;

    /**
     * Converte eventos entre diferentes formatos de calendário
     * @param array $events Eventos originais
     * @param string $sourceFormat Formato de origem
     * @param string $targetFormat Formato de destino
     * @return array Eventos convertidos
     */
    public function convertEvents(
        array $events, 
        string $sourceFormat, 
        string $targetFormat
    ): array;

    /**
     * Valida um arquivo ou fonte de calendário
     * @param string $source Fonte do calendário
     * @return bool Válido ou não
     */
    public function validateCalendarSource(string $source): bool;

    /**
     * Gera um identificador único para eventos
     * @param array $eventData Dados do evento
     * @return string Identificador único
     */
    public function generateEventUid(array $eventData): string;
}
