<?php
namespace CondMan\Domain\Transformers;

use CondMan\Domain\Interfaces\CondominiumInterface;

class CondominiumTransformer {
    private CondominiumInterface $condominium;

    public function __construct(CondominiumInterface $condominium) {
        $this->condominium = $condominium;
    }

    /**
     * Formata o CNPJ
     * @return string CNPJ formatado
     */
    public function formatCnpj(): string {
        $cnpj = $this->condominium->getCnpj();
        return preg_replace(
            '/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', 
            '$1.$2.$3/$4-$5', 
            $cnpj
        );
    }

    /**
     * Normaliza o nome do condomínio
     * @return string Nome normalizado
     */
    public function normalizeName(): string {
        $name = $this->condominium->getName();
        $name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
        $name = preg_replace('/\s+/', ' ', $name);
        return trim($name);
    }

    /**
     * Gera um slug para o condomínio
     * @return string Slug gerado
     */
    public function generateSlug(): string {
        $name = $this->normalizeName();
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        $slug = preg_replace('/[^a-zA-Z0-9\s]/', '', $slug);
        $slug = mb_strtolower($slug);
        $slug = preg_replace('/\s+/', '-', $slug);
        return $slug;
    }

    /**
     * Máscara de endereço
     * @return string Endereço mascarado
     */
    public function maskAddress(): string {
        $address = $this->condominium->getAddress();
        $parts = explode(',', $address);
        
        if (count($parts) >= 2) {
            $street = trim($parts[0]);
            $number = trim($parts[1]);
            
            return sprintf(
                "%s, %s", 
                $street, 
                preg_replace('/\d+/', '***', $number)
            );
        }
        
        return $address;
    }

    /**
     * Calcula o percentual de unidades ocupadas
     * @param int $occupiedUnits Número de unidades ocupadas
     * @return float Percentual de ocupação
     */
    public function calculateOccupancyRate(int $occupiedUnits): float {
        $totalUnits = $this->condominium->getTotalUnits();
        
        if ($totalUnits == 0) {
            return 0.0;
        }
        
        return round(($occupiedUnits / $totalUnits) * 100, 2);
    }
}
