<?php
namespace CondMan\Domain\Serializers;

use CondMan\Domain\Interfaces\CommunicationInterface;
use CondMan\Domain\Transformers\CommunicationTransformer;
use JsonSerializable;

class CommunicationSerializer implements JsonSerializable {
    private CommunicationInterface $communication;
    private CommunicationTransformer $transformer;

    public function __construct(CommunicationInterface $communication) {
        $this->communication = $communication;
        $this->transformer = new CommunicationTransformer($communication);
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->communication->getId(),
            'condominium_id' => $this->communication->getCondominiumId(),
            'unit_id' => $this->communication->getUnitId(),
            'channel' => $this->transformer->translateChannel(),
            'recipient' => $this->transformer->maskRecipient(),
            'subject' => $this->communication->getSubject(),
            'content' => $this->communication->getContent(),
            'status' => $this->transformer->translateStatus(),
            'additional_data' => $this->communication->getAdditionalData(),
            'sent_at' => $this->communication->getSentAt() 
                ? $this->communication->getSentAt()->format('Y-m-d H:i:s') 
                : null,
            'read_at' => $this->communication->getReadAt() 
                ? $this->communication->getReadAt()->format('Y-m-d H:i:s') 
                : null,
            'created_at' => $this->communication->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $this->communication->getUpdatedAt()->format('Y-m-d H:i:s')
        ];
    }

    public function toArray(): array {
        return $this->jsonSerialize();
    }

    public function toXml(): string {
        $xml = new \SimpleXMLElement('<communication/>');
        
        foreach ($this->jsonSerialize() as $key => $value) {
            $xml->addChild($key, is_scalar($value) ? $value : json_encode($value));
        }
        
        return $xml->asXML();
    }

    public function toYaml(): string {
        return \Symfony\Component\Yaml\Yaml::dump($this->jsonSerialize(), 4, 2);
    }
}
