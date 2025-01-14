<?php
namespace CondMan\Infrastructure\Payment;

use CondMan\Domain\Interfaces\PaymentGatewayInterface;
use CondMan\Domain\Entities\Invoice;
use CondMan\Domain\Entities\Payment;
use CondMan\Domain\Interfaces\LoggerInterface;
use DateTime;

class PixPaymentAdapter implements PaymentGatewayInterface {
    private LoggerInterface $logger;
    private string $pixKey;
    private string $merchantId;

    public function __construct(
        LoggerInterface $logger,
        string $pixKey,
        string $merchantId
    ) {
        $this->logger = $logger;
        $this->pixKey = $pixKey;
        $this->merchantId = $merchantId;
    }

    /**
     * Processa pagamento via Pix
     * @param Invoice $invoice Fatura a ser paga
     * @param array $paymentDetails Detalhes do pagamento
     * @return Payment Objeto de pagamento
     * @throws \Exception Erro no processamento
     */
    public function processPayment(Invoice $invoice, array $paymentDetails): Payment {
        try {
            // Validação básica
            $this->validatePaymentDetails($paymentDetails);

            // Gera QR Code ou chave Pix
            $pixKey = $this->generatePixKey($invoice);

            // Cria objeto de pagamento
            $payment = new Payment([
                'invoice_id' => $invoice->getId(),
                'condominium_id' => $invoice->getCondominiumId(),
                'amount' => $invoice->getTotalAmount(),
                'payment_method' => 'pix',
                'status' => 'pending',
                'transaction_id' => $this->generateTransactionId(),
                'gateway_reference' => $pixKey,
                'payment_date' => new DateTime(),
                'receipt_url' => $this->generateReceiptUrl($pixKey),
                'processing_fee' => $this->calculateProcessingFee($invoice->getTotalAmount())
            ]);

            $this->logger->info('Pix payment processed', [
                'invoice_id' => $invoice->getId(),
                'pix_key' => $pixKey
            ]);

            return $payment;
        } catch (\Exception $e) {
            $this->logger->error('Pix payment processing error', [
                'invoice_id' => $invoice->getId(),
                'exception' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Gera boleto Pix (QR Code)
     * @param Invoice $invoice Fatura para geração
     * @return string URL do QR Code
     * @throws \Exception Erro na geração
     */
    public function generateBoleto(Invoice $invoice): string {
        try {
            $pixKey = $this->generatePixKey($invoice);
            $qrCodeUrl = $this->generateQrCodeUrl($pixKey);

            $this->logger->info('Pix QR Code generated', [
                'invoice_id' => $invoice->getId(),
                'qr_code_url' => $qrCodeUrl
            ]);

            return $qrCodeUrl;
        } catch (\Exception $e) {
            $this->logger->error('Pix QR Code generation error', [
                'invoice_id' => $invoice->getId(),
                'exception' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Verifica status de pagamento Pix
     * @param string $paymentId Identificador do pagamento
     * @return string Status do pagamento
     * @throws \Exception Erro na consulta
     */
    public function checkPaymentStatus(string $paymentId): string {
        try {
            // Simulação de consulta de status
            // Em produção, integraria com API do banco
            $statuses = [
                'pending' => 'Aguardando pagamento',
                'completed' => 'Pagamento confirmado',
                'failed' => 'Pagamento não realizado'
            ];

            $status = $statuses[array_rand($statuses)];

            $this->logger->info('Pix payment status checked', [
                'payment_id' => $paymentId,
                'status' => $status
            ]);

            return $status;
        } catch (\Exception $e) {
            $this->logger->error('Pix payment status check error', [
                'payment_id' => $paymentId,
                'exception' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Reembolsa pagamento Pix
     * @param string $paymentId Identificador do pagamento
     * @param float $amount Valor a ser reembolsado
     * @return bool Sucesso do reembolso
     * @throws \Exception Erro no reembolso
     */
    public function refundPayment(string $paymentId, float $amount = null): bool {
        try {
            // Simulação de reembolso
            // Em produção, integraria com API do banco
            $refundResult = rand(0, 1) === 1;

            $this->logger->info('Pix payment refunded', [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'result' => $refundResult
            ]);

            return $refundResult;
        } catch (\Exception $e) {
            $this->logger->error('Pix payment refund error', [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'exception' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Lista métodos de pagamento Pix
     * @return array Métodos de pagamento
     */
    public function listAvailablePaymentMethods(): array {
        return [
            'pix_qr_code' => 'Pix QR Code',
            'pix_key' => 'Chave Pix',
            'pix_email' => 'Pix por E-mail'
        ];
    }

    /**
     * Valida detalhes do pagamento
     * @param array $paymentDetails Detalhes do pagamento
     * @throws \InvalidArgumentException Se detalhes inválidos
     */
    private function validatePaymentDetails(array $paymentDetails): void {
        $requiredFields = ['pix_key', 'payer_document'];
        
        foreach ($requiredFields as $field) {
            if (!isset($paymentDetails[$field])) {
                throw new \InvalidArgumentException("Missing required field: $field");
            }
        }
    }

    /**
     * Gera chave Pix para pagamento
     * @param Invoice $invoice Fatura
     * @return string Chave Pix gerada
     */
    private function generatePixKey(Invoice $invoice): string {
        return hash('sha256', 
            $invoice->getId() . 
            $invoice->getCondominiumId() . 
            $invoice->getTotalAmount() . 
            time()
        );
    }

    /**
     * Gera URL de QR Code
     * @param string $pixKey Chave Pix
     * @return string URL do QR Code
     */
    private function generateQrCodeUrl(string $pixKey): string {
        // Em produção, integraria com serviço de geração de QR Code
        return "https://qrcode.example.com/pix/{$pixKey}";
    }

    /**
     * Gera ID de transação
     * @return string ID de transação
     */
    private function generateTransactionId(): string {
        return uniqid('PIX_', true);
    }

    /**
     * Gera URL de recibo
     * @param string $pixKey Chave Pix
     * @return string URL do recibo
     */
    private function generateReceiptUrl(string $pixKey): string {
        // Em produção, integraria com serviço de geração de recibo
        return "https://receipts.example.com/pix/{$pixKey}";
    }

    /**
     * Calcula taxa de processamento
     * @param float $amount Valor do pagamento
     * @return float Taxa de processamento
     */
    private function calculateProcessingFee(float $amount): float {
        // Exemplo de cálculo de taxa
        // Em produção, usaria tabela de taxas do provedor
        return max(0.50, $amount * 0.02);
    }
}
