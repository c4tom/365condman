<?php
namespace CondMan\Domain\Validators;

use CondMan\Domain\Interfaces\CommunicationInterface;

class CommunicationValidator {
    private CommunicationInterface $communication;

    public function __construct(CommunicationInterface $communication) {
        $this->communication = $communication;
    }

    public function validate(): bool {
        return $this->validateCondominiumId() && 
               $this->validateChannel() && 
               $this->validateRecipient() && 
               $this->validateContent() && 
               $this->validateStatus();
    }

    private function validateCondominiumId(): bool {
        return $this->communication->getCondominiumId() > 0;
    }

    private function validateChannel(): bool {
        $validChannels = ['email', 'sms', 'whatsapp', 'push_notification'];
        return in_array($this->communication->getChannel(), $validChannels);
    }

    private function validateRecipient(): bool {
        $recipient = $this->communication->getRecipient();
        $channel = $this->communication->getChannel();

        switch ($channel) {
            case 'email':
                return filter_var($recipient, FILTER_VALIDATE_EMAIL) !== false;
            case 'sms':
                return preg_match('/^\+?[1-9]\d{1,14}$/', $recipient);
            case 'whatsapp':
                return preg_match('/^\+?[1-9]\d{1,14}$/', $recipient);
            default:
                return !empty($recipient);
        }
    }

    private function validateContent(): bool {
        $content = $this->communication->getContent();
        return !empty($content) && 
               mb_strlen($content) >= 5 && 
               mb_strlen($content) <= 5000;
    }

    private function validateStatus(): bool {
        $validStatuses = ['pending', 'sent', 'failed', 'read'];
        return in_array($this->communication->getStatus(), $validStatuses);
    }
}
