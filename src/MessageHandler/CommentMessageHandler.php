<?php

namespace App\MessageHandler;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CommentMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SpamChecker $spamChecker,
        private CommentRepository $commentRepository,
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

        // Usar la API de terceros para validar si es spam o no
        if (2 === $this->spamChecker->getSpamScore($comment, $message->getContext())) {
            $comment->setState('spam');
        } else {
            $comment->setState('published');
        }

        // Actualizar la base de datos
        $this->entityManager->flush();
    }
}