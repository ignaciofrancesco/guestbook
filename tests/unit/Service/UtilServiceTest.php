<?php

use App\Service\UtilService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\Attributes\DataProvider;



final class UtilServiceTest extends TestCase
{
    // Tests para Convertir Fecha a Texto
    public function testConvertirFechaATexto(): void
    {
        /// TEST datos 1

        // Crear un mock para la dependencia de UtilService, cuyo constructor espera un logger
        $logger = $this->createMock(LoggerInterface::class);

        // Instanciar la clase a probar
        $utilService = new UtilService($logger);
    
        // Definir fecha a testear
        $fechaInput = DateTime::createFromFormat('Y-m-d', '2023-01-01');

        // Llamar al metodo a testear
        $resultado = $utilService->convertirFechaATexto($fechaInput);

        // Definir resultado esperado
        $resultadoEsperado = "uno de Enero de dos mil veintitrés";

        $this->assertSame($resultadoEsperado, $resultado);

        /// TEST datos 2
    
        // Definir fecha a testear
        $fechaInput = DateTime::createFromFormat('Y-m-d', '2023-12-31');

        // Llamar al metodo a testear
        $resultado = $utilService->convertirFechaATexto($fechaInput);

        // Definir resultado esperado
        $resultadoEsperado = "treinta y uno de Diciembre de dos mil veintitrés";

        $this->assertSame($resultadoEsperado, $resultado);
    }


    // Tests para Convertir Fecha a Texto con data provider
    /**
     * @dataProvider convertirFechaATextoProvider
     */
    public function testConvertirFechaATextoConDataProvider(DateTime $fechaInput, string $resultadoEsperado): void
    {
        /// TEST

        // Crear un mock para la dependencia de UtilService, cuyo constructor espera un logger
        $logger = $this->createMock(LoggerInterface::class);

        // Instanciar la clase a probar
        $utilService = new UtilService($logger);
    
        // Llamar al metodo a testear
        $resultado = $utilService->convertirFechaATexto($fechaInput);

        $this->assertSame($resultadoEsperado, $resultado);
    }


    // Test para exportarArrayACSV - testear output segun expresion regular
    public function testExportarArrayACSV(): void
    {
        // Patron para validar archivos de tipo csv separados con comas
        $regex = '/^("[^"]*"|[^,]*)(,("[^"]*"|[^,]*))*$/';
        $this->expectOutputRegex($regex);

        $arrayDeStrings = 
            [
                "ignacio",
                "vigo",
                "3368428"
            ];
        $pathArchivo = "C:\Users\ifvigo\Desktop";
        $encabezado = null;

        // Crear un mock para la dependencia de UtilService, cuyo constructor espera un logger
        $logger = $this->createMock(LoggerInterface::class);

        // Instanciar la clase a probar
        $utilService = new UtilService($logger);

        // Llamar al metodo a testear
        $resultado = $utilService->exportarArrayACSV($arrayDeStrings, $pathArchivo, $encabezado);
    }


    // Test marcado como incompleto
    public function testGetGitInformation(): void
    {
        $this->markTestIncomplete();
    }


    // Test ejemplo de dependencias

    public function testEmpty(): array
    {
        $stack = [];
        $this->assertEmpty($stack);

        return $stack;
    }

    /**
     * @depends testEmpty
     */
    // Recibe el return de testEmpty
    public function testPush(array $stack): array
    {
        $stack[] = 'foo';
        $this->assertSame('foo', $stack[count($stack) - 1]);
        $this->assertNotEmpty($stack);

        return $stack;
    }

    /**
     * @depends testPush
     */
    // Recibe el return de testPush
    public function testPop(array $stack): void
    {
        $this->assertSame('foo', array_pop($stack));
        $this->assertEmpty($stack);
    }

    //////// DATA PROVIDERS

    // Provee de datos a una funcion de test.
    // La funcion de test se llama tantas veces como sets tenga el array
    public static function convertirFechaATextoProvider(): array
    {
        // Crear array con casos de prueba.
        // Cada array dentro del array, es un set de datos a probar, junto con el valor esperado al final
        $array = 
            [
                'limite inferior' => [DateTime::createFromFormat('Y-m-d', '2023-01-01'), "uno de Enero de dos mil veintitrés"],
                'medio' => [DateTime::createFromFormat('Y-m-d', '2023-12-31'), "treinta y uno de Diciembre de dos mil veintitrés"],
                'limite superior' => [DateTime::createFromFormat('Y-m-d', '2023-07-15'), "quince de Julio de dos mil veintitrés"]
            ];

        return $array;
    }


///// EXCEPTION TESTS

    // Tests para Convertir Fecha a Texto de excepcion
    public function testConvertirFechaATextoThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Crear un mock para la dependencia de UtilService, cuyo constructor espera un logger
        $logger = $this->createMock(LoggerInterface::class);

        // Instanciar la clase a probar
        $utilService = new UtilService($logger);

        // Llamar al metodo a testear
        $resultado = $utilService->convertirFechaATexto("hola");

        // Definir resultado esperado
        $resultadoEsperado = "uno de Enero de dos mil veintitrés";

        $this->assertSame($resultadoEsperado, $resultado);
    }



/* ////// SKIP TESTS

    // Se establece condicion para ejecutar los tests. En este caso, que la extension pgsql este instalada. Si no, se saltean los tests.
    protected function setUp(): void
    {
        if (!extension_loaded('pgsql'))
        {
            $this->markTestSkipped("No está disponible la extensión");
        }
       // $this->markTestSkipped("No está disponible la extensión");

    } */
}