<?php
namespace CondMan\Domain\Services;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionClass;
use ReflectionProperty;

class EntityMappingService {
    private EntityManagerInterface $entityManager;
    private ClassMetadataFactory $metadataFactory;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
        $this->metadataFactory = $entityManager->getMetadataFactory();
    }

    /**
     * Obtém metadados de uma entidade
     * @param string $entityClass Nome da classe da entidade
     * @return ClassMetadata Metadados da entidade
     */
    public function getEntityMetadata(string $entityClass): ClassMetadata {
        return $this->metadataFactory->getMetadataFor($entityClass);
    }

    /**
     * Lista todas as propriedades mapeadas de uma entidade
     * @param string $entityClass Nome da classe da entidade
     * @return array Propriedades mapeadas
     */
    public function listMappedProperties(string $entityClass): array {
        $metadata = $this->getEntityMetadata($entityClass);
        return $metadata->getFieldNames();
    }

    /**
     * Lista relacionamentos de uma entidade
     * @param string $entityClass Nome da classe da entidade
     * @return array Relacionamentos
     */
    public function listEntityRelationships(string $entityClass): array {
        $metadata = $this->getEntityMetadata($entityClass);
        return $metadata->getAssociationNames();
    }

    /**
     * Gera um mapa completo de uma entidade
     * @param string $entityClass Nome da classe da entidade
     * @return array Mapa completo da entidade
     */
    public function generateEntityMap(string $entityClass): array {
        $reflectionClass = new ReflectionClass($entityClass);
        $metadata = $this->getEntityMetadata($entityClass);

        return [
            'class_name' => $entityClass,
            'table_name' => $metadata->getTableName(),
            'primary_key' => $metadata->getIdentifierFieldNames(),
            'properties' => $this->mapClassProperties($reflectionClass),
            'relationships' => $this->listEntityRelationships($entityClass)
        ];
    }

    /**
     * Mapeia propriedades de uma classe
     * @param ReflectionClass $reflectionClass Classe para reflexão
     * @return array Propriedades mapeadas
     */
    private function mapClassProperties(ReflectionClass $reflectionClass): array {
        $properties = [];

        foreach ($reflectionClass->getProperties() as $property) {
            $properties[] = $this->mapPropertyDetails($property);
        }

        return $properties;
    }

    /**
     * Mapeia detalhes de uma propriedade
     * @param ReflectionProperty $property Propriedade para mapeamento
     * @return array Detalhes da propriedade
     */
    private function mapPropertyDetails(ReflectionProperty $property): array {
        return [
            'name' => $property->getName(),
            'type' => $property->getType() ? $property->getType()->getName() : 'mixed',
            'visibility' => $this->getPropertyVisibility($property),
            'is_nullable' => $property->getType() ? $property->getType()->allowsNull() : true
        ];
    }

    /**
     * Obtém a visibilidade de uma propriedade
     * @param ReflectionProperty $property Propriedade
     * @return string Visibilidade
     */
    private function getPropertyVisibility(ReflectionProperty $property): string {
        return match(true) {
            $property->isPublic() => 'public',
            $property->isProtected() => 'protected',
            $property->isPrivate() => 'private',
            default => 'unknown'
        };
    }

    /**
     * Valida o mapeamento de uma entidade
     * @param string $entityClass Nome da classe da entidade
     * @return bool Indica se o mapeamento é válido
     */
    public function validateEntityMapping(string $entityClass): bool {
        try {
            $this->getEntityMetadata($entityClass);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Gera um relatório de mapeamento
     * @param string $entityClass Nome da classe da entidade
     * @return array Relatório de mapeamento
     */
    public function generateMappingReport(string $entityClass): array {
        $report = [
            'entity_class' => $entityClass,
            'is_mapped' => $this->validateEntityMapping($entityClass),
            'mapped_properties' => $this->listMappedProperties($entityClass),
            'relationships' => $this->listEntityRelationships($entityClass)
        ];

        return $report;
    }
}
