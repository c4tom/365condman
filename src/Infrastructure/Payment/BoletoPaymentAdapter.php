<?php
namespace CondMan\Infrastructure\Payment;

use CondMan\Domain\Interfaces\PaymentGatewayInterface;
use CondMan\Domain\Entities\Invoice;
use CondMan\Domain\Entities\Payment;
use CondMan\Domain\Interfaces\LoggerInterface;
use DateTime;
use DateInterval;

class BoletoPaymentAdapter implements PaymentGatewayInterface {
    private LoggerInterface $logger;
    private string $bankCode;
    private string $agencyNumber;
    private string $accountNumber;

    public function __construct(
        LoggerInterface $logger,
        string $bankCode,
        string $agencyNumber,
        string $accountNumber
    ) {
        $this->logger = $logger;
        $this->bankCode = $bankCode;
        $this->agencyNumber = $agencyNumber;
        $this->accountNumber = $accountNumber;
    }

    /**
     * Processa pagamento via Boleto Bancário
     * @param Invoice $invoice Fatura a ser paga
     * @param array $paymentDetails Detalhes do pagamento
     * @return Payment Objeto de pagamento
     * @throws \Exception Erro no processamento
     */
    public function processPayment(Invoice $invoice, array $paymentDetails): Payment {
        try {
            // Validação básica
            $this->validatePaymentDetails($paymentDetails);

            // Gera código de barras do boleto
            $boletoBarcode = $this->generateBoletoBarcode($invoice);

            // Calcula data de vencimento
            $dueDate = $this->calculateDueDate($invoice);

            // Cria objeto de pagamento
            $payment = new Payment([
                'invoice_id' => $invoice->getId(),
                'condominium_id' => $invoice->getCondominiumId(),
                'amount' => $invoice->getTotalAmount(),
                'payment_method' => 'boleto',
                'status' => 'pending',
                'transaction_id' => $this->generateTransactionId(),
                'gateway_reference' => $boletoBarcode,
                'payment_date' => $dueDate,
                'receipt_url' => $this->generateBoletoUrl($boletoBarcode),
                'processing_fee' => $this->calculateProcessingFee($invoice->getTotalAmount())
            ]);

            $this->logger->info('Boleto payment processed', [
                'invoice_id' => $invoice->getId(),
                'boleto_barcode' => $boletoBarcode
            ]);

            return $payment;
        } catch (\Exception $e) {
            $this->logger->error('Boleto payment processing error', [
                'invoice_id' => $invoice->getId(),
                'exception' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Gera boleto bancário
     * @param Invoice $invoice Fatura para geração
     * @return string Código de barras do boleto
     * @throws \Exception Erro na geração
     */
    public function generateBoleto(Invoice $invoice): string {
        try {
            $boletoBarcode = $this->generateBoletoBarcode($invoice);
            $boletoUrl = $this->generateBoletoUrl($boletoBarcode);

            $this->logger->info('Boleto generated', [
                'invoice_id' => $invoice->getId(),
                'boleto_url' => $boletoUrl
            ]);

            return $boletoUrl;
        } catch (\Exception $e) {
            $this->logger->error('Boleto generation error', [
                'invoice_id' => $invoice->getId(),
                'exception' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Verifica status de pagamento de boleto
     * @param string $paymentId Identificador do pagamento
     * @return string Status do pagamento
     * @throws \Exception Erro na consulta
     */
    public function checkPaymentStatus(string $paymentId): string {
        try {
            // Simulação de consulta de status
            // Em produção, integraria com API bancária
            $statuses = [
                'pending' => 'Boleto emitido, aguardando pagamento',
                'overdue' => 'Boleto vencido',
                'completed' => 'Pagamento confirmado',
                'canceled' => 'Boleto cancelado'
            ];

            $status = $statuses[array_rand($statuses)];

            $this->logger->info('Boleto payment status checked', [
                'payment_id' => $paymentId,
                'status' => $status
            ]);

            return $status;
        } catch (\Exception $e) {
            $this->logger->error('Boleto payment status check error', [
                'payment_id' => $paymentId,
                'exception' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Reembolsa pagamento de boleto
     * @param string $paymentId Identificador do pagamento
     * @param float $amount Valor a ser reembolsado
     * @return bool Sucesso do reembolso
     * @throws \Exception Erro no reembolso
     */
    public function refundPayment(string $paymentId, float $amount = null): bool {
        try {
            // Simulação de reembolso
            // Em produção, integraria com API bancária
            $refundResult = rand(0, 1) === 1;

            $this->logger->info('Boleto payment refunded', [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'result' => $refundResult
            ]);

            return $refundResult;
        } catch (\Exception $e) {
            $this->logger->error('Boleto payment refund error', [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'exception' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Lista métodos de pagamento de boleto
     * @return array Métodos de pagamento
     */
    public function listAvailablePaymentMethods(): array {
        return [
            'boleto_bancario' => 'Boleto Bancário',
            'boleto_internet' => 'Boleto Internet Banking',
            'boleto_app' => 'Boleto por Aplicativo'
        ];
    }

    /**
     * Valida detalhes do pagamento
     * @param array $paymentDetails Detalhes do pagamento
     * @throws \InvalidArgumentException Se detalhes inválidos
     */
    private function validatePaymentDetails(array $paymentDetails): void {
        $requiredFields = ['payer_name', 'payer_document'];
        
        foreach ($requiredFields as $field) {
            if (!isset($paymentDetails[$field])) {
                throw new \InvalidArgumentException("Missing required field: $field");
            }
        }
    }

    /**
     * Gera código de barras do boleto
     * @param Invoice $invoice Fatura
     * @return string Código de barras
     */
    private function generateBoletoBarcode(Invoice $invoice): string {
        return sprintf(
            '%s%s%s%s%s',
            $this->bankCode,
            str_pad($this->agencyNumber, 4, '0', STR_PAD_LEFT),
            str_pad($this->accountNumber, 7, '0', STR_PAD_LEFT),
            str_pad((string)$invoice->getId(), 10, '0', STR_PAD_LEFT),
            hash('crc32', $invoice->getId() . $invoice->getTotalAmount())
        );
    }

    /**
     * Gera URL do boleto
     * @param string $boletoBarcode Código de barras
     * @return string URL do boleto
     */
    private function generateBoletoUrl(string $boletoBarcode): string {
        // Em produção, integraria com serviço bancário
        return "https://boletos.example.com/view/{$boletoBarcode}";
    }

    /**
     * Gera ID de transação
     * @return string ID de transação
     */
    private function generateTransactionId(): string {
        return uniqid('BOLETO_', true);
    }

    /**
     * Calcula data de vencimento
     * @param Invoice $invoice Fatura
     * @return DateTime Data de vencimento
     */
    private function calculateDueDate(Invoice $invoice): DateTime {
        $dueDate = new DateTime();
        $dueDate->add(new DateInterval('P30D')); // 30 dias a partir da data atual
        return $dueDate;
    }

    /**
     * Calcula taxa de processamento
     * @param float $amount Valor do pagamento
     * @return float Taxa de processamento
     */
    private function calculateProcessingFee(float $amount): float {
        // Exemplo de cálculo de taxa
        // Em produção, usaria tabela de taxas bancárias
        return max(2.50, $amount * 0.01);
    }
}
