<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\ConfigurationInterface;

class CondominiumService {
    private $config;

    public function __construct(ConfigurationInterface $config) {
        $this->config = $config;
    }

    /**
     * Cria novo condomínio
     * @param array $data Dados do condomínio
     * @return int ID do condomínio criado
     * @throws \InvalidArgumentException Se dados inválidos
     */
    public function create(array $data): int {
        $this->validate($data);
        
        // Lógica de criação de condomínio
        $condominiumId = $this->persist($data);
        
        return $condominiumId;
    }

    /**
     * Valida dados do condomínio
     * @param array $data Dados a serem validados
     * @throws \InvalidArgumentException Se dados inválidos
     */
    private function validate(array $data): void {
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Nome do condomínio é obrigatório');
        }
        
        // Validações adicionais
    }

    /**
     * Persiste dados do condomínio
     * @param array $data Dados do condomínio
     * @return int ID do condomínio
     */
    private function persist(array $data): int {
        // Implementação de persistência
        // Pode ser substituída por injeção de dependência
        return 1; // ID mockado
    }
}
