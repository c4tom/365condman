<?php
namespace CondMan\Domain\Interfaces;

interface ConfigurationInterface {
    /**
     * Obtém configuração específica
     * @param string $key Chave de configuração
     * @param mixed $default Valor padrão
     * @return mixed Valor da configuração
     */
    public function get(string $key, $default = null);

    /**
     * Define configuração
     * @param string $key Chave de configuração
     * @param mixed $value Valor da configuração
     */
    public function set(string $key, $value): void;

    /**
     * Verifica se configuração existe
     * @param string $key Chave de configuração
     * @return bool Status da existência
     */
    public function has(string $key): bool;
}
