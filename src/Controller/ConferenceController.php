<?php

namespace App\Controller;

use App\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ConferenceRepository;
use App\Entity\Conference;
use App\Form\CommentType;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use App\SpamChecker;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;


class ConferenceController extends AbstractController
{

    public function __construct (
            private EntityManagerInterface $entityManager,
            private MessageBusInterface $bus,
        )
    {
    }

    
    /**
     * HOMEPAGE
     */
    #[Route('/', name: 'homepage')]    
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        return $this->render('conference/index.html.twig', 
            [
                'conferences' => $conferenceRepository->findAll(),
            ])->setSharedMaxAge(3600); // se cachea la home en la cache de los reverse proxies

            // Para cachear en el browser, se usa setMaxAge
            // Sólo usar este método para páginas que no sean dinámicas, puesto que tardaran en actualizarse los datos si se cachea
    }

    /**
     * Conference header: porcion html que lista las conferencias
     */
    #[Route('/conference_header', name: 'conference_header')]
    public function conferenceHeader(ConferenceRepository $conferenceRepository): Response
    {
        // Devuelvo una porcion html que liste las conferencias
        return $this->render('conference/header.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ])->setSharedMaxAge(3600);
    }



    // PAGINA DE UNA CONFERENCIA EN PARTICULAR.
    // Permite agregar comentarios a la conferencia.
    #[Route('/conference/{slug}', name: 'conference')]
    public function show(
            Request $request,
            Conference $conference,
            CommentRepository $commentRepository,
            NotifierInterface $notifier,
            #[Autowire('%photo_dir%')] string $photoDir,
        ) : Response
    {
        // $comments = $commentRepository->findBy(['conference' => $conference], ['createdAt' => 'DESC']);

        // Crear un comentario que se va a pasar al formulario
        $comment = new Comment();
        // Crear el formulario, y pasarle el comentario creado (comentario vacio hasta ahora)
        $form = $this->createForm(CommentType::class, $comment);

        // Verifica la $request, y chequea si el form fue submitted. Si es asi, lo setea como submitted.
        $form->handleRequest($request);

        // Si el form fue enviado y es valido, entra por este camino especial
        if ($form->isSubmitted() && $form->isValid())
        {
            // Fuerzo que el comentario pertenezca a la actua conferencia
            $comment->setConference($conference);

            if ($photo = $form['photo']->getData())
            {
                // Crear un nombre random para el archivo
                $filename = bin2hex(random_bytes(6)) . '.' . $photo->guessExtension();
                // Mover el archivo a su carpeta destino (el archivo esta eh $photo)
                $photo->move($photoDir, $filename);

                // Setear el filename en el comentario
                $comment->setPhotoFilename($filename);
            }

            // Guardo el comment para persistir
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            // Crear el contexto para llamar a la API que chequea spam
            $context = [
                            'user_ip' => $request->getClientIp(),
                            'user_agent' => $request->headers->get('user-agent'),
                            'referrer' => $request->headers->get('referer'),
                            'permalink' => $request->getUri(),
                        ];

            // Mandar un mensaje de tipo CommentMessage al Messenger, para que gestione la validacion antispam en paralelo
            // Esto lo que hace es permitir el manejo asincrono del procesamiento del comentario en el back
            $this->bus->dispatch(new CommentMessage($comment->getId(), $context));

            // Inmediatamente se notifica al usuario que el comentario sera moderado
            $notifier->send(new Notification('Thank you for the feedback; your comment will be posted after moderation.', ['browser']));

            // Se redirecciona a la pagina de la conferencia
            return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }

        // Entra solo si el form fue enviado, pero no es valido
        if ($form->isSubmitted()) {
            $notifier->send(new Notification('Can you check your submission? There are some problems with it.', ['browser']));
        }

        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $commentRepository->getCommentPaginator($conference, $offset);

        return $this->render('conference/show.html.twig',
            [
                'conference' => $conference,
                'comments' => $paginator,
                'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
                'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
                'comment_form' => $form
            ]);
    }

}
