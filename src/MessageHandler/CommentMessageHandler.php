<?php

namespace App\MessageHandler;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler]
class CommentMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SpamChecker $spamChecker,
        private CommentRepository $commentRepository,
        private MessageBusInterface $bus,
        private WorkflowInterface $commentStateMachine,
        private ?LoggerInterface $logger = null,
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

/*         // Usar la API de terceros para validar si es spam o no
        if (2 === $this->spamChecker->getSpamScore($comment, $message->getContext())) {
            $comment->setState('spam');
        } else {
            $comment->setState('published');
        } */

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
        
        elseif ($this->commentStateMachine->can($comment, 'publish') || $this->commentStateMachine->can($comment, 'publish_ham'))
        {
                $this->commentStateMachine->apply($comment, $this->commentStateMachine->can($comment, 'publish') ? 'publish' : 'publish_ham');
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