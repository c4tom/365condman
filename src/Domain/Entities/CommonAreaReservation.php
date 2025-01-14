<?php
namespace CondMan\Domain\Entities;

use DateTime;
use JsonSerializable;

class CommonAreaReservation implements JsonSerializable {
    private ?int $id;
    private int $commonAreaId;
    private int $userId;
    private DateTime $startTime;
    private DateTime $endTime;
    private string $status;
    private ?float $totalCost;
    private array $additionalDetails;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;

    public function __construct(
        ?int $id,
        int $commonAreaId,
        int $userId,
        DateTime $startTime,
        DateTime $endTime,
        string $status = 'pending',
        ?float $totalCost = null,
        array $additionalDetails = [],
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->commonAreaId = $commonAreaId;
        $this->userId = $userId;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->status = $status;
        $this->totalCost = $totalCost;
        $this->additionalDetails = $additionalDetails;
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getCommonAreaId(): int { return $this->commonAreaId; }
    public function getUserId(): int { return $this->userId; }
    public function getStartTime(): DateTime { return $this->startTime; }
    public function getEndTime(): DateTime { return $this->endTime; }
    public function getStatus(): string { return $this->status; }
    public function getTotalCost(): ?float { return $this->totalCost; }
    public function getAdditionalDetails(): array { return $this->additionalDetails; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }

    // Métodos de Manipulação
    public function updateReservation(
        DateTime $startTime,
        DateTime $endTime,
        string $status = 'pending',
        ?float $totalCost = null,
        array $additionalDetails = []
    ): void {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->status = $status;
        $this->totalCost = $totalCost;
        $this->additionalDetails = $additionalDetails;
        $this->updatedAt = new DateTime();
    }

    public function updateStatus(string $status): void {
        $this->status = $status;
        $this->updatedAt = new DateTime();
    }

    public function addAdditionalDetail(string $key, $value): void {
        $this->additionalDetails[$key] = $value;
        $this->updatedAt = new DateTime();
    }

    public function removeAdditionalDetail(string $key): void {
        unset($this->additionalDetails[$key]);
        $this->updatedAt = new DateTime();
    }

    public function calculateTotalCost(float $hourlyRate): void {
        $hours = $this->startTime->diff($this->endTime)->h;
        $this->totalCost = $hourlyRate * $hours;
        $this->updatedAt = new DateTime();
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'common_area_id' => $this->commonAreaId,
            'user_id' => $this->userId,
            'start_time' => $this->startTime->format('Y-m-d H:i:s'),
            'end_time' => $this->endTime->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'total_cost' => $this->totalCost,
            'additional_details' => $this->additionalDetails,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null
        ];
    }
}
