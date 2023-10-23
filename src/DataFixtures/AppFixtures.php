<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\Comment;
use App\Entity\Conference;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AppFixtures extends Fixture
{

    public function __construct(
        private PasswordHasherFactoryInterface $passwordHasherFactory,
    )
    {

    }

        
    /**
     * Carga los datos (fixtures) para las pruebas
     * 
     * Se usa el comando: symfony console doctrine:fixtures:load --env=test
     *
     * @param  mixed $manager
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product)
        
        // Creao conferencias, y las persisto
        $amsterdam = new Conference();
        $amsterdam->setCity('Amsterdam');
        $amsterdam->setYear('2019');
        $amsterdam->setIsInternational(true);
        $manager->persist($amsterdam);

        $paris = new Conference();
        $paris->setCity('Paris');
        $paris->setYear('2019');
        $paris->setIsInternational(false);
        $manager->persist($paris);

        // Creo comentarios y los persisto
        $comment1 = new Comment();
        $comment1->setConference($amsterdam);
        $comment1->setAuthor('Fabien');
        $comment1->setEmail('fabien@example.com');
        $comment1->setText('This was a great conference.');
        $comment1->setState('published');
        $manager->persist($comment1);

        $comment2 = new Comment();
        $comment2->setConference($amsterdam);
        $comment2->setAuthor('Lucas');
        $comment2->setEmail('lucas@example.com');
        $comment2->setText('I think this one is going to be moderated.');
        $manager->persist($comment2);

        // Creo el usuario admin
        $admin = new Admin();
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setUsername('admin');
        $admin->setPassword($this->passwordHasherFactory->getPasswordHasher(Admin::class)->hash('admin'));
        $manager->persist($admin);

        $manager->flush();
    }
}
