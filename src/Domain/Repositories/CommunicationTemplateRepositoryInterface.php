<?php
namespace CondMan\Domain\Repositories;

use CondMan\Domain\Entities\CommunicationTemplate;

interface CommunicationTemplateRepositoryInterface {
    /**
     * Salva um novo template ou atualiza um existente
     */
    public function save(CommunicationTemplate $template): CommunicationTemplate;

    /**
     * Busca um template por ID
     */
    public function findById(int $id): ?CommunicationTemplate;

    /**
     * Busca templates por filtros
     * @param array $filters Filtros para busca de templates
     * @return CommunicationTemplate[]
     */
    public function findByFilters(array $filters): array;

    /**
     * Conta templates por filtros
     * @param array $filters Filtros para contagem
     */
    public function countByFilters(array $filters): int;

    /**
     * Busca template padrão para um tipo específico
     */
    public function findDefaultTemplateByType(string $type): ?CommunicationTemplate;

    /**
     * Define um template como padrão para seu tipo
     */
    public function setDefaultTemplate(int $templateId): bool;

    /**
     * Remove template
     */
    public function delete(int $templateId): bool;
}
