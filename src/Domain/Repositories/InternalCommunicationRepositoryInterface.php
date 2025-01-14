<?php
namespace CondMan\Domain\Repositories;

use CondMan\Domain\Entities\InternalCommunication;
use DateTime;

interface InternalCommunicationRepositoryInterface {
    /**
     * Salva um novo comunicado ou atualiza um existente
     */
    public function save(InternalCommunication $communication): InternalCommunication;

    /**
     * Busca um comunicado por ID
     */
    public function findById(int $id): ?InternalCommunication;

    /**
     * Busca comunicados por filtros
     * @param array $filters Filtros para busca de comunicados
     * @return InternalCommunication[]
     */
    public function findByFilters(array $filters): array;

    /**
     * Conta comunicados por filtros
     * @param array $filters Filtros para contagem
     */
    public function countByFilters(array $filters): int;

    /**
     * Agenda um comunicado para envio futuro
     */
    public function schedule(InternalCommunication $communication): bool;

    /**
     * Marca comunicado como enviado
     */
    public function markAsSent(int $communicationId): bool;

    /**
     * Registra confirmação de leitura
     */
    public function registerReadConfirmation(int $communicationId, int $recipientId): bool;

    /**
     * Recupera estatísticas de leitura de um comunicado
     */
    public function getReadStatistics(int $communicationId): array;
}
