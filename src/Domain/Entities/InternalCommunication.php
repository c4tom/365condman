<?php
namespace CondMan\Domain\Entities;

use DateTime;
use JsonSerializable;

class InternalCommunication implements JsonSerializable {
    private ?int $id;
    private int $authorId;
    private string $title;
    private string $content;
    private string $type;
    private array $recipients;
    private ?DateTime $scheduledFor;
    private ?DateTime $sentAt;
    private string $status;
    private array $readConfirmations;
    private array $metadata;

    public function __construct(
        ?int $id,
        int $authorId,
        string $title,
        string $content,
        string $type = 'general',
        array $recipients = [],
        ?DateTime $scheduledFor = null,
        ?DateTime $sentAt = null,
        string $status = 'draft',
        array $readConfirmations = [],
        array $metadata = []
    ) {
        $this->id = $id;
        $this->authorId = $authorId;
        $this->title = $title;
        $this->content = $content;
        $this->type = $type;
        $this->recipients = $recipients;
        $this->scheduledFor = $scheduledFor;
        $this->sentAt = $sentAt;
        $this->status = $status;
        $this->readConfirmations = $readConfirmations;
        $this->metadata = $metadata;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getAuthorId(): int { return $this->authorId; }
    public function getTitle(): string { return $this->title; }
    public function getContent(): string { return $this->content; }
    public function getType(): string { return $this->type; }
    public function getRecipients(): array { return $this->recipients; }
    public function getScheduledFor(): ?DateTime { return $this->scheduledFor; }
    public function getSentAt(): ?DateTime { return $this->sentAt; }
    public function getStatus(): string { return $this->status; }
    public function getReadConfirmations(): array { return $this->readConfirmations; }
    public function getMetadata(): array { return $this->metadata; }

    // Métodos de Ação
    public function schedule(DateTime $scheduledFor): void {
        $this->scheduledFor = $scheduledFor;
        $this->status = 'scheduled';
    }

    public function send(): void {
        $this->sentAt = new DateTime();
        $this->status = 'sent';
    }

    public function addRecipient(int $recipientId): void {
        if (!in_array($recipientId, $this->recipients)) {
            $this->recipients[] = $recipientId;
        }
    }

    public function markAsRead(int $recipientId): void {
        if (!isset($this->readConfirmations[$recipientId])) {
            $this->readConfirmations[$recipientId] = new DateTime();
        }
    }

    public function getReadRate(): float {
        $totalRecipients = count($this->recipients);
        $readCount = count($this->readConfirmations);
        return $totalRecipients > 0 ? ($readCount / $totalRecipients) * 100 : 0;
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'author_id' => $this->authorId,
            'title' => $this->title,
            'content' => $this->content,
            'type' => $this->type,
            'recipients' => $this->recipients,
            'scheduled_for' => $this->scheduledFor ? $this->scheduledFor->format('Y-m-d H:i:s') : null,
            'sent_at' => $this->sentAt ? $this->sentAt->format('Y-m-d H:i:s') : null,
            'status' => $this->status,
            'read_confirmations' => array_map(
                fn($timestamp) => $timestamp->format('Y-m-d H:i:s'), 
                $this->readConfirmations
            ),
            'metadata' => $this->metadata,
            'read_rate' => $this->getReadRate()
        ];
    }
}
