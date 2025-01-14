<?php
namespace CondMan\Domain\Interfaces;

interface TranslationManagementInterface {
    /**
     * Lista todos os domínios de tradução disponíveis
     * @return array Lista de domínios
     */
    public function listTranslationDomains(): array;

    /**
     * Obtém todas as traduções para um domínio e idioma específicos
     * @param string $domain Domínio de tradução
     * @param string $locale Código do idioma
     * @return array Traduções do domínio
     */
    public function getTranslations(string $domain, string $locale): array;

    /**
     * Adiciona ou atualiza uma tradução
     * @param string $domain Domínio de tradução
     * @param string $locale Código do idioma
     * @param string $key Chave da tradução
     * @param string $translation Texto traduzido
     * @return bool Sucesso na operação
     */
    public function updateTranslation(
        string $domain, 
        string $locale, 
        string $key, 
        string $translation
    ): bool;

    /**
     * Remove uma tradução específica
     * @param string $domain Domínio de tradução
     * @param string $locale Código do idioma
     * @param string $key Chave da tradução
     * @return bool Sucesso na operação
     */
    public function removeTranslation(
        string $domain, 
        string $locale, 
        string $key
    ): bool;

    /**
     * Exporta traduções para um arquivo
     * @param string $domain Domínio de tradução
     * @param string $locale Código do idioma
     * @param string $format Formato de exportação (yaml, json)
     * @return string Caminho do arquivo exportado
     */
    public function exportTranslations(
        string $domain, 
        string $locale, 
        string $format = 'yaml'
    ): string;

    /**
     * Importa traduções de um arquivo
     * @param string $filePath Caminho do arquivo de traduções
     * @param string $domain Domínio de tradução
     * @param string $locale Código do idioma
     * @return bool Sucesso na importação
     */
    public function importTranslations(
        string $filePath, 
        string $domain, 
        string $locale
    ): bool;

    /**
     * Verifica a consistência das traduções
     * @param string $domain Domínio de tradução
     * @return array Relatório de consistência
     */
    public function validateTranslations(string $domain): array;

    /**
     * Gera relatório de cobertura de traduções
     * @param string $domain Domínio de tradução
     * @return array Relatório de cobertura
     */
    public function generateTranslationCoverageReport(string $domain): array;

    /**
     * Busca traduções faltantes
     * @param string $domain Domínio de tradução
     * @param string $sourceLocale Idioma de origem
     * @param string $targetLocale Idioma de destino
     * @return array Traduções faltantes
     */
    public function findMissingTranslations(
        string $domain, 
        string $sourceLocale, 
        string $targetLocale
    ): array;
}
