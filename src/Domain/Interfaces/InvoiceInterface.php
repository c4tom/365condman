<?php
namespace CondMan\Domain\Interfaces;

interface InvoiceInterface {
    /**
     * Recupera o ID da fatura
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Define o ID do condomínio
     * @param int $condominiumId
     */
    public function setCondominiumId(int $condominiumId): void;

    /**
     * Recupera o ID do condomínio
     * @return int
     */
    public function getCondominiumId(): int;

    /**
     * Define o ID da unidade
     * @param int $unitId
     */
    public function setUnitId(int $unitId): void;

    /**
     * Recupera o ID da unidade
     * @return int
     */
    public function getUnitId(): int;

    /**
     * Define o mês de referência
     * @param string $referenceMonth
     */
    public function setReferenceMonth(string $referenceMonth): void;

    /**
     * Recupera o mês de referência
     * @return string
     */
    public function getReferenceMonth(): string;

    /**
     * Define o ano de referência
     * @param string $referenceYear
     */
    public function setReferenceYear(string $referenceYear): void;

    /**
     * Recupera o ano de referência
     * @return string
     */
    public function getReferenceYear(): string;

    /**
     * Define a data de vencimento
     * @param \DateTime $dueDate
     */
    public function setDueDate(\DateTime $dueDate): void;

    /**
     * Recupera a data de vencimento
     * @return \DateTime
     */
    public function getDueDate(): \DateTime;

    /**
     * Define o valor total da fatura
     * @param float $totalAmount
     */
    public function setTotalAmount(float $totalAmount): void;

    /**
     * Recupera o valor total da fatura
     * @return float
     */
    public function getTotalAmount(): float;

    /**
     * Define o valor total pago
     * @param float $totalPaid
     */
    public function setTotalPaid(float $totalPaid): void;

    /**
     * Recupera o valor total pago
     * @return float
     */
    public function getTotalPaid(): float;

    /**
     * Define o status da fatura
     * @param string $status
     */
    public function setStatus(string $status): void;

    /**
     * Recupera o status da fatura
     * @return string
     */
    public function getStatus(): string;

    /**
     * Adiciona um item à fatura
     * @param array $item
     */
    public function addItem(array $item): void;

    /**
     * Recupera os itens da fatura
     * @return array
     */
    public function getItems(): array;

    /**
     * Valida os dados da fatura
     * @return bool
     */
    public function validate(): bool;

    /**
     * Converte a entidade para array
     * @return array
     */
    public function toArray(): array;
}
