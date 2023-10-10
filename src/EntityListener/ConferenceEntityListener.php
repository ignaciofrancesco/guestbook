<?php

namespace App\EntityListener;

use App\Entity\Conference;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;


// Listener para escuchar eventos relacionado con la entidad Conference (Doctrine listener)
#[AsEntityListener(event: Events::prePersist, entity: Conference::class)]
#[AsEntityListener(event: Events::preUpdate, entity: Conference::class)]
class ConferenceEntityListener
{
    // se inyecta la clase a utilziar en el constructor, y como variable private
    public function __construct(private SluggerInterface $slugger)
    {
    }

    // Antes de crear la entidad por primera vez
    public function prePersist(Conference $conference, LifecycleEventArgs $event)
    {
        // LLamo al metodo de la entidad para computar el slug, y le paso el SluggerInterface que inyecte en el constructor
        $conference->computeSlug($this->slugger);
    }

    // Antes de actualizar
    public function preUpdate(Conference $conference, LifecycleEventArgs $event)
    {
        $conference->computeSlug($this->slugger);
    }


}