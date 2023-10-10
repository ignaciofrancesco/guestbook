<?php

namespace App\EventSubscriber;

use App\Repository\ConferenceRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;

class TwigEventSubscriber implements EventSubscriberInterface
{
    private $twig;
    private $conferenceRepository;

    public function __construct(Environment $twig, ConferenceRepository $conferenceRepository)
    {
        $this->twig = $twig;
        $this->conferenceRepository = $conferenceRepository;
    }


    // Codigo a ejecutar cuando se recibe un evento suscripto (en este caso, un ControlleEvent)
    public function onControllerEvent(ControllerEvent $event): void
    {
        // se agrega como variable global de twig, a la lista de conferencias
        $this->twig->addGlobal('conferences', $this->conferenceRepository->findAll());
    }

    
    // EVENTOS A LOS QUE SE SUSCRIBE ESTE SUBSCRIBER
    public static function getSubscribedEvents(): array
    {
        return [
            // Evento que se dispara justo antes de ejecutar cualquier controlador
            ControllerEvent::class => 'onControllerEvent',
        ];
    }
}
