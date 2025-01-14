<?php
namespace CondMan\Domain\Transformers;

use CondMan\Domain\Interfaces\CommunicationInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class CommunicationTransformer {
    private CommunicationInterface $communication;
    private Environment $twigEnvironment;

    public function __construct(CommunicationInterface $communication) {
        $this->communication = $communication;
        $loader = new ArrayLoader();
        $this->twigEnvironment = new Environment($loader);
    }

    /**
     * Traduz o canal de comunicação
     * @return string Canal traduzido
     */
    public function translateChannel(): string {
        $translations = [
            'email' => 'E-mail',
            'sms' => 'SMS',
            'whatsapp' => 'WhatsApp',
            'push_notification' => 'Notificação Push'
        ];
        
        return $translations[$this->communication->getChannel()] ?? $this->communication->getChannel();
    }

    /**
     * Traduz o status da comunicação
     * @return string Status traduzido
     */
    public function translateStatus(): string {
        $translations = [
            'pending' => 'Pendente',
            'sent' => 'Enviado',
            'failed' => 'Falha',
            'read' => 'Lido'
        ];
        
        return $translations[$this->communication->getStatus()] ?? $this->communication->getStatus();
    }

    /**
     * Renderiza o conteúdo da comunicação com template
     * @param array $context Contexto para renderização
     * @return string Conteúdo renderizado
     */
    public function renderContent(array $context = []): string {
        $content = $this->communication->getContent();
        
        try {
            return $this->twigEnvironment->createTemplate($content)->render($context);
        } catch (\Exception $e) {
            return $content;
        }
    }

    /**
     * Mascara o destinatário
     * @return string Destinatário mascarado
     */
    public function maskRecipient(): string {
        $recipient = $this->communication->getRecipient();
        $channel = $this->communication->getChannel();
        
        switch ($channel) {
            case 'email':
                return $this->maskEmail($recipient);
            case 'sms':
            case 'whatsapp':
                return $this->maskPhone($recipient);
            default:
                return $recipient;
        }
    }

    /**
     * Máscara de email
     * @param string $email E-mail a ser mascarado
     * @return string E-mail mascarado
     */
    private function maskEmail(string $email): string {
        list($username, $domain) = explode('@', $email);
        
        $usernameLength = strlen($username);
        $maskedUsername = substr($username, 0, 2) . 
                          str_repeat('*', max(0, $usernameLength - 2));
        
        return $maskedUsername . '@' . $domain;
    }

    /**
     * Máscara de telefone
     * @param string $phone Telefone a ser mascarado
     * @return string Telefone mascarado
     */
    private function maskPhone(string $phone): string {
        $phoneLength = strlen($phone);
        return substr($phone, 0, 3) . 
               str_repeat('*', max(0, $phoneLength - 6)) . 
               substr($phone, -3);
    }

    /**
     * Gera um resumo da comunicação
     * @return array Resumo da comunicação
     */
    public function generateSummary(): array {
        return [
            'channel' => $this->translateChannel(),
            'recipient' => $this->maskRecipient(),
            'status' => $this->translateStatus(),
            'subject' => $this->communication->getSubject(),
            'additional_data' => $this->communication->getAdditionalData()
        ];
    }
}
