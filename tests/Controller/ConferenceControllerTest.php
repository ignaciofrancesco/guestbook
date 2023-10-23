<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Panther\PantherTestCase;

// Como vamos a testear un controlador, esta clase debe tener una vision superior a la clase controladora a testear, para poder manipularla.
// class ConferenceControllerTest extends WebTestCase
class ConferenceControllerTest extends PantherTestCase
{
        
    /**
     * Testear acceso a la home
     *
     * @return void
     */
    public function testIndex(): void
    {
        // Crea un Browser
        //$client = static::createClient();

        // Crear un browser real
        $client = static::createPantherClient(['external_base_uri' => rtrim($_SERVER['SYMFONY_PROJECT_DEFAULT_ROUTE_URL'], '/')]);

        // Hace una peticion GET.
        // Se usa una URL hardcodeada, y no se la genera dinamicamente, para recordar que los search engines y ciertas paginas pueden tdvia linkear a la vieja url
        $client->request('GET', '/');

        // Se testea resultado. Devuelve un 200?
        $this->assertResponseIsSuccessful();
        // Se chequea contenido devuelto
        $this->assertSelectorTextContains('h2', 'Give your feedback');
    }

        
    /**
     * Testear acceso a una conferencia
     *
     * @return void
     */
    public function testConferencePage(): void
    {
        $client = static::createClient();
        // Se llama a una URI, y devuelve un crawler que permite encontrar elementos en la pagina usando selectores CSS
        $crawler = $client->request('GET', '/');

        // Testeo que este en una pagina con dos h4
        $this->assertCount(2, $crawler->filter('h4'));

        // Hago click en el primer link cuyo texto sea "View"
        $client->clickLink('View');

        // Testeo que este en la pagina show de la primera conferencia cargada
        $this->assertPageTitleContains('Amsterdam');
        // Testeo que la Response sea exitosa
        $this->assertResponseIsSuccessful();
        // Testeo que el elemento h2 contenga este texto
        $this->assertSelectorTextContains('h2', 'Amsterdam 2019');
        $this->assertSelectorExists('div:contains("There are 1 comments")');
    }

    
    /**
     * Testear enviar un nuevo comentario, a traves de un form submission
     *
     * @return void
     */
    public function testCommentSubmission(): void
    {
        $client = static::createClient();
       // $client = HttpClient::create(['verify_peer' => false]);

        $client->request('GET', '/conference/amsterdam-2019');
        $client->submitForm('Submit', [
            'comment[author]' => 'Fabien',
            'comment[text]' => 'Some feedback from an automated functional test',
            'comment[email]' => $email = 'me@automat.ed',
            'comment[photo]' => dirname(__DIR__, 2).'/public/images/under-construction.gif',
        ]);

        // Testeo que redirija
        $this->assertResponseRedirects();

        // Simular validacion del comentario (se simula que el comentario fue validado, y por lo tanto se setea su estado a published)
        $comment = self::getContainer()->get(CommentRepository::class)->findOneByEmail($email);
        $comment->setState('published');
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        // Le digo al browser que siga la redireccion
        $client->followRedirect();
        // Testeo que en la pagina de la conferencia, ahora existan dos comentarios
        $this->assertSelectorExists('div:contains("There are 2 comments")');
    }

}