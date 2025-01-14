<?php
namespace CondMan\Domain\Entities;

use DateTime;
use JsonSerializable;

class CommonArea implements JsonSerializable {
    private ?int $id;
    private string $name;
    private string $description;
    private string $type;
    private int $capacity;
    private float $area;
    private array $amenities;
    private bool $isReservable;
    private ?float $hourlyRate;
    private array $operatingHours;
    private array $restrictions;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;

    public function __construct(
        ?int $id,
        string $name,
        string $description,
        string $type = 'multipurpose',
        int $capacity = 0,
        float $area = 0.0,
        array $amenities = [],
        bool $isReservable = false,
        ?float $hourlyRate = null,
        array $operatingHours = [],
        array $restrictions = [],
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
        $this->capacity = $capacity;
        $this->area = $area;
        $this->amenities = $amenities;
        $this->isReservable = $isReservable;
        $this->hourlyRate = $hourlyRate;
        $this->operatingHours = $operatingHours;
        $this->restrictions = $restrictions;
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getType(): string { return $this->type; }
    public function getCapacity(): int { return $this->capacity; }
    public function getArea(): float { return $this->area; }
    public function getAmenities(): array { return $this->amenities; }
    public function isReservable(): bool { return $this->isReservable; }
    public function getHourlyRate(): ?float { return $this->hourlyRate; }
    public function getOperatingHours(): array { return $this->operatingHours; }
    public function getRestrictions(): array { return $this->restrictions; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }

    // Métodos de Manipulação
    public function updateDetails(
        string $name,
        string $description,
        string $type = 'multipurpose',
        int $capacity = 0,
        float $area = 0.0,
        array $amenities = [],
        bool $isReservable = false,
        ?float $hourlyRate = null,
        array $operatingHours = [],
        array $restrictions = []
    ): void {
        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
        $this->capacity = $capacity;
        $this->area = $area;
        $this->amenities = $amenities;
        $this->isReservable = $isReservable;
        $this->hourlyRate = $hourlyRate;
        $this->operatingHours = $operatingHours;
        $this->restrictions = $restrictions;
        $this->updatedAt = new DateTime();
    }

    public function addAmenity(string $amenity): void {
        if (!in_array($amenity, $this->amenities)) {
            $this->amenities[] = $amenity;
            $this->updatedAt = new DateTime();
        }
    }

    public function removeAmenity(string $amenity): void {
        $key = array_search($amenity, $this->amenities);
        if ($key !== false) {
            unset($this->amenities[$key]);
            $this->amenities = array_values($this->amenities);
            $this->updatedAt = new DateTime();
        }
    }

    public function updateOperatingHours(array $hours): void {
        $this->operatingHours = $hours;
        $this->updatedAt = new DateTime();
    }

    public function addRestriction(string $restriction): void {
        if (!in_array($restriction, $this->restrictions)) {
            $this->restrictions[] = $restriction;
            $this->updatedAt = new DateTime();
        }
    }

    public function removeRestriction(string $restriction): void {
        $key = array_search($restriction, $this->restrictions);
        if ($key !== false) {
            unset($this->restrictions[$key]);
            $this->restrictions = array_values($this->restrictions);
            $this->updatedAt = new DateTime();
        }
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'capacity' => $this->capacity,
            'area' => $this->area,
            'amenities' => $this->amenities,
            'is_reservable' => $this->isReservable,
            'hourly_rate' => $this->hourlyRate,
            'operating_hours' => $this->operatingHours,
            'restrictions' => $this->restrictions,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null
        ];
    }
}
