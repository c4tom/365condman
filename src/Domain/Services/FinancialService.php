<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\ConfigurationInterface;
use CondMan\Domain\Entities\Invoice;
use CondMan\Infrastructure\Notifications\CommunicationService;

class FinancialService {
    private $config;
    private $wpdb;
    private $communicationService;

    public function __construct(
        ConfigurationInterface $config,
        \wpdb $wpdb,
        CommunicationService $communicationService
    ) {
        $this->config = $config;
        $this->wpdb = $wpdb;
        $this->communicationService = $communicationService;
    }

    /**
     * Gera fatura para uma unidade
     * 
     * @param array $data Dados da fatura
     * @return Invoice Fatura gerada
     * @throws \InvalidArgumentException Se dados inválidos
     */
    public function generateInvoice(array $data): Invoice {
        $this->validateInvoiceData($data);

        $invoiceData = [
            'condominium_id' => $data['condominium_id'],
            'unit_id' => $data['unit_id'],
            'reference_month' => $data['reference_month'],
            'reference_year' => $data['reference_year'],
            'due_date' => $data['due_date'],
            'total_amount' => $this->calculateTotalAmount($data),
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $result = $this->wpdb->insert(
            $this->wpdb->prefix . '365condman_invoices', 
            $invoiceData
        );

        if ($result === false) {
            throw new \RuntimeException('Falha ao gerar fatura: ' . $this->wpdb->last_error);
        }

        $invoiceId = $this->wpdb->insert_id;
        $invoice = $this->findInvoiceById($invoiceId);

        $this->createInvoiceItems($invoiceId, $data['items'] ?? []);
        $this->notifyInvoiceGeneration($invoice);

        return $invoice;
    }

    /**
     * Valida dados da fatura
     * 
     * @param array $data Dados a serem validados
     * @throws \InvalidArgumentException Se dados inválidos
     */
    private function validateInvoiceData(array $data): void {
        if (empty($data['condominium_id'])) {
            throw new \InvalidArgumentException('ID do condomínio é obrigatório');
        }

        if (empty($data['unit_id'])) {
            throw new \InvalidArgumentException('ID da unidade é obrigatório');
        }

        if (empty($data['reference_month']) || empty($data['reference_year'])) {
            throw new \InvalidArgumentException('Referência de mês/ano é obrigatória');
        }
    }

    /**
     * Calcula valor total da fatura
     * 
     * @param array $data Dados para cálculo
     * @return float Valor total
     */
    private function calculateTotalAmount(array $data): float {
        $items = $data['items'] ?? [];
        return array_reduce($items, function($total, $item) {
            return $total + ($item['amount'] ?? 0);
        }, 0.0);
    }

    /**
     * Cria itens da fatura
     * 
     * @param int $invoiceId ID da fatura
     * @param array $items Itens da fatura
     */
    private function createInvoiceItems(int $invoiceId, array $items): void {
        foreach ($items as $item) {
            $this->wpdb->insert(
                $this->wpdb->prefix . '365condman_invoice_items', 
                [
                    'invoice_id' => $invoiceId,
                    'description' => $item['description'],
                    'amount' => $item['amount'],
                    'type' => $item['type'] ?? 'default'
                ]
            );
        }
    }

    /**
     * Encontra fatura por ID
     * 
     * @param int $id Identificador da fatura
     * @return Invoice Fatura encontrada
     */
    public function findInvoiceById(int $id): Invoice {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}365condman_invoices WHERE id = %d", 
                $id
            ), 
            ARRAY_A
        );

        return new Invoice($result);
    }

    /**
     * Notifica geração de fatura
     * 
     * @param Invoice $invoice Fatura gerada
     */
    private function notifyInvoiceGeneration(Invoice $invoice): void {
        // Buscar email do morador/responsável pela unidade
        $email = $this->findUnitResponsibleEmail($invoice->getUnitId());
        
        if ($email) {
            $this->communicationService->send(
                $email, 
                $this->generateInvoiceEmailBody($invoice),
                [
                    'subject' => "Fatura {$invoice->getReferenceMonth()}/{$invoice->getReferenceYear()}",
                    'html' => true
                ]
            );
        }
    }

    /**
     * Encontra email do responsável pela unidade
     * 
     * @param int $unitId ID da unidade
     * @return string|null Email do responsável
     */
    private function findUnitResponsibleEmail(int $unitId): ?string {
        // Implementação simplificada
        // Na prática, buscaria do banco de dados
        return $this->config->get('default_notification_email');
    }

    /**
     * Gera corpo do email de fatura
     * 
     * @param Invoice $invoice Fatura
     * @return string Corpo do email
     */
    private function generateInvoiceEmailBody(Invoice $invoice): string {
        return sprintf(
            "Prezado(a),<br><br>" .
            "Segue a fatura do condomínio referente a %s/%s.<br>" .
            "Valor total: R$ %.2f<br>" .
            "Data de vencimento: %s<br><br>" .
            "Atenciosamente,<br>Administração",
            $invoice->getReferenceMonth(),
            $invoice->getReferenceYear(),
            $invoice->getTotalAmount(),
            $invoice->getDueDate()
        );
    }
}
