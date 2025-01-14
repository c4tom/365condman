<?php
namespace CondMan\Domain\Interfaces;

interface IntegrationInterface {
    /**
     * Realiza integração com sistema externo
     * @param array $data Dados para integração
     * @return bool Resultado da integração
     */
    public function integrate(array $data): bool;

    /**
     * Valida dados antes da integração
     * @param array $data Dados a serem validados
     * @return bool Status da validação
     */
    public function validate(array $data): bool;
}
