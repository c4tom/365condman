<?php
namespace CondMan\Domain\Interfaces;

interface CondominiumInterface {
    /**
     * Recupera o ID do condomínio
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Define o nome do condomínio
     * @param string $name
     */
    public function setName(string $name): void;

    /**
     * Recupera o nome do condomínio
     * @return string
     */
    public function getName(): string;

    /**
     * Define o CNPJ do condomínio
     * @param string $cnpj
     */
    public function setCnpj(string $cnpj): void;

    /**
     * Recupera o CNPJ do condomínio
     * @return string
     */
    public function getCnpj(): string;

    /**
     * Define o endereço do condomínio
     * @param string $address
     */
    public function setAddress(string $address): void;

    /**
     * Recupera o endereço do condomínio
     * @return string
     */
    public function getAddress(): string;

    /**
     * Define o total de unidades do condomínio
     * @param int $totalUnits
     */
    public function setTotalUnits(int $totalUnits): void;

    /**
     * Recupera o total de unidades do condomínio
     * @return int
     */
    public function getTotalUnits(): int;

    /**
     * Verifica se o condomínio está ativo
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Define o status de atividade do condomínio
     * @param bool $active
     */
    public function setActive(bool $active): void;

    /**
     * Valida os dados do condomínio
     * @return bool
     */
    public function validate(): bool;

    /**
     * Converte a entidade para array
     * @return array
     */
    public function toArray(): array;
}
