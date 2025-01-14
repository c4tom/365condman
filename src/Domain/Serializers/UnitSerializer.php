<?php
namespace CondMan\Domain\Serializers;

use CondMan\Domain\Interfaces\UnitInterface;
use CondMan\Domain\Transformers\UnitTransformer;
use JsonSerializable;

class UnitSerializer implements JsonSerializable {
    private UnitInterface $unit;
    private UnitTransformer $transformer;

    public function __construct(UnitInterface $unit) {
        $this->unit = $unit;
        $this->transformer = new UnitTransformer($unit);
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->unit->getId(),
            'condominium_id' => $this->unit->getCondominiumId(),
            'unique_identifier' => $this->transformer->generateUniqueIdentifier(),
            'block' => $this->unit->getBlock(),
            'number' => $this->unit->getNumber(),
            'type' => $this->transformer->translateUnitType(),
            'area' => $this->transformer->formatArea(),
            'fraction' => $this->transformer->calculateFractionPercentage(),
            'status' => $this->unit->getStatus(),
            'full_description' => $this->transformer->generateFullDescription(),
            'created_at' => $this->unit->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $this->unit->getUpdatedAt()->format('Y-m-d H:i:s')
        ];
    }

    public function toArray(): array {
        return $this->jsonSerialize();
    }

    public function toXml(): string {
        $xml = new \SimpleXMLElement('<unit/>');
        
        foreach ($this->jsonSerialize() as $key => $value) {
            $xml->addChild($key, is_scalar($value) ? $value : json_encode($value));
        }
        
        return $xml->asXML();
    }

    public function toYaml(): string {
        return \Symfony\Component\Yaml\Yaml::dump($this->jsonSerialize(), 4, 2);
    }
}
