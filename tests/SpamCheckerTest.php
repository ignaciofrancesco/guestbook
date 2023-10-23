<?php

namespace App\Tests;

use App\Entity\Comment;
use App\SpamChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SpamCheckerTest extends TestCase
{

        
    /**
     * Testear excepcion por api key invalida
     *
     * @return void
     */
    public function testSpamScoreWithInvalidRequest(): void
    {
        //// PREPARACION DE LA ENTRADA PARA EL CONSTRUCTOR DE LA CLASE SPAMCHECKER
        $client = new MockHttpClient([new MockResponse('invalid', ['response_headers' => ['x-akismet-debug-help: Invalid key']])]);
        $checker = new SpamChecker($client, 'test_key');

        //// PREPARACION DE LA ENTRADA PARA EL METODO getSpamScore
        $comment = new Comment();
        $comment->setCreatedAtValue();
        $context = [];

        //// CONFIGURACION DEL TEST
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to check for spam: invalid (Invalid key).');
        
        //// LLAMADO A LA FUNCION A TESTEAR
        $checker->getSpamScore($comment, $context);
    }



        
    /**
     * Testear el camino exitoso con un proveedor de datos (set de datos de prueba)
     *
     * @dataProvider provideComments
     */
    public function testSpamScore(int $expectedScore, ResponseInterface $response, Comment $comment, array $context)
    {
        $client = new MockHttpClient([$response]);
        $checker = new SpamChecker($client, 'test_key');

        $score = $checker->getSpamScore($comment, $context);
        $this->assertSame($expectedScore, $score);
    }
    
    /**
     * DataProvider de todo el contexto necesario para probar comentarios
     *
     * @return iterable
     */
    public static function provideComments(): iterable
    {
        // Crear el comment y el context
        $comment = new Comment();
        $comment->setCreatedAtValue();
        $context = [];

        // Crear una mock response de la API de akismet, para spam
        $response = new MockResponse('', ['response_headers' => ['x-akismet-pro-tip: discard']]);
        // [expectedScore, responseDeLaAPI, comentario, contexto]
        yield 'blatant_spam' => [2, $response, $comment, $context];

        // Crear una mock response de la API de akismet, para posible spam
        $response = new MockResponse('true');
        yield 'spam' => [1, $response, $comment, $context];

        // Crear una mock response de la API de akismet, para no spam
        $response = new MockResponse('false');
        yield 'ham' => [0, $response, $comment, $context];

        /// --> el yield agrega una clave => valor al array devuelto por el metodo
        /// --> cada clave => valor es un set de datos de entrada para realizar un test
    }



}
