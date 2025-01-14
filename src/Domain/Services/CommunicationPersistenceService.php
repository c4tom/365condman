<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\ValidatorInterface;
use CondMan\Domain\Interfaces\TransformerInterface;
use CondMan\Infrastructure\Repositories\CommunicationRepository;
use CondMan\Infrastructure\Repositories\UnitRepository;
use CondMan\Infrastructure\Repositories\CondominiumRepository;
use Psr\Log\LoggerInterface;

class CommunicationPersistenceService extends AbstractPersistenceService {
    private UnitRepository $unitRepository;
    private CondominiumRepository $condominiumRepository;

    public function __construct(
        CommunicationRepository $repository, 
        UnitRepository $unitRepository,
        CondominiumRepository $condominiumRepository,
        LoggerInterface $logger, 
        ?ValidatorInterface $validator = null, 
        ?TransformerInterface $transformer = null
    ) {
        parent::__construct($repository, $logger, $validator, $transformer);
        $this->unitRepository = $unitRepository;
        $this->condominiumRepository = $condominiumRepository;
    }

    /**
     * Cria um template de comunicação
     * @param array $templateData Dados do template
     * @return int ID do template criado
     * @throws \Exception Erro durante a criação do template
     */
    public function createTemplate(array $templateData): int {
        try {
            $this->validateData($templateData);
            $transformedData = $this->transformData($templateData);

            // Inicia transação
            $this->beginTransaction();

            // Cria o template
            /** @var CommunicationRepository $repository */
            $repository = $this->repository;
            $templateId = $repository->createTemplate($transformedData);

            // Confirma transação
            $this->commit();

            return $templateId;
        } catch (\Exception $e) {
            // Reverte transação em caso de erro
            $this->rollback();

            $this->logger->error('Error creating communication template', [
                'exception' => $e->getMessage(),
                'data' => $templateData
            ]);
            throw $e;
        }
    }

    /**
     * Adiciona um log de comunicação
     * @param int $communicationId ID da comunicação
     * @param array $logData Dados do log
     * @return int ID do log criado
     * @throws \Exception Erro durante a adição do log
     */
    public function addCommunicationLog(int $communicationId, array $logData): int {
        try {
            $this->validateData($logData);
            $transformedData = $this->transformData($logData);

            // Inicia transação
            $this->beginTransaction();

            // Adiciona log de comunicação
            /** @var CommunicationRepository $repository */
            $repository = $this->repository;
            $logId = $repository->addCommunicationLog($communicationId, $transformedData);

            // Confirma transação
            $this->commit();

            return $logId;
        } catch (\Exception $e) {
            // Reverte transação em caso de erro
            $this->rollback();

            $this->logger->error('Error adding communication log', [
                'exception' => $e->getMessage(),
                'communicationId' => $communicationId,
                'data' => $logData
            ]);
            throw $e;
        }
    }

    /**
     * Encontra comunicações por condomínio
     * @param int $condominiumId ID do condomínio
     * @param array $filters Filtros adicionais
     * @return array Lista de comunicações
     */
    public function findByCondominium(int $condominiumId, array $filters = []): array {
        try {
            /** @var CommunicationRepository $repository */
            $repository = $this->repository;
            return $repository->findByCondominium($condominiumId, $filters);
        } catch (\Exception $e) {
            $this->logger->error('Error finding communications by condominium', [
                'exception' => $e->getMessage(),
                'condominiumId' => $condominiumId,
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    /**
     * Encontra comunicações por unidade
     * @param int $unitId ID da unidade
     * @param array $filters Filtros adicionais
     * @return array Lista de comunicações
     */
    public function findByUnit(int $unitId, array $filters = []): array {
        try {
            /** @var CommunicationRepository $repository */
            $repository = $this->repository;
            return $repository->findByUnit($unitId, $filters);
        } catch (\Exception $e) {
            $this->logger->error('Error finding communications by unit', [
                'exception' => $e->getMessage(),
                'unitId' => $unitId,
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    /**
     * Encontra templates de comunicação
     * @param array $filters Filtros para busca de templates
     * @return array Lista de templates
     */
    public function findTemplates(array $filters = []): array {
        try {
            /** @var CommunicationRepository $repository */
            $repository = $this->repository;
            return $repository->findTemplates($filters);
        } catch (\Exception $e) {
            $this->logger->error('Error finding communication templates', [
                'exception' => $e->getMessage(),
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    /**
     * Encontra logs de uma comunicação
     * @param int $communicationId ID da comunicação
     * @param array $filters Filtros adicionais
     * @return array Lista de logs
     */
    public function findCommunicationLogs(int $communicationId, array $filters = []): array {
        try {
            /** @var CommunicationRepository $repository */
            $repository = $this->repository;
            return $repository->findCommunicationLogs($communicationId, $filters);
        } catch (\Exception $e) {
            $this->logger->error('Error finding communication logs', [
                'exception' => $e->getMessage(),
                'communicationId' => $communicationId,
                'filters' => $filters
            ]);
            throw $e;
        }
    }
}
