<?php

namespace Axm\Publish;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Exception;


class Publisher
{
    protected $destination;
    protected $errors = [];
    protected $published = [];
    protected $bufferSize = 4096; // Tamaño del búfer en bytes
    protected $openFiles = [];   // Almacena los recursos abiertos
    protected $discovered;
    protected $source = ROOT_PATH;

    
    public function __construct($source, $destination)
    {
        $this->source = $source;
        $this->destination = $destination;
    }

    /**
     * Escanea los archivos de un directorio.
     *
     * @param string $dir El directorio a escanear.
     * @return array Una lista de rutas a los archivos encontrados.
     */
    protected function scanFiles($dir)
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Descubre y devuelve todas las clases en el directorio especificado.
     *
     * @param string $directory El directorio a escanear.
     * @return array Un array de instancias de clases encontradas.
     */
    public function discover($directory = 'Publishers')
    {
        // Verifica si ya se han descubierto las clases en este directorio y las almacena en caché.
        if (isset($this->discovered[$directory])) {
            return $this->discovered[$directory];
        }

        $this->discovered[$directory] = [];

        // Obtiene la lista de archivos en el directorio utilizando la función nativa de PHP `scandir`.
        $directoryPath = $this->source . $directory;
        $files = $this->scanFiles($directoryPath);

        // Recorre los archivos para verificar si son clases y si son subclases de Publisher.
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && is_file($directory . '/' . $file)) {
                // Convierte el nombre de archivo en un nombre de clase potencial.
                $className = pathinfo($file, PATHINFO_FILENAME);

                // Comprueba si la clase existe y es una subclase de Publisher.
                if (class_exists($className) && is_subclass_of($className, 'Axm\\Publish\\Publisher')) {
                    // Crea una instancia de la clase y la almacena en el array.
                    $this->discovered[$directory][] = new $className();
                }
            }
        }

        return $this->discovered[$directory];
    }

    /**
     * Merges all files into the destination.
     * Creates a mirrored directory structure only for files from source.
     *
     * @param bool $replace Whether to overwrite existing files.
     *
     * @return bool Whether all files were copied successfully
     */
    public function merge(bool $replace = true): bool
    {
        $this->errors = $this->published = [];

        // Get the files from source for special handling
        $sourced = $this->filterFiles($this->get(), $this->source);

        // Handle everything else with a flat copy
        $this->files = array_diff($this->files, $sourced);
        $this->copy($replace);

        // Copy each sourced file to its relative destination
        foreach ($sourced as $file) {
            // Resolve the destination path
            $to = $this->destination . substr($file, strlen($this->source));

            try {
                $this->safeCopyFile($file, $to, $replace);
                $this->published[] = $to;
            } catch (Exception $e) {
                $this->errors[$file] = $e;
            }
        }

        return empty($this->errors);
    }

    /**
     * Filter files based on the source directory.
     *
     * @param array $files   An array of file paths to filter.
     * @param string $source The source directory.
     * @return array An array of filtered file paths.
     */
    protected function filterFiles(array $files, string $source): array
    {
        $filteredFiles = [];

        foreach ($files as $file) {
            if (strpos($file, $source) === 0) {
                $filteredFiles[] = $file;
            }
        }

        return $filteredFiles;
    }

    /**
     * Copia todos los archivos de la fuente al destino.
     *
     * @return bool `true` si todos los archivos se han copiado con éxito, o `false` si hay algún error.
     */
    public function copy()
    {
        $this->errors = $this->published = [];

        if (!is_dir($this->source) || !is_dir($this->destination)) {
            return false;
        }

        $files = $this->scanFiles($this->source);

        foreach ($files as $file) {
            $to = $this->destination . '/' . basename($file);
            try {
                if ($this->safeCopyFile($file, $to)) {
                    $this->published[] = $to;
                }
            } catch (Exception $e) {
                $this->errors[$file] = $e;
            }
        }

        return empty($this->errors);
    }

    /**
     * Devuelve una lista de errores que ocurrieron durante la última operación de copia.
     *
     * @return array Una lista de errores, o una lista vacía si no hay errores.
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Devuelve una lista de rutas a los archivos que se copiaron durante la última operación de copia.
     *
     * @return array Una lista de rutas a los archivos, o una lista vacía si no se copiaron archivos.
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * Elimina un archivo en la carpeta de destino.
     *
     * @param string $filename El nombre del archivo a eliminar.
     * @return bool `true` si el archivo se eliminó con éxito, o `false` si hubo un error.
     */
    public function deleteFile($filename)
    {
        $targetFile = $this->destination . '/' . $filename;

        if (file_exists($targetFile) && is_file($targetFile)) {
            return unlink($targetFile);
        }

        return false;
    }

    /**
     * Elimina archivos no utilizados en la carpeta de destino.
     *
     * @return bool `true` si se eliminaron los archivos con éxito, o `false` si hubo un error.
     */
    public function deleteUnusedFiles()
    {
        $this->errors = $this->published = [];

        $sourceFiles = $this->scanFiles($this->source);
        $targetFiles = $this->scanFiles($this->destination);

        $unusedFiles = array_diff($targetFiles, $sourceFiles);

        foreach ($unusedFiles as $file) {
            if (!$this->deleteFile(basename($file))) {
                $this->errors[$file] = new Exception("Error deleting file: $file");
            }
        }

        return empty($this->errors);
    }

    /**
     * Copia archivos de forma recursiva.
     *
     * @param bool $replace Si se debe reemplazar el archivo de destino si existe.
     * @return bool `true` si los archivos se copiaron con éxito, o `false` si hubo un error.
     */
    public function copyRecursive($replace = true)
    {
        $this->errors = $this->published = [];

        if (!is_dir($this->source) || !is_dir($this->destination)) {
            return false;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->source), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $sourceFile = $file->getPathname();
                $relativePath = str_replace($this->source, '', $sourceFile);
                $targetFile = $this->destination . $relativePath;

                try {
                    $this->safeCopyFile($sourceFile, $targetFile, $replace);
                    $this->published[] = $targetFile;
                } catch (Exception $e) {
                    $this->errors[$sourceFile] = $e;
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Verifica si un archivo existe en la carpeta de destino.
     *
     * @param string $filename El nombre del archivo a verificar.
     * @return bool `true` si el archivo existe en la carpeta de destino, o `false` en caso contrario.
     */
    public function fileExistsInDestination($filename)
    {
        return file_exists($this->destination . '/' . $filename);
    }

    /**
     * Copia un archivo de forma segura utilizando un búfer.
     *
     * @param string $from    La ruta al archivo que se va a copiar.
     * @param string $to      La ruta al archivo de destino.
     * @param bool   $replace Si se debe reemplazar el archivo de destino si existe.
     * @return bool `true` si el archivo se ha copiado con éxito, o `false` si hay algún error.
     */
    protected function safeCopyFile($from, $to, $replace = false)
    {
        if (file_exists($to)) {
            if (!$replace) {
                return false;
            }

            if (!unlink($to)) {
                throw new Exception("Error deleting existing file: $to");
            }
        }

        $toDirectory = pathinfo($to, PATHINFO_DIRNAME);

        if (!is_dir($toDirectory)) {
            mkdir($toDirectory, 0777, true);
        }

        $sourceFile = fopen($from, 'rb');
        $targetFile = fopen($to, 'wb');

        if ($sourceFile && $targetFile) {
            while (!feof($sourceFile)) {
                fwrite($targetFile, fread($sourceFile, $this->bufferSize));
            }

            fclose($sourceFile);
            fclose($targetFile);

            // Guardar los recursos abiertos
            $this->openFiles[] = $sourceFile;
            $this->openFiles[] = $targetFile;

            return true;
        }

        return false;
    }

    /**
     * Destructor de la clase para cerrar recursos.
     */
    public function __destruct()
    {
        // Cerrar todos los recursos abiertos
        foreach ($this->openFiles as $file) {
            fclose($file);
        }
    }
}
