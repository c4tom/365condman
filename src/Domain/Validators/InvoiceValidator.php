<?php
namespace CondMan\Domain\Validators;

use CondMan\Domain\Interfaces\InvoiceInterface;
use DateTime;

class InvoiceValidator {
    private InvoiceInterface $invoice;

    public function __construct(InvoiceInterface $invoice) {
        $this->invoice = $invoice;
    }

    public function validate(): bool {
        return $this->validateCondominiumId() && 
               $this->validateUnitId() && 
               $this->validateReferenceMonth() && 
               $this->validateReferenceYear() && 
               $this->validateDueDate() && 
               $this->validateTotalAmount() && 
               $this->validateTotalPaid() && 
               $this->validateStatus() && 
               $this->validateItems();
    }

    private function validateCondominiumId(): bool {
        return $this->invoice->getCondominiumId() > 0;
    }

    private function validateUnitId(): bool {
        return $this->invoice->getUnitId() > 0;
    }

    private function validateReferenceMonth(): bool {
        $month = $this->invoice->getReferenceMonth();
        return preg_match('/^(0[1-9]|1[0-2])$/', $month);
    }

    private function validateReferenceYear(): bool {
        $year = $this->invoice->getReferenceYear();
        $currentYear = date('Y');
        return preg_match('/^\d{4}$/', $year) && 
               $year >= 2020 && 
               $year <= ($currentYear + 1);
    }

    private function validateDueDate(): bool {
        $dueDate = $this->invoice->getDueDate();
        $now = new DateTime();
        return $dueDate instanceof DateTime && 
               $dueDate >= $now;
    }

    private function validateTotalAmount(): bool {
        $totalAmount = $this->invoice->getTotalAmount();
        return $totalAmount >= 0 && $totalAmount <= 1000000;
    }

    private function validateTotalPaid(): bool {
        $totalPaid = $this->invoice->getTotalPaid();
        $totalAmount = $this->invoice->getTotalAmount();
        return $totalPaid >= 0 && $totalPaid <= $totalAmount;
    }

    private function validateStatus(): bool {
        $validStatuses = ['pending', 'paid', 'overdue', 'partial', 'canceled'];
        return in_array($this->invoice->getStatus(), $validStatuses);
    }

    private function validateItems(): bool {
        $items = $this->invoice->getItems();
        
        if (empty($items)) {
            return false;
        }

        foreach ($items as $item) {
            if (!$this->validateItem($item)) {
                return false;
            }
        }

        return true;
    }

    private function validateItem(array $item): bool {
        $requiredKeys = ['description', 'amount', 'quantity'];
        
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $item)) {
                return false;
            }
        }

        return !empty($item['description']) && 
               $item['amount'] > 0 && 
               $item['quantity'] > 0;
    }
}
