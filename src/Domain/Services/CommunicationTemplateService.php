<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Entities\CommunicationTemplate;
use CondMan\Domain\Repositories\CommunicationTemplateRepositoryInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use DateTime;
use Exception;

class CommunicationTemplateService {
    private CommunicationTemplateRepositoryInterface $repository;
    private LoggerInterface $logger;

    public function __construct(
        CommunicationTemplateRepositoryInterface $repository,
        LoggerInterface $logger
    ) {
        $this->repository = $repository;
        $this->logger = $logger;
    }

    /**
     * Cria um novo template de comunicação
     */
    public function createTemplate(
        int $authorId,
        string $name,
        string $title,
        string $content,
        string $type = 'general',
        array $metadata = [],
        bool $isDefault = false,
        array $placeholders = []
    ): CommunicationTemplate {
        try {
            $template = new CommunicationTemplate(
                null,
                $authorId,
                $name,
                $title,
                $content,
                $type,
                $metadata,
                null,
                null,
                $isDefault,
                $placeholders
            );

            $savedTemplate = $this->repository->save($template);

            // Se for definido como padrão, remove outros templates padrão do mesmo tipo
            if ($isDefault) {
                $this->repository->setDefaultTemplate($savedTemplate->getId());
            }

            $this->logger->info('Template de comunicação criado', [
                'template_id' => $savedTemplate->getId(),
                'name' => $name
            ]);

            return $savedTemplate;
        } catch (Exception $e) {
            $this->logger->error('Erro ao criar template', [
                'error' => $e->getMessage(),
                'name' => $name
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza um template existente
     */
    public function updateTemplate(
        int $templateId,
        string $name,
        string $title,
        string $content,
        string $type = 'general',
        array $metadata = [],
        bool $isDefault = false,
        array $placeholders = []
    ): CommunicationTemplate {
        try {
            $template = $this->repository->findById($templateId);

            if (!$template) {
                throw new Exception("Template não encontrado");
            }

            $template->updateTemplate(
                $name,
                $title,
                $content,
                $type,
                $metadata,
                $placeholders
            );

            $template->setAsDefault($isDefault);

            $savedTemplate = $this->repository->save($template);

            // Se for definido como padrão, remove outros templates padrão do mesmo tipo
            if ($isDefault) {
                $this->repository->setDefaultTemplate($savedTemplate->getId());
            }

            $this->logger->info('Template de comunicação atualizado', [
                'template_id' => $savedTemplate->getId(),
                'name' => $name
            ]);

            return $savedTemplate;
        } catch (Exception $e) {
            $this->logger->error('Erro ao atualizar template', [
                'error' => $e->getMessage(),
                'template_id' => $templateId
            ]);
            throw $e;
        }
    }

    /**
     * Busca template por ID
     */
    public function getTemplateById(int $templateId): ?CommunicationTemplate {
        try {
            $template = $this->repository->findById($templateId);

            $this->logger->info('Template de comunicação recuperado', [
                'template_id' => $templateId
            ]);

            return $template;
        } catch (Exception $e) {
            $this->logger->error('Erro ao recuperar template', [
                'error' => $e->getMessage(),
                'template_id' => $templateId
            ]);
            throw $e;
        }
    }

    /**
     * Busca template padrão por tipo
     */
    public function getDefaultTemplateByType(string $type): ?CommunicationTemplate {
        try {
            $template = $this->repository->findDefaultTemplateByType($type);

            $this->logger->info('Template padrão recuperado', [
                'type' => $type
            ]);

            return $template;
        } catch (Exception $e) {
            $this->logger->error('Erro ao recuperar template padrão', [
                'error' => $e->getMessage(),
                'type' => $type
            ]);
            throw $e;
        }
    }

    /**
     * Busca templates por filtros
     */
    public function findTemplatesByFilters(array $filters): array {
        try {
            $templates = $this->repository->findByFilters($filters);

            $this->logger->info('Templates recuperados por filtros', [
                'filters' => $filters,
                'count' => count($templates)
            ]);

            return $templates;
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar templates', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    /**
     * Remove um template
     */
    public function deleteTemplate(int $templateId): bool {
        try {
            $result = $this->repository->delete($templateId);

            $this->logger->info('Template removido', [
                'template_id' => $templateId
            ]);

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Erro ao remover template', [
                'error' => $e->getMessage(),
                'template_id' => $templateId
            ]);
            throw $e;
        }
    }

    /**
     * Renderiza template com placeholders
     */
    public function renderTemplate(
        int $templateId, 
        array $placeholderValues
    ): array {
        try {
            $template = $this->repository->findById($templateId);

            if (!$template) {
                throw new Exception("Template não encontrado");
            }

            $renderedContent = $template->replacePlaceholders($placeholderValues);

            $this->logger->info('Template renderizado', [
                'template_id' => $templateId
            ]);

            return [
                'title' => $template->getTitle(),
                'content' => $renderedContent
            ];
        } catch (Exception $e) {
            $this->logger->error('Erro ao renderizar template', [
                'error' => $e->getMessage(),
                'template_id' => $templateId
            ]);
            throw $e;
        }
    }
}
