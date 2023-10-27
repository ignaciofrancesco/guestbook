<?php

namespace App\Service;

use DateTime;
use InvalidArgumentException;
use NumberFormatter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Filesystem\Exception\ExceptionInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Servicio utilizado para realizar acciones generales de los sistemas symfony del PJ
 * 
 * @author Juani Alarcón <jialarcon@justiciasantafe.gov.ar>
 */
class UtilService
{
    private $_logger;
    private $release;

    public function __construct(LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    public function getRelease()
    {
        return $this->release;
    }

    public function setRelease($release)
    {
        $this->release = $release;
    }

    /**
     * Obtiene la ultima versión del software desde git
     * @return string
     */
    public function getGitInformation(): string
    {
        // Obtengo la raíz donde corre el sistema
        $path = $_ENV['PATH_SISTEMA'];

        // Creo el proceso de git para obtener el ultimo tags
        $process = Process::fromShellCommandline('git describe --tags', $path);
        // Corro el proceso
        $process->run();

        // Si hay alguna falla, logueo el error y retorno la palabra ERROR 
        if (!$process->isSuccessful()) {
            $excepcion = new ProcessFailedException($process);
            $this->_logger->error($excepcion->getMessage());
            return 'error';
        }

        return $process->getOutput();
    }

    /**
     * Exporta un array de strings a un archivo csv.
     * Retorna true si fue exitoso, o false caso contrario.
     * @param array $arrayDeStrings array de strings
     * @param string $pathArchivo path del archivo de salida
     * @param array $encabezado Encabezado del archivo csv (opcional)
     * @return bool $Retorna true si fue exitoso, o false caso contrario
     */
    public function exportarArrayACSV(array $arrayDeStrings, string $pathArchivo, array $encabezado = null): bool
    {
        try {
            // Si no es null, se agrega el encabezado al resultado de la consulta
            if (!is_null($encabezado)) {
                array_unshift($arrayDeStrings, $encabezado);
            }

            // Se crea un serializador para codificar en formato CSV
            $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
            // Se serializa el array resultado de la consulta de BD pasado como parametro, a formato CSV
            $csvData = $serializer->encode($arrayDeStrings, 'csv', [
                'csv_delimiter' => ';', // Se setea el punto y coma como delimitador del archivo csv
                'no_headers' => true, // Se setea para que no incluya el header por defecto, que son las keys del array
            ]);

            // Se escribe en el archivo pasado como parametro
            $filesystem = new Filesystem();
            $filesystem->dumpFile($pathArchivo, $csvData);

            return true;
        } catch (ExceptionInterface | IOException $e) {
            $this->_logger->error('Error al serializar los datos, o al querer escribir en el sistema de archivos.');
            $this->_logger->error($e->getMessage());
            $this->_logger->error($e->getTraceAsString());

            return false;
        }
    }



/**
 * Convierte un array asociativo a formato csv, y lo devuelve como string.
 * @param array $array asociativo
 * @return string devuelve el csv en tipo string
 */
public function arrayToCsv(array $array): string
{
    $output = fopen('php://temp', 'r+');
    
    // Write the header row with keys as column names
    fputcsv($output, array_keys($array[0]), ';');
    
    foreach ($array as $row) {

        // si algun valor es de tipo DateTime, se lo parsea a string
        foreach ($row as &$value) {
            if ($value instanceof \DateTime)
            {
                $value = $value->format('d-m-Y'); 
            }
        }

        // escribo la linea
        fputcsv($output, $row, ';');
    }
    
    rewind($output);

    $csvContent = stream_get_contents($output);
    
    fclose($output);

    return $csvContent;
}


    /**
     * Sanitiza el string pasado como argumento, para hacerlo seguro como nombre de archivo
     * @param string $nombreArchivo nombre del archivo a sanitizar
     * @return string Retorna el nombre del archivo sanitizado
     */
    public function sanitizarNombreArchivo($nombreArchivo): string
    {
        $extensionArchivo = pathinfo($nombreArchivo, PATHINFO_EXTENSION);
        $nombreArchivoString = pathinfo($nombreArchivo, PATHINFO_FILENAME);

        // Replaces all spaces with hyphens. 
        $nombreArchivoString = str_replace(' ', '-', $nombreArchivoString);
        // Removes special chars. 
        $nombreArchivoString = preg_replace('/[^A-Za-z0-9\-\_]/', '', $nombreArchivoString);
        // Replaces multiple hyphens with single one. 
        $nombreArchivoString = preg_replace('/-+/', '-', $nombreArchivoString);

        $nombreArchivoSanitizado = $nombreArchivoString . '.' . $extensionArchivo;

        return $nombreArchivoSanitizado;
    }


    /**
     * Convierte una fecha en numeros a texto
     * @param DateTime $fecha fecha a convertir
     * @return string Retorna la fecha en texto
     */
    public function convertirFechaATexto($fecha): string
    {
        if (!is_a($fecha, 'DateTime'))
        {
            throw new InvalidArgumentException("Error de tipo del parametro.");
        }
        

        $formatterES = new NumberFormatter("es", NumberFormatter::SPELLOUT);

        $diaNumero = date_format($fecha, 'j');
        $diaPalabra = $formatterES->format($diaNumero);

        $mesNumero = date_format($fecha, 'n');
        $mesPalabra = '';

        switch ($mesNumero) {
            case 1:
                $mesPalabra = 'Enero';
                break;
            case 2:
                $mesPalabra = 'Febrero';
                break;
            case 3:
                $mesPalabra = 'Marzo';
                break;
            case 4:
                $mesPalabra = 'Abril';
                break;
            case 5:
                $mesPalabra = 'Mayo';
                break;
            case 6:
                $mesPalabra = 'Junio';
                break;
            case 7:
                $mesPalabra = 'Julio';
                break;
            case 8:
                $mesPalabra = 'Agosto';
                break;
            case 9:
                $mesPalabra = 'Septiembre';
                break;
            case 10:
                $mesPalabra = 'Octubre';
                break;
            case 11:
                $mesPalabra = 'Noviembre';
                break;
            case 12:
                $mesPalabra = 'Diciembre';
        }

        $anioNumero = date_format($fecha, 'Y');
        $anioPalabra = $formatterES->format($anioNumero);

        $fechaTexto = sprintf("$diaPalabra de $mesPalabra de $anioPalabra");

        return $fechaTexto;
    }
}
