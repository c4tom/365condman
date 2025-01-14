<?php
/**
 * Interface para gerenciamento de configurações do plugin
 *
 * @package CondMan\Domain\Interfaces
 */

namespace CondMan\Domain\Interfaces;

/**
 * Define métodos para gerenciamento de configurações
 */
interface ConfigurationManagerInterface {
    /**
     * Obtém um valor de configuração
     *
     * @param string $key Chave da configuração
     * @param mixed $default Valor padrão caso a configuração não exista
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Define um valor de configuração
     *
     * @param string $key Chave da configuração
     * @param mixed $value Valor da configuração
     * @return void
     */
    public function set(string $key, $value): void;

    /**
     * Verifica se uma configuração existe
     *
     * @param string $key Chave da configuração
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Carrega configurações de uma fonte específica
     *
     * @param string $source Fonte das configurações (env, file, database)
     * @return void
     */
    public function load(string $source): void;
}
