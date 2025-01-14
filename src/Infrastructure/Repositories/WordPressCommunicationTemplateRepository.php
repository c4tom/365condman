<?php
namespace CondMan\Infrastructure\Repositories;

use CondMan\Domain\Repositories\CommunicationTemplateRepositoryInterface;
use CondMan\Domain\Entities\CommunicationTemplate;
use CondMan\Domain\Interfaces\LoggerInterface;
use DateTime;
use wpdb;
use Exception;

class WordPressCommunicationTemplateRepository implements CommunicationTemplateRepositoryInterface {
    private wpdb $wpdb;
    private LoggerInterface $logger;
    private string $templateTable;

    public function __construct(
        wpdb $wpdb, 
        LoggerInterface $logger,
        ?string $templateTable = null
    ) {
        $this->wpdb = $wpdb;
        $this->logger = $logger;
        $this->templateTable = $templateTable ?? $this->wpdb->prefix . 'communication_templates';
    }

    public function save(CommunicationTemplate $template): CommunicationTemplate {
        try {
            $data = [
                'author_id' => $template->getAuthorId(),
                'name' => $template->getName(),
                'title' => $template->getTitle(),
                'content' => $template->getContent(),
                'type' => $template->getType(),
                'metadata' => json_encode($template->getMetadata()),
                'is_default' => $template->isDefault() ? 1 : 0,
                'placeholders' => json_encode($template->getPlaceholders())
            ];

            if ($template->getId() === null) {
                // Inserir novo template
                $this->wpdb->insert($this->templateTable, $data);
                $templateId = $this->wpdb->insert_id;

                // Se for definido como padrão, remover padrão anterior do mesmo tipo
                if ($template->isDefault()) {
                    $this->removeOtherDefaultTemplates($template->getType(), $templateId);
                }
            } else {
                // Atualizar template existente
                $this->wpdb->update(
                    $this->templateTable, 
                    $data, 
                    ['id' => $template->getId()]
                );
                $templateId = $template->getId();

                // Se for definido como padrão, remover padrão anterior do mesmo tipo
                if ($template->isDefault()) {
                    $this->removeOtherDefaultTemplates($template->getType(), $templateId);
                }
            }

            $this->logger->info('Template de comunicação salvo', [
                'template_id' => $templateId,
                'name' => $template->getName()
            ]);

            return $this->findById($templateId);
        } catch (Exception $e) {
            $this->logger->error('Erro ao salvar template', [
                'error' => $e->getMessage(),
                'template_name' => $template->getName()
            ]);
            throw $e;
        }
    }

    public function findById(int $id): ?CommunicationTemplate {
        try {
            $result = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->templateTable} WHERE id = %d", 
                    $id
                ), 
                ARRAY_A
            );

            return $result ? $this->createTemplateFromDbResult($result) : null;
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar template por ID', [
                'error' => $e->getMessage(),
                'template_id' => $id
            ]);
            throw $e;
        }
    }

    public function findByFilters(array $filters): array {
        try {
            $conditions = [];
            $values = [];

            if (isset($filters['author_id'])) {
                $conditions[] = 'author_id = %d';
                $values[] = $filters['author_id'];
            }

            if (isset($filters['type'])) {
                $conditions[] = 'type = %s';
                $values[] = $filters['type'];
            }

            if (isset($filters['is_default'])) {
                $conditions[] = 'is_default = %d';
                $values[] = $filters['is_default'] ? 1 : 0;
            }

            $query = "SELECT * FROM {$this->templateTable}";
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }

            $query .= " ORDER BY created_at DESC";

            if (isset($filters['limit'])) {
                $query .= $this->wpdb->prepare(" LIMIT %d", $filters['limit']);
            }

            $results = $this->wpdb->get_results(
                $this->wpdb->prepare($query, $values), 
                ARRAY_A
            );

            return array_map(
                fn($result) => $this->createTemplateFromDbResult($result), 
                $results
            );
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar templates', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    public function countByFilters(array $filters): int {
        try {
            $conditions = [];
            $values = [];

            if (isset($filters['author_id'])) {
                $conditions[] = 'author_id = %d';
                $values[] = $filters['author_id'];
            }

            if (isset($filters['type'])) {
                $conditions[] = 'type = %s';
                $values[] = $filters['type'];
            }

            $query = "SELECT COUNT(*) FROM {$this->templateTable}";
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }

            return (int) $this->wpdb->get_var(
                $this->wpdb->prepare($query, $values)
            );
        } catch (Exception $e) {
            $this->logger->error('Erro ao contar templates', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    public function findDefaultTemplateByType(string $type): ?CommunicationTemplate {
        try {
            $result = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->templateTable} WHERE type = %s AND is_default = 1", 
                    $type
                ), 
                ARRAY_A
            );

            return $result ? $this->createTemplateFromDbResult($result) : null;
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar template padrão', [
                'error' => $e->getMessage(),
                'type' => $type
            ]);
            throw $e;
        }
    }

    public function setDefaultTemplate(int $templateId): bool {
        try {
            $template = $this->findById($templateId);

            if (!$template) {
                throw new Exception("Template não encontrado");
            }

            // Remover outros templates padrão do mesmo tipo
            $this->removeOtherDefaultTemplates($template->getType(), $templateId);

            // Definir template atual como padrão
            $result = $this->wpdb->update(
                $this->templateTable,
                ['is_default' => 1],
                ['id' => $templateId]
            );

            $this->logger->info('Template definido como padrão', [
                'template_id' => $templateId,
                'type' => $template->getType()
            ]);

            return $result !== false;
        } catch (Exception $e) {
            $this->logger->error('Erro ao definir template padrão', [
                'error' => $e->getMessage(),
                'template_id' => $templateId
            ]);
            throw $e;
        }
    }

    public function delete(int $templateId): bool {
        try {
            $result = $this->wpdb->delete(
                $this->templateTable, 
                ['id' => $templateId]
            );

            $this->logger->info('Template removido', [
                'template_id' => $templateId
            ]);

            return $result !== false;
        } catch (Exception $e) {
            $this->logger->error('Erro ao remover template', [
                'error' => $e->getMessage(),
                'template_id' => $templateId
            ]);
            throw $e;
        }
    }

    private function removeOtherDefaultTemplates(string $type, int $currentTemplateId): void {
        $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->templateTable} SET is_default = 0 WHERE type = %s AND id != %d",
                $type,
                $currentTemplateId
            )
        );
    }

    private function createTemplateFromDbResult(array $result): CommunicationTemplate {
        return new CommunicationTemplate(
            (int) $result['id'],
            (int) $result['author_id'],
            $result['name'],
            $result['title'],
            $result['content'],
            $result['type'],
            json_decode($result['metadata'] ?? '{}', true),
            new DateTime($result['created_at']),
            $result['updated_at'] ? new DateTime($result['updated_at']) : null,
            (bool) $result['is_default'],
            json_decode($result['placeholders'] ?? '[]', true)
        );
    }
}
