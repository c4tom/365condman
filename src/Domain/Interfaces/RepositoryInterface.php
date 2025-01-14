<?php
namespace CondMan\Domain\Interfaces;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

interface RepositoryInterface {
    /**
     * Encontra um registro pelo ID
     * @param int $id Identificador do registro
     * @return array|null Dados do registro ou null se não encontrado
     */
    public function findById(int $id): ?array;

    /**
     * Encontra todos os registros
     * @param array $criteria Critérios de filtro
     * @param array $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de registros
     * @param int|null $offset Deslocamento inicial
     * @return array Lista de registros
     */
    public function findAll(array $criteria = [], array $orderBy = [], ?int $limit = null, ?int $offset = null): array;

    /**
     * Conta o número de registros
     * @param array $criteria Critérios de contagem
     * @return int Número de registros
     */
    public function count(array $criteria = []): int;

    /**
     * Insere um novo registro
     * @param array $data Dados do registro a ser inserido
     * @return int ID do registro inserido
     */
    public function insert(array $data): int;

    /**
     * Atualiza um registro existente
     * @param int $id Identificador do registro
     * @param array $data Dados a serem atualizados
     * @return bool Indica se a atualização foi bem-sucedida
     */
    public function update(int $id, array $data): bool;

    /**
     * Remove um registro
     * @param int $id Identificador do registro
     * @return bool Indica se a remoção foi bem-sucedida
     */
    public function delete(int $id): bool;

    /**
     * Inicia uma transação
     */
    public function beginTransaction(): void;

    /**
     * Confirma a transação atual
     */
    public function commit(): void;

    /**
     * Reverte a transação atual
     */
    public function rollback(): void;
}
