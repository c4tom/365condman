<?php
namespace CondMan\Domain\Repositories;

use CondMan\Domain\Entities\Occurrence;
use DateTime;

interface OccurrenceRepositoryInterface {
    /**
     * Salva uma nova ocorrência ou atualiza uma existente
     */
    public function save(Occurrence $occurrence): Occurrence;

    /**
     * Busca uma ocorrência por ID
     */
    public function findById(int $id): ?Occurrence;

    /**
     * Busca ocorrências por filtros
     * @param array $filters Filtros para busca de ocorrências
     * @return Occurrence[]
     */
    public function findByFilters(array $filters): array;

    /**
     * Conta ocorrências por filtros
     * @param array $filters Filtros para contagem
     */
    public function countByFilters(array $filters): int;

    /**
     * Atualiza o status de uma ocorrência
     */
    public function updateStatus(int $occurrenceId, string $status): bool;

    /**
     * Atribui uma ocorrência a um usuário
     */
    public function assignOccurrence(int $occurrenceId, int $assignedToId): bool;

    /**
     * Resolve uma ocorrência
     */
    public function resolveOccurrence(int $occurrenceId): bool;
}
