<?php
namespace CondMan\Domain\Interfaces;

interface InternationalizationInterface {
    /**
     * Carrega traduções para um idioma específico
     * @param string $locale Código do idioma (ex: pt_BR, en_US)
     * @param string $domain Domínio de tradução
     * @return bool Sucesso no carregamento
     */
    public function loadTranslations(string $locale, string $domain = 'condman'): bool;

    /**
     * Traduz uma string
     * @param string $text Texto a ser traduzido
     * @param string $domain Domínio de tradução
     * @return string Texto traduzido
     */
    public function translate(string $text, string $domain = 'condman'): string;

    /**
     * Traduz uma string com suporte a pluralização
     * @param string $singular Texto no singular
     * @param string $plural Texto no plural
     * @param int $count Quantidade para determinar singular/plural
     * @param string $domain Domínio de tradução
     * @return string Texto traduzido
     */
    public function translatePlural(
        string $singular, 
        string $plural, 
        int $count, 
        string $domain = 'condman'
    ): string;

    /**
     * Obtém lista de idiomas suportados
     * @return array Lista de idiomas
     */
    public function getSupportedLanguages(): array;

    /**
     * Detecta o idioma do navegador
     * @return string Código do idioma
     */
    public function detectBrowserLanguage(): string;

    /**
     * Formata data de acordo com localidade
     * @param \DateTime $date Data a ser formatada
     * @param string $locale Código do idioma
     * @param string $format Formato de data
     * @return string Data formatada
     */
    public function formatDate(
        \DateTime $date, 
        string $locale = 'pt_BR', 
        string $format = 'full'
    ): string;

    /**
     * Formata moeda de acordo com localidade
     * @param float $value Valor monetário
     * @param string $locale Código do idioma
     * @param string $currency Código da moeda
     * @return string Valor formatado
     */
    public function formatCurrency(
        float $value, 
        string $locale = 'pt_BR', 
        string $currency = 'BRL'
    ): string;

    /**
     * Registra novo domínio de tradução
     * @param string $domain Nome do domínio
     * @param string $path Caminho para arquivos de tradução
     * @return bool Sucesso no registro
     */
    public function registerTranslationDomain(string $domain, string $path): bool;
}
