<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\ConfigurationInterface;
use CondMan\Domain\Entities\Unit;

class UnitService {
    private $config;
    private $wpdb;

    public function __construct(
        ConfigurationInterface $config, 
        \wpdb $wpdb
    ) {
        $this->config = $config;
        $this->wpdb = $wpdb;
    }

    /**
     * Cria uma nova unidade condominial
     * 
     * @param array $data Dados da unidade
     * @return Unit Unidade criada
     * @throws \InvalidArgumentException Se dados inválidos
     */
    public function create(array $data): Unit {
        $this->validate($data);
        
        $unitData = [
            'condominium_id' => $data['condominium_id'],
            'block' => $data['block'] ?? null,
            'number' => $data['number'],
            'type' => $data['type'] ?? 'residential',
            'area' => $data['area'] ?? null,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $result = $this->wpdb->insert(
            $this->wpdb->prefix . '365condman_units', 
            $unitData
        );

        if ($result === false) {
            throw new \RuntimeException('Falha ao criar unidade: ' . $this->wpdb->last_error);
        }

        $unitId = $this->wpdb->insert_id;
        return $this->findById($unitId);
    }

    /**
     * Encontra unidade por ID
     * 
     * @param int $id Identificador da unidade
     * @return Unit|null Unidade encontrada
     */
    public function findById(int $id): ?Unit {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}365condman_units WHERE id = %d", 
                $id
            ), 
            ARRAY_A
        );

        return $result ? new Unit($result) : null;
    }

    /**
     * Valida dados da unidade
     * 
     * @param array $data Dados a serem validados
     * @throws \InvalidArgumentException Se dados inválidos
     */
    private function validate(array $data): void {
        if (empty($data['condominium_id'])) {
            throw new \InvalidArgumentException('ID do condomínio é obrigatório');
        }

        if (empty($data['number'])) {
            throw new \InvalidArgumentException('Número da unidade é obrigatório');
        }

        // Validações adicionais podem ser implementadas aqui
    }
}
