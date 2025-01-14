<?php
namespace CondMan\Domain\Entities;

use DateTime;
use JsonSerializable;

class Subscription implements JsonSerializable {
    private ?int $id;
    private int $userId;
    private string $type;
    private array $preferences;
    private bool $isActive;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private array $notificationChannels;

    public function __construct(
        ?int $id,
        int $userId,
        string $type = 'communication',
        array $preferences = [],
        bool $isActive = true,
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null,
        array $notificationChannels = ['email', 'dashboard']
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
        $this->preferences = $preferences;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt;
        $this->notificationChannels = $notificationChannels;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getType(): string { return $this->type; }
    public function getPreferences(): array { return $this->preferences; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }
    public function getNotificationChannels(): array { return $this->notificationChannels; }

    // Métodos de Manipulação
    public function updatePreferences(array $newPreferences): void {
        $this->preferences = array_merge($this->preferences, $newPreferences);
        $this->updatedAt = new DateTime();
    }

    public function toggleActive(bool $active = true): void {
        $this->isActive = $active;
        $this->updatedAt = new DateTime();
    }

    public function updateNotificationChannels(array $channels): void {
        $this->notificationChannels = $channels;
        $this->updatedAt = new DateTime();
    }

    public function hasPreference(string $key): bool {
        return isset($this->preferences[$key]);
    }

    public function getPreference(string $key, $default = null) {
        return $this->preferences[$key] ?? $default;
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'type' => $this->type,
            'preferences' => $this->preferences,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null,
            'notification_channels' => $this->notificationChannels
        ];
    }
}
