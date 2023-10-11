<?php

namespace App\Controller;

use App\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ConferenceRepository;
use App\Entity\Conference;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use App\SpamChecker;

class ConferenceController extends AbstractController
{

    public function __construct (
            private EntityManagerInterface $entityManager,
        )
    {
    }


    #[Route('/', name: 'homepage')]
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        return $this->render('conference/index.html.twig', 
            [
                'conferences' => $conferenceRepository->findAll(),
            ]);
    }


    // PAGINA DE UNA CONFERENCIA EN PARTICULAR.
    // Permite agregar comentarios a la conferencia.
    #[Route('/conference/{slug}', name: 'conference')]
    public function show(
            Request $request,
            Conference $conference,
            CommentRepository $commentRepository,
            SpamChecker $spamChecker,
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

            // Chequeo que no sea spam, antes de ejecutar la actualizacion de la bd
            $context = [
                            'user_ip' => $request->getClientIp(),
                            'user_agent' => $request->headers->get('user-agent'),
                            'referrer' => $request->headers->get('referer'),
                            'permalink' => $request->getUri(),
                        ];

            if (2 === $spamChecker->getSpamScore($comment, $context))
            {
                throw new \RuntimeException('Blatant spam, go away!');
            }

            $this->entityManager->flush();

            // Se redirecciona a la pagina de la conferencia
            return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
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
