<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Message\CommentMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;
use Twig\Environment;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[Route('/admin')]
class AdminController extends AbstractController
{
    public function __construct(
        private Environment $twig,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus,
    ) {
    }

    /**
     * Gestiona la revision de comentarios.
     * Se accede mediante los botons que se envian por correo al admin para aceptar o rechazar un comentario.
     * No se accede de manera directa a traves del sitio web, sino a modo de servicio (endpoint)
     */
    #[Route('/comment/review/{id}', name: 'review_comment')]
    public function reviewComment(Request $request, Comment $comment, WorkflowInterface $commentStateMachine): Response
    {
        $accepted = !$request->query->get('reject');

        if ($commentStateMachine->can($comment, 'publish'))
        {
            $transition = $accepted ? 'publish' : 'reject';
        }
        elseif ($commentStateMachine->can($comment, 'publish_ham'))
        {
            $transition = $accepted ? 'publish_ham' : 'reject_ham';
        }
        else
        {
            return new Response('Comment already reviewed or not in the right state.');
        }

        $commentStateMachine->apply($comment, $transition);
        $this->entityManager->flush();

        if ($accepted) {
            $this->bus->dispatch(new CommentMessage($comment->getId()));
        }

        return new Response($this->twig->render('admin/review.html.twig', [
            'transition' => $transition,
            'comment' => $comment,
        ]));
    }


    /**
     * Purga la cache de la url que se le pase como parametro.
     * Solo accesible para admins.
     * Metodo HTTP de tipo PURGE.
     */
    #[Route('/http-cache/{uri<.*>}', methods: ['PURGE'])]
    public function purgeHttpCache(KernelInterface $kernel, Request $request, string $uri, StoreInterface $store): Response
    {
        // Si es entorno produccion, se retorna un 400 (bad request)
        if ('prod' == $kernel->getEnvironment())
        {
            return new Response('KO', 400);
        }

        // Purga la cache de la url indicada.
        // El parametro pasado por url machea con el parametro del metodo $uri, que es la url a purgar
        $store->purge($request->getSchemeAndHttpHost().'/'.$uri);

        return new Response('Done');
    }

}