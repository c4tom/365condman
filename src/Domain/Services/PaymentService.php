<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Entities\Invoice;
use CondMan\Domain\Entities\Payment;
use CondMan\Domain\Interfaces\PaymentGatewayInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use CondMan\Infrastructure\Repositories\PaymentRepository;
use CondMan\Infrastructure\Repositories\InvoiceRepository;
use DateTime;

class PaymentService {
    private PaymentGatewayInterface $paymentGateway;
    private PaymentRepository $paymentRepository;
    private InvoiceRepository $invoiceRepository;
    private LoggerInterface $logger;

    public function __construct(
        PaymentGatewayInterface $paymentGateway,
        PaymentRepository $paymentRepository,
        InvoiceRepository $invoiceRepository,
        LoggerInterface $logger
    ) {
        $this->paymentGateway = $paymentGateway;
        $this->paymentRepository = $paymentRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->logger = $logger;
    }

    /**
     * Processa pagamento de fatura
     * @param int $invoiceId ID da fatura
     * @param array $paymentDetails Detalhes do pagamento
     * @return Payment Objeto de pagamento
     * @throws \Exception Erro no processamento
     */
    public function processInvoicePayment(
        int $invoiceId, 
        array $paymentDetails
    ): Payment {
        try {
            // Busca fatura
            $invoice = $this->invoiceRepository->findById($invoiceId);
            if (!$invoice) {
                throw new \InvalidArgumentException("Invoice not found");
            }

            // Verifica status da fatura
            if ($invoice->getStatus() === 'paid') {
                throw new \InvalidArgumentException("Invoice already paid");
            }

            // Processa pagamento via gateway
            $payment = $this->paymentGateway->processPayment($invoice, $paymentDetails);

            // Inicia transação
            $this->paymentRepository->beginTransaction();

            // Salva pagamento
            $paymentId = $this->paymentRepository->insert($payment);
            $payment->setId($paymentId);

            // Atualiza status da fatura
            if ($payment->isSuccessful()) {
                $this->invoiceRepository->update($invoiceId, [
                    'status' => 'paid',
                    'total_paid' => $payment->getAmount(),
                    'payment_date' => $payment->getPaymentDate()->format('Y-m-d H:i:s')
                ]);
            }

            // Confirma transação
            $this->paymentRepository->commit();

            $this->logger->info('Payment processed', [
                'invoice_id' => $invoiceId,
                'payment_id' => $paymentId,
                'status' => $payment->getStatus()
            ]);

            return $payment;
        } catch (\Exception $e) {
            // Reverte transação
            $this->paymentRepository->rollback();

            $this->logger->error('Payment processing error', [
                'invoice_id' => $invoiceId,
                'exception' => $e->getMessage(),
                'payment_details' => $paymentDetails
            ]);

            throw $e;
        }
    }

    /**
     * Gera boleto bancário para fatura
     * @param int $invoiceId ID da fatura
     * @return string URL ou código do boleto
     * @throws \Exception Erro na geração
     */
    public function generateInvoiceBoleto(int $invoiceId): string {
        try {
            // Busca fatura
            $invoice = $this->invoiceRepository->findById($invoiceId);
            if (!$invoice) {
                throw new \InvalidArgumentException("Invoice not found");
            }

            // Gera boleto via gateway
            $boletoUrl = $this->paymentGateway->generateBoleto($invoice);

            $this->logger->info('Boleto generated', [
                'invoice_id' => $invoiceId,
                'boleto_url' => $boletoUrl
            ]);

            return $boletoUrl;
        } catch (\Exception $e) {
            $this->logger->error('Boleto generation error', [
                'invoice_id' => $invoiceId,
                'exception' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Verifica status de pagamento
     * @param string $paymentId Identificador do pagamento
     * @return string Status do pagamento
     * @throws \Exception Erro na consulta
     */
    public function checkPaymentStatus(string $paymentId): string {
        try {
            $status = $this->paymentGateway->checkPaymentStatus($paymentId);

            $this->logger->info('Payment status checked', [
                'payment_id' => $paymentId,
                'status' => $status
            ]);

            return $status;
        } catch (\Exception $e) {
            $this->logger->error('Payment status check error', [
                'payment_id' => $paymentId,
                'exception' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Reembolsa um pagamento
     * @param string $paymentId Identificador do pagamento
     * @param float|null $amount Valor a ser reembolsado
     * @return bool Sucesso do reembolso
     * @throws \Exception Erro no reembolso
     */
    public function refundPayment(
        string $paymentId, 
        ?float $amount = null
    ): bool {
        try {
            // Reembolsa via gateway
            $refundResult = $this->paymentGateway->refundPayment($paymentId, $amount);

            $this->logger->info('Payment refunded', [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'result' => $refundResult
            ]);

            return $refundResult;
        } catch (\Exception $e) {
            $this->logger->error('Payment refund error', [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'exception' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Lista métodos de pagamento disponíveis
     * @return array Métodos de pagamento
     */
    public function listAvailablePaymentMethods(): array {
        try {
            $methods = $this->paymentGateway->listAvailablePaymentMethods();

            $this->logger->info('Payment methods listed', [
                'methods' => $methods
            ]);

            return $methods;
        } catch (\Exception $e) {
            $this->logger->error('Payment methods listing error', [
                'exception' => $e->getMessage()
            ]);

            return [];
        }
    }
}
