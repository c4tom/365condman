<?php
namespace CondMan\Domain\Interfaces;

interface PersistenceServiceInterface {
    /**
     * Salva uma entidade
     * @param array $data Dados da entidade
     * @return int ID da entidade salva
     * @throws \Exception Erro durante a persistência
     */
    public function save(array $data): int;

    /**
     * Atualiza uma entidade existente
     * @param int $id ID da entidade
     * @param array $data Dados atualizados
     * @return bool Indica se a atualização foi bem-sucedida
     * @throws \Exception Erro durante a atualização
     */
    public function update(int $id, array $data): bool;

    /**
     * Remove uma entidade
     * @param int $id ID da entidade
     * @return bool Indica se a remoção foi bem-sucedida
     * @throws \Exception Erro durante a remoção
     */
    public function delete(int $id): bool;

    /**
     * Encontra uma entidade pelo ID
     * @param int $id ID da entidade
     * @return array|null Dados da entidade
     * @throws \Exception Erro durante a busca
     */
    public function findById(int $id): ?array;

    /**
     * Lista todas as entidades
     * @param array $criteria Critérios de filtro
     * @param array $orderBy Critérios de ordenação
     * @param int|null $limit Limite de registros
     * @param int|null $offset Deslocamento inicial
     * @return array Lista de entidades
     * @throws \Exception Erro durante a listagem
     */
    public function findAll(array $criteria = [], array $orderBy = [], ?int $limit = null, ?int $offset = null): array;

    /**
     * Conta o número de entidades
     * @param array $criteria Critérios de contagem
     * @return int Número de entidades
     * @throws \Exception Erro durante a contagem
     */
    public function count(array $criteria = []): int;
}
