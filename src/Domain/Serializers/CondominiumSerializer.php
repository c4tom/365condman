<?php
namespace CondMan\Domain\Serializers;

use CondMan\Domain\Interfaces\CondominiumInterface;
use CondMan\Domain\Transformers\CondominiumTransformer;
use JsonSerializable;

class CondominiumSerializer implements JsonSerializable {
    private CondominiumInterface $condominium;
    private CondominiumTransformer $transformer;

    public function __construct(CondominiumInterface $condominium) {
        $this->condominium = $condominium;
        $this->transformer = new CondominiumTransformer($condominium);
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->condominium->getId(),
            'name' => $this->transformer->normalizeName(),
            'cnpj' => $this->transformer->formatCnpj(),
            'address' => $this->transformer->maskAddress(),
            'total_units' => $this->condominium->getTotalUnits(),
            'occupancy_rate' => $this->transformer->calculateOccupancyRate(
                $this->condominium->getOccupiedUnits()
            ),
            'created_at' => $this->condominium->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $this->condominium->getUpdatedAt()->format('Y-m-d H:i:s')
        ];
    }

    public function toArray(): array {
        return $this->jsonSerialize();
    }

    public function toXml(): string {
        $xml = new \SimpleXMLElement('<condominium/>');
        
        foreach ($this->jsonSerialize() as $key => $value) {
            $xml->addChild($key, is_scalar($value) ? $value : json_encode($value));
        }
        
        return $xml->asXML();
    }

    public function toYaml(): string {
        return \Symfony\Component\Yaml\Yaml::dump($this->jsonSerialize(), 4, 2);
    }
}
