<?php

namespace App\MessageHandler;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Notification\CommentReviewNotification;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use App\ImageOptimizer;

#[AsMessageHandler]
class CommentMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SpamChecker $spamChecker,
        private CommentRepository $commentRepository,
        private MessageBusInterface $bus,
        private WorkflowInterface $commentStateMachine,
        private NotifierInterface $notifier,
        private ?LoggerInterface $logger = null,
        private ImageOptimizer $imageOptimizer,
        #[Autowire('%photo_dir%')] private string $photoDir,
    ) {
    }

    // Aca va la logica del manejador del mensaje.
    // El argumento de esta funcion es el que indica qué tipo de Message maneja esta clase
    public function __invoke(CommentMessage $message)
    {
        // Buscar el comentario guardado previamente con state='submitted'
        $comment = $this->commentRepository->find($message->getId());
        if (!$comment) {
            return;
        }

        // Si el estado actual de $comment acepta la transicion 'accept' 
        if ($this->commentStateMachine->can($comment, 'accept'))
        {
                $score = $this->spamChecker->getSpamScore($comment, $message->getContext());

                // Segun el score, asigno la transicion que corresponda
                $transition = match ($score) {
                    2 => 'reject_spam',
                    1 => 'might_be_spam',
                    default => 'accept',
                };

                // Aplico la transicion que corresponda
                $this->commentStateMachine->apply($comment, $transition);

                $this->entityManager->flush();

                // Envio nuevamente el mensaje a la cola, para que pueda continuar la transicion en la maquina de estados
                $this->bus->dispatch($message);

        }
        
        // Si el estado actual de $comment acepta la transicion publish o publish_ham
        elseif ($this->commentStateMachine->can($comment, 'publish') || $this->commentStateMachine->can($comment, 'publish_ham'))
        {

            $this->notifier->send(new CommentReviewNotification($comment), ...$this->notifier->getAdminRecipients());

/*             // Enviar email al admin para que modere
            $this->mailer->send((new NotificationEmail())
                            ->subject('New comment posted')
                            ->htmlTemplate('emails/comment_notification.html.twig')
                            ->from($this->adminEmail)
                            ->to($this->adminEmail)
                            ->context(['comment' => $comment])
            ); */
        }

        elseif ($this->commentStateMachine->can($comment, 'optimize'))
        {
            if ($comment->getPhotoFilename())
            {
                $this->imageOptimizer->resize($this->photoDir.'/'.$comment->getPhotoFilename());
            }
            $this->commentStateMachine->apply($comment, 'optimize');
            $this->entityManager->flush();
        }
        
        elseif ($this->logger)
        {
                $this->logger->debug('Dropping comment message', ['comment' => $comment->getId(), 'state' => $comment->getState()]);
        }
        

        // Actualizar la base de datos
        $this->entityManager->flush();
    }
}