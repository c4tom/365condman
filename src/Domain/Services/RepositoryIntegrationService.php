<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\CacheInterface;
use CondMan\Infrastructure\Repositories\AbstractRepository;
use Psr\Log\LoggerInterface;

class RepositoryIntegrationService {
    private CacheInterface $cache;
    private LoggerInterface $logger;

    public function __construct(
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * Busca dados com suporte a cache
     * @param AbstractRepository $repository Repositório de origem
     * @param string $cacheKey Chave de cache
     * @param callable $queryMethod Método de consulta
     * @param array $queryParams Parâmetros da consulta
     * @param int $ttl Tempo de vida do cache
     * @return mixed Resultado da consulta
     */
    public function cachedQuery(
        AbstractRepository $repository,
        string $cacheKey,
        callable $queryMethod,
        array $queryParams = [],
        int $ttl = 3600
    ) {
        try {
            // Tenta buscar do cache primeiro
            $cachedResult = $this->cache->get($cacheKey);
            if ($cachedResult !== false) {
                $this->logger->info('Cache hit', ['key' => $cacheKey]);
                return $cachedResult;
            }

            // Se não estiver em cache, executa a consulta
            $result = call_user_func_array([$repository, $queryMethod], $queryParams);

            // Armazena no cache
            $this->cache->set($cacheKey, $result, $ttl);
            $this->logger->info('Cache miss', ['key' => $cacheKey]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error in cached query', [
                'exception' => $e->getMessage(),
                'repository' => get_class($repository),
                'method' => $queryMethod,
                'params' => $queryParams
            ]);
            throw $e;
        }
    }

    /**
     * Invalida cache após operações de escrita
     * @param string $cacheKey Chave de cache a ser invalidada
     * @param AbstractRepository $repository Repositório relacionado
     * @param callable|null $preInvalidationHook Função opcional executada antes da invalidação
     */
    public function invalidateCache(
        string $cacheKey,
        AbstractRepository $repository,
        ?callable $preInvalidationHook = null
    ): void {
        try {
            // Executa hook opcional antes da invalidação
            if ($preInvalidationHook) {
                call_user_func($preInvalidationHook, $repository);
            }

            // Remove do cache
            $this->cache->delete($cacheKey);
            $this->logger->info('Cache invalidated', ['key' => $cacheKey]);
        } catch (\Exception $e) {
            $this->logger->error('Error invalidating cache', [
                'exception' => $e->getMessage(),
                'key' => $cacheKey
            ]);
        }
    }

    /**
     * Sincroniza dados entre repositórios
     * @param AbstractRepository $sourceRepository Repositório de origem
     * @param AbstractRepository $targetRepository Repositório de destino
     * @param callable $transformMethod Método de transformação
     * @param array $syncOptions Opções de sincronização
     * @return int Número de registros sincronizados
     */
    public function synchronizeRepositories(
        AbstractRepository $sourceRepository,
        AbstractRepository $targetRepository,
        callable $transformMethod,
        array $syncOptions = []
    ): int {
        try {
            $defaultOptions = [
                'batchSize' => 100,
                'filters' => [],
                'orderBy' => [],
            ];
            $options = array_merge($defaultOptions, $syncOptions);

            $totalSynced = 0;
            $offset = 0;

            do {
                // Busca lote de registros
                $records = $sourceRepository->findAll(
                    $options['filters'],
                    $options['orderBy'],
                    $options['batchSize'],
                    $offset
                );

                foreach ($records as $record) {
                    // Transforma registro
                    $transformedRecord = call_user_func($transformMethod, $record);

                    // Insere no repositório de destino
                    $targetRepository->insert($transformedRecord);
                    $totalSynced++;
                }

                $offset += $options['batchSize'];
            } while (count($records) === $options['batchSize']);

            $this->logger->info('Repository synchronization complete', [
                'source' => get_class($sourceRepository),
                'target' => get_class($targetRepository),
                'totalSynced' => $totalSynced
            ]);

            return $totalSynced;
        } catch (\Exception $e) {
            $this->logger->error('Error synchronizing repositories', [
                'exception' => $e->getMessage(),
                'source' => get_class($sourceRepository),
                'target' => get_class($targetRepository)
            ]);
            throw $e;
        }
    }
}
