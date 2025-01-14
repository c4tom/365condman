<?php
namespace CondMan\Domain\Serializers;

use CondMan\Domain\Interfaces\InvoiceInterface;
use CondMan\Domain\Transformers\InvoiceTransformer;
use JsonSerializable;

class InvoiceSerializer implements JsonSerializable {
    private InvoiceInterface $invoice;
    private InvoiceTransformer $transformer;

    public function __construct(InvoiceInterface $invoice) {
        $this->invoice = $invoice;
        $this->transformer = new InvoiceTransformer($invoice);
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->invoice->getId(),
            'condominium_id' => $this->invoice->getCondominiumId(),
            'unit_id' => $this->invoice->getUnitId(),
            'reference' => $this->transformer->generateReference(),
            'total_amount' => $this->transformer->formatTotalAmount(),
            'total_paid' => $this->transformer->formatTotalPaid(),
            'remaining_amount' => $this->transformer->calculateRemainingAmount(),
            'status' => $this->transformer->translateStatus(),
            'is_overdue' => $this->transformer->isOverdue(),
            'overdue_days' => $this->transformer->calculateOverdueDays(),
            'due_date' => $this->invoice->getDueDate()->format('Y-m-d'),
            'payment_date' => $this->invoice->getPaymentDate() 
                ? $this->invoice->getPaymentDate()->format('Y-m-d') 
                : null,
            'created_at' => $this->invoice->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $this->invoice->getUpdatedAt()->format('Y-m-d H:i:s')
        ];
    }

    public function toArray(): array {
        return $this->jsonSerialize();
    }

    public function toXml(): string {
        $xml = new \SimpleXMLElement('<invoice/>');
        
        foreach ($this->jsonSerialize() as $key => $value) {
            $xml->addChild($key, is_scalar($value) ? $value : json_encode($value));
        }
        
        return $xml->asXML();
    }

    public function toYaml(): string {
        return \Symfony\Component\Yaml\Yaml::dump($this->jsonSerialize(), 4, 2);
    }
}
