<?php
namespace CondMan\Domain\Entities;

use DateTime;
use JsonSerializable;

class Occurrence implements JsonSerializable {
    private ?int $id;
    private int $condominiumId;
    private int $reporterId;
    private string $title;
    private string $description;
    private string $category;
    private string $status;
    private ?int $assignedToId;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private ?DateTime $resolvedAt;
    private array $metadata;

    public function __construct(
        ?int $id,
        int $condominiumId,
        int $reporterId,
        string $title,
        string $description,
        string $category,
        string $status = 'open',
        ?int $assignedToId = null,
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null,
        ?DateTime $resolvedAt = null,
        array $metadata = []
    ) {
        $this->id = $id;
        $this->condominiumId = $condominiumId;
        $this->reporterId = $reporterId;
        $this->title = $title;
        $this->description = $description;
        $this->category = $category;
        $this->status = $status;
        $this->assignedToId = $assignedToId;
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt;
        $this->resolvedAt = $resolvedAt;
        $this->metadata = $metadata;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getCondominiumId(): int { return $this->condominiumId; }
    public function getReporterId(): int { return $this->reporterId; }
    public function getTitle(): string { return $this->title; }
    public function getDescription(): string { return $this->description; }
    public function getCategory(): string { return $this->category; }
    public function getStatus(): string { return $this->status; }
    public function getAssignedToId(): ?int { return $this->assignedToId; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }
    public function getResolvedAt(): ?DateTime { return $this->resolvedAt; }
    public function getMetadata(): array { return $this->metadata; }

    // Setters
    public function setStatus(string $status): void { 
        $this->status = $status;
        $this->updatedAt = new DateTime();
    }

    public function setAssignedTo(?int $assignedToId): void {
        $this->assignedToId = $assignedToId;
        $this->updatedAt = new DateTime();
    }

    public function resolve(): void {
        $this->status = 'resolved';
        $this->resolvedAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'condominium_id' => $this->condominiumId,
            'reporter_id' => $this->reporterId,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'status' => $this->status,
            'assigned_to_id' => $this->assignedToId,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null,
            'resolved_at' => $this->resolvedAt ? $this->resolvedAt->format('Y-m-d H:i:s') : null,
            'metadata' => $this->metadata
        ];
    }
}
