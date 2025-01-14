<?php
namespace CondMan\Domain\Interfaces;

interface CacheInterface {
    /**
     * Define um valor no cache
     * @param string $key Chave de identificação
     * @param mixed $value Valor a ser armazenado
     * @param int $ttl Tempo de vida em segundos
     * @return bool Indica se o armazenamento foi bem-sucedido
     */
    public function set(string $key, $value, int $ttl = 3600): bool;

    /**
     * Obtém um valor do cache
     * @param string $key Chave de identificação
     * @return mixed Valor armazenado ou null se não existir
     */
    public function get(string $key);

    /**
     * Remove um valor do cache
     * @param string $key Chave de identificação
     * @return bool Indica se a remoção foi bem-sucedida
     */
    public function delete(string $key): bool;

    /**
     * Limpa todo o cache
     * @return bool Indica se a limpeza foi bem-sucedida
     */
    public function clear(): bool;

    /**
     * Verifica se uma chave existe no cache
     * @param string $key Chave de identificação
     * @return bool Indica se a chave existe
     */
    public function has(string $key): bool;

    /**
     * Incrementa o valor de uma chave numérica
     * @param string $key Chave de identificação
     * @param int $step Valor de incremento
     * @return int|false Novo valor ou false em caso de erro
     */
    public function increment(string $key, int $step = 1);

    /**
     * Decrementa o valor de uma chave numérica
     * @param string $key Chave de identificação
     * @param int $step Valor de decremento
     * @return int|false Novo valor ou false em caso de erro
     */
    public function decrement(string $key, int $step = 1);
}
