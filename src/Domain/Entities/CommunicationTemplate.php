<?php
namespace CondMan\Domain\Entities;

use DateTime;
use JsonSerializable;

class CommunicationTemplate implements JsonSerializable {
    private ?int $id;
    private int $authorId;
    private string $name;
    private string $title;
    private string $content;
    private string $type;
    private array $metadata;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private bool $isDefault;
    private array $placeholders;

    public function __construct(
        ?int $id,
        int $authorId,
        string $name,
        string $title,
        string $content,
        string $type = 'general',
        array $metadata = [],
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null,
        bool $isDefault = false,
        array $placeholders = []
    ) {
        $this->id = $id;
        $this->authorId = $authorId;
        $this->name = $name;
        $this->title = $title;
        $this->content = $content;
        $this->type = $type;
        $this->metadata = $metadata;
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt;
        $this->isDefault = $isDefault;
        $this->placeholders = $placeholders;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getAuthorId(): int { return $this->authorId; }
    public function getName(): string { return $this->name; }
    public function getTitle(): string { return $this->title; }
    public function getContent(): string { return $this->content; }
    public function getType(): string { return $this->type; }
    public function getMetadata(): array { return $this->metadata; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }
    public function isDefault(): bool { return $this->isDefault; }
    public function getPlaceholders(): array { return $this->placeholders; }

    // Métodos de Manipulação
    public function updateTemplate(
        string $name,
        string $title,
        string $content,
        string $type = 'general',
        array $metadata = [],
        array $placeholders = []
    ): void {
        $this->name = $name;
        $this->title = $title;
        $this->content = $content;
        $this->type = $type;
        $this->metadata = $metadata;
        $this->placeholders = $placeholders;
        $this->updatedAt = new DateTime();
    }

    public function setAsDefault(bool $isDefault = true): void {
        $this->isDefault = $isDefault;
        $this->updatedAt = new DateTime();
    }

    public function replacePlaceholders(array $values): string {
        $content = $this->content;
        foreach ($this->placeholders as $placeholder) {
            $key = "{{" . $placeholder . "}}";
            $value = $values[$placeholder] ?? '';
            $content = str_replace($key, $value, $content);
        }
        return $content;
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'author_id' => $this->authorId,
            'name' => $this->name,
            'title' => $this->title,
            'content' => $this->content,
            'type' => $this->type,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null,
            'is_default' => $this->isDefault,
            'placeholders' => $this->placeholders
        ];
    }
}
