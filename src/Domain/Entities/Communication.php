<?php
namespace CondMan\Domain\Entities;

use CondMan\Domain\Interfaces\CommunicationInterface;
use CondMan\Domain\Validators\CommunicationValidator;

class Communication implements CommunicationInterface {
    private ?int $id = null;
    private int $condominiumId;
    private ?int $unitId = null;
    private string $channel = 'email';
    private string $recipient = '';
    private ?string $subject = null;
    private string $content = '';
    private string $status = 'pending';
    private array $additionalData = [];
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->condominiumId = $data['condominium_id'] ?? 0;
        $this->unitId = $data['unit_id'] ?? null;
        $this->channel = $data['channel'] ?? 'email';
        $this->recipient = $data['recipient'] ?? '';
        $this->subject = $data['subject'] ?? null;
        $this->content = $data['content'] ?? '';
        $this->status = $data['status'] ?? 'pending';
        $this->additionalData = $data['additional_data'] ?? [];
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function setCondominiumId(int $condominiumId): void {
        $this->condominiumId = $condominiumId;
    }

    public function getCondominiumId(): int {
        return $this->condominiumId;
    }

    public function setUnitId(?int $unitId): void {
        $this->unitId = $unitId;
    }

    public function getUnitId(): ?int {
        return $this->unitId;
    }

    public function setChannel(string $channel): void {
        $validChannels = ['email', 'sms', 'whatsapp', 'push_notification'];
        $this->channel = in_array($channel, $validChannels) ? $channel : 'email';
    }

    public function getChannel(): string {
        return $this->channel;
    }

    public function setRecipient(string $recipient): void {
        $this->recipient = trim($recipient);
    }

    public function getRecipient(): string {
        return $this->recipient;
    }

    public function setSubject(?string $subject): void {
        $this->subject = $subject ? trim($subject) : null;
    }

    public function getSubject(): ?string {
        return $this->subject;
    }

    public function setContent(string $content): void {
        $this->content = trim($content);
    }

    public function getContent(): string {
        return $this->content;
    }

    public function setStatus(string $status): void {
        $validStatuses = ['pending', 'sent', 'failed', 'read'];
        $this->status = in_array($status, $validStatuses) ? $status : 'pending';
        $this->updatedAt = current_time('mysql');
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function setAdditionalData(array $additionalData): void {
        $this->additionalData = $additionalData;
    }

    public function getAdditionalData(): array {
        return $this->additionalData;
    }

    public function validate(): bool {
        $validator = new CommunicationValidator($this);
        return $validator->validate();
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'condominium_id' => $this->condominiumId,
            'unit_id' => $this->unitId,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'subject' => $this->subject,
            'content' => $this->content,
            'status' => $this->status,
            'additional_data' => $this->additionalData,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}
