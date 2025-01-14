<?php
namespace CondMan\Domain\Services;

use CondMan\Domain\Interfaces\NotificationInterface;
use CondMan\Domain\Interfaces\LoggerInterface;
use CondMan\Domain\Entities\Occurrence;
use CondMan\Domain\Repositories\OccurrenceRepositoryInterface;
use DateTime;

class NotificationService {
    private NotificationInterface $notificationHandler;
    private LoggerInterface $logger;
    private OccurrenceRepositoryInterface $occurrenceRepository;

    public function __construct(
        NotificationInterface $notificationHandler,
        LoggerInterface $logger,
        OccurrenceRepositoryInterface $occurrenceRepository
    ) {
        $this->notificationHandler = $notificationHandler;
        $this->logger = $logger;
        $this->occurrenceRepository = $occurrenceRepository;
    }

    /**
     * Notifica criação de nova ocorrência
     */
    public function notifyOccurrenceCreation(Occurrence $occurrence): bool {
        $message = $this->formatOccurrenceMessage(
            'Nova Ocorrência Registrada', 
            $occurrence, 
            'criação'
        );

        return $this->sendNotification(
            $this->getRecipientForOccurrence($occurrence),
            $message,
            ['occurrence_id' => $occurrence->getId()]
        );
    }

    /**
     * Notifica atribuição de ocorrência
     */
    public function notifyOccurrenceAssignment(Occurrence $occurrence): bool {
        $message = $this->formatOccurrenceMessage(
            'Ocorrência Atribuída', 
            $occurrence, 
            'atribuição'
        );

        return $this->sendNotification(
            $this->getRecipientForOccurrence($occurrence, true),
            $message,
            ['occurrence_id' => $occurrence->getId()]
        );
    }

    /**
     * Notifica resolução de ocorrência
     */
    public function notifyOccurrenceResolution(Occurrence $occurrence): bool {
        $message = $this->formatOccurrenceMessage(
            'Ocorrência Resolvida', 
            $occurrence, 
            'resolução'
        );

        return $this->sendNotification(
            $this->getRecipientForOccurrence($occurrence),
            $message,
            ['occurrence_id' => $occurrence->getId()]
        );
    }

    /**
     * Envia notificação de prazo de ocorrência próximo
     */
    public function notifyOccurrenceDueDateApproaching(Occurrence $occurrence, DateTime $dueDate): bool {
        $message = $this->formatOccurrenceMessage(
            'Prazo de Ocorrência se Aproximando', 
            $occurrence, 
            'prazo',
            $dueDate
        );

        return $this->sendNotification(
            $this->getRecipientForOccurrence($occurrence, true),
            $message,
            [
                'occurrence_id' => $occurrence->getId(),
                'due_date' => $dueDate->format('Y-m-d H:i:s')
            ]
        );
    }

    /**
     * Formata mensagem de notificação de ocorrência
     */
    private function formatOccurrenceMessage(
        string $title, 
        Occurrence $occurrence, 
        string $actionType,
        ?DateTime $additionalDate = null
    ): string {
        $details = [
            'Título' => $occurrence->getTitle(),
            'Categoria' => $occurrence->getCategory(),
            'Status' => $occurrence->getStatus(),
            'Descrição' => $occurrence->getDescription()
        ];

        if ($additionalDate) {
            $details['Data Limite'] = $additionalDate->format('d/m/Y H:i');
        }

        $formattedDetails = implode("\n", array_map(
            fn($key, $value) => "{$key}: {$value}", 
            array_keys($details), 
            $details
        ));

        return sprintf(
            "*%s*\n\nUma ocorrência foi %s:\n\n%s",
            $title,
            $actionType,
            $formattedDetails
        );
    }

    /**
     * Obtém destinatário para notificação de ocorrência
     */
    private function getRecipientForOccurrence(
        Occurrence $occurrence, 
        bool $getAssignedUser = false
    ): string {
        // Lógica de recuperação de destinatário
        // Pode ser um e-mail, ID de usuário, ou outro identificador
        // No momento, retornará um placeholder
        return $getAssignedUser && $occurrence->getAssignedToId() 
            ? "user_{$occurrence->getAssignedToId()}"
            : "user_{$occurrence->getReporterId()}";
    }

    /**
     * Envia notificação
     */
    private function sendNotification(
        string $recipient, 
        string $message, 
        array $context = []
    ): bool {
        try {
            $status = $this->notificationHandler->send($recipient, $message, $context);
            
            $this->notificationHandler->log($recipient, $message, $status);
            
            $this->logger->info('Notificação enviada', [
                'recipient' => $recipient,
                'context' => $context,
                'status' => $status
            ]);

            return $status;
        } catch (\Exception $e) {
            $this->logger->error('Erro ao enviar notificação', [
                'recipient' => $recipient,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
