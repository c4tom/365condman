<?php
namespace CondMan\Domain\Interfaces;

interface CommunicationInterface {
    /**
     * Recupera o ID da comunicação
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
     * @param int|null $unitId
     */
    public function setUnitId(?int $unitId): void;

    /**
     * Recupera o ID da unidade
     * @return int|null
     */
    public function getUnitId(): ?int;

    /**
     * Define o canal de comunicação
     * @param string $channel
     */
    public function setChannel(string $channel): void;

    /**
     * Recupera o canal de comunicação
     * @return string
     */
    public function getChannel(): string;

    /**
     * Define o destinatário
     * @param string $recipient
     */
    public function setRecipient(string $recipient): void;

    /**
     * Recupera o destinatário
     * @return string
     */
    public function getRecipient(): string;

    /**
     * Define o assunto da comunicação
     * @param string|null $subject
     */
    public function setSubject(?string $subject): void;

    /**
     * Recupera o assunto da comunicação
     * @return string|null
     */
    public function getSubject(): ?string;

    /**
     * Define o conteúdo da comunicação
     * @param string $content
     */
    public function setContent(string $content): void;

    /**
     * Recupera o conteúdo da comunicação
     * @return string
     */
    public function getContent(): string;

    /**
     * Define o status da comunicação
     * @param string $status
     */
    public function setStatus(string $status): void;

    /**
     * Recupera o status da comunicação
     * @return string
     */
    public function getStatus(): string;

    /**
     * Define dados adicionais da comunicação
     * @param array $additionalData
     */
    public function setAdditionalData(array $additionalData): void;

    /**
     * Recupera dados adicionais da comunicação
     * @return array
     */
    public function getAdditionalData(): array;

    /**
     * Valida os dados da comunicação
     * @return bool
     */
    public function validate(): bool;

    /**
     * Converte a entidade para array
     * @return array
     */
    public function toArray(): array;
}
