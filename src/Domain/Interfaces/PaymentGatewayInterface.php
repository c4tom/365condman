<?php
namespace CondMan\Domain\Interfaces;

use CondMan\Domain\Entities\Payment;
use CondMan\Domain\Entities\Invoice;

interface PaymentGatewayInterface {
    /**
     * Processa pagamento de fatura
     * @param Invoice $invoice Fatura a ser paga
     * @param array $paymentDetails Detalhes do pagamento
     * @return Payment Objeto de pagamento
     * @throws \Exception Erro no processamento do pagamento
     */
    public function processPayment(Invoice $invoice, array $paymentDetails): Payment;

    /**
     * Gera boleto bancário para uma fatura
     * @param Invoice $invoice Fatura para geração de boleto
     * @return string URL ou código de boleto
     * @throws \Exception Erro na geração do boleto
     */
    public function generateBoleto(Invoice $invoice): string;

    /**
     * Verifica status de pagamento
     * @param string $paymentId Identificador do pagamento
     * @return string Status do pagamento
     * @throws \Exception Erro na consulta de status
     */
    public function checkPaymentStatus(string $paymentId): string;

    /**
     * Reembolsa um pagamento
     * @param string $paymentId Identificador do pagamento
     * @param float $amount Valor a ser reembolsado
     * @return bool Sucesso do reembolso
     * @throws \Exception Erro no reembolso
     */
    public function refundPayment(string $paymentId, float $amount = null): bool;

    /**
     * Lista métodos de pagamento disponíveis
     * @return array Métodos de pagamento suportados
     */
    public function listAvailablePaymentMethods(): array;
}
