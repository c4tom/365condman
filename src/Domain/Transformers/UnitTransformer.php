<?php
namespace CondMan\Domain\Transformers;

use CondMan\Domain\Interfaces\UnitInterface;

class UnitTransformer {
    private UnitInterface $unit;

    public function __construct(UnitInterface $unit) {
        $this->unit = $unit;
    }

    /**
     * Gera identificador único para a unidade
     * @return string Identificador único
     */
    public function generateUniqueIdentifier(): string {
        $block = $this->unit->getBlock() ?? '';
        $number = $this->unit->getNumber();
        
        return sprintf(
            "%s-%s", 
            strtoupper(substr($block, 0, 3)), 
            $number
        );
    }

    /**
     * Traduz o tipo de unidade
     * @return string Tipo traduzido
     */
    public function translateUnitType(): string {
        $translations = [
            'residential' => 'Residencial',
            'commercial' => 'Comercial',
            'parking' => 'Estacionamento'
        ];
        
        return $translations[$this->unit->getType()] ?? $this->unit->getType();
    }

    /**
     * Calcula a fração ideal percentual
     * @return float Fração ideal em percentual
     */
    public function calculateFractionPercentage(): float {
        $fraction = $this->unit->getFraction();
        
        if ($fraction === null) {
            return 0.0;
        }
        
        return round($fraction * 100, 2);
    }

    /**
     * Formata a área da unidade
     * @return string Área formatada
     */
    public function formatArea(): string {
        $area = $this->unit->getArea();
        
        if ($area === null) {
            return 'Não informado';
        }
        
        return sprintf("%.2f m²", $area);
    }

    /**
     * Gera descrição completa da unidade
     * @return string Descrição da unidade
     */
    public function generateFullDescription(): string {
        $block = $this->unit->getBlock() ? "Bloco {$this->unit->getBlock()}, " : '';
        $number = $this->unit->getNumber();
        $type = $this->translateUnitType();
        
        return sprintf(
            "%sUnidade %s (%s)", 
            $block, 
            $number, 
            $type
        );
    }

    /**
     * Verifica se a unidade é elegível para alguma ação
     * @param string $action Ação a ser verificada
     * @return bool Elegibilidade para a ação
     */
    public function isEligibleFor(string $action): bool {
        $status = $this->unit->getStatus();
        
        $eligibility = [
            'maintenance' => ['inactive', 'maintenance'],
            'rent' => ['active'],
            'sale' => ['active']
        ];
        
        return isset($eligibility[$action]) && 
               in_array($status, $eligibility[$action]);
    }
}
