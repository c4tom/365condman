<?php
namespace CondMan\Domain\Interfaces;

interface UnitInterface {
    /**
     * Recupera o ID da unidade
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
     * Define o bloco da unidade
     * @param string|null $block
     */
    public function setBlock(?string $block): void;

    /**
     * Recupera o bloco da unidade
     * @return string|null
     */
    public function getBlock(): ?string;

    /**
     * Define o número da unidade
     * @param string $number
     */
    public function setNumber(string $number): void;

    /**
     * Recupera o número da unidade
     * @return string
     */
    public function getNumber(): string;

    /**
     * Define o tipo da unidade
     * @param string $type
     */
    public function setType(string $type): void;

    /**
     * Recupera o tipo da unidade
     * @return string
     */
    public function getType(): string;

    /**
     * Define a área da unidade
     * @param float|null $area
     */
    public function setArea(?float $area): void;

    /**
     * Recupera a área da unidade
     * @return float|null
     */
    public function getArea(): ?float;

    /**
     * Define a fração ideal da unidade
     * @param float|null $fraction
     */
    public function setFraction(?float $fraction): void;

    /**
     * Recupera a fração ideal da unidade
     * @return float|null
     */
    public function getFraction(): ?float;

    /**
     * Define o status da unidade
     * @param string $status
     */
    public function setStatus(string $status): void;

    /**
     * Recupera o status da unidade
     * @return string
     */
    public function getStatus(): string;

    /**
     * Valida os dados da unidade
     * @return bool
     */
    public function validate(): bool;

    /**
     * Converte a entidade para array
     * @return array
     */
    public function toArray(): array;
}
