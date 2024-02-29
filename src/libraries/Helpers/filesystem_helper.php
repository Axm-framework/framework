<?php

if (!function_exists('directoryMap')) {
    /**
     * Genera una matriz de archivos y directorios dentro de un directorio dado.
     *
     * @param string $sourceDir Directorio de origen.
     * @param int $directoryDepth (Opcional) Profundidad máxima de directorios a explorar. Por defecto es 0 (exploración sin límite).
     * @param bool $hidden (Opcional) Indica si se incluirán archivos ocultos. Por defecto es false (no se incluyen).
     * @return array Matriz de archivos y directorios dentro del directorio dado.
     */
    function directoryMap(string $sourceDir, int $directoryDepth = 0, bool $hidden = false): array
    {
        try {
            // Eliminar el separador de directorio final si existe
            $sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            // Crear un iterador recursivo para explorar los archivos y directorios
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            // Matriz para almacenar los archivos y directorios
            $fileData = [];

            // Variable para realizar un seguimiento de la profundidad actual del directorio
            $depth = 0;

            // Iterar sobre los archivos y directorios
            foreach ($iterator as $path => $fileInfo) {
                // Saltar archivos ocultos si no se incluyen
                if (!$hidden && $fileInfo->isHidden()) {
                    continue;
                }

                // Agregar el separador de directorio al final si es un directorio
                if ($fileInfo->isDir()) {
                    $path .= DIRECTORY_SEPARATOR;
                }

                // Comprobar si se ha alcanzado la profundidad máxima del directorio
                if ($directoryDepth === 0 || $depth < $directoryDepth) {
                    // Agregar el archivo o directorio a la matriz
                    $fileData[$path] = $fileInfo->isDir() ? [] : $fileInfo->getFilename();
                } else {
                    // Salir del bucle si se alcanza la profundidad máxima
                    break;
                }

                // Actualizar la profundidad actual del directorio
                $depth = $iterator->getDepth();
            }

            // Devolver la matriz de archivos y directorios
            return $fileData;
        } catch (Throwable $e) {
            // En caso de error, devolver una matriz vacía
            return [];
        }
    }
}

if (!function_exists('directoryMirror')) {
    /**
     * Copia recursivamente los archivos y directorios desde el directorio de origen al directorio de destino.
     *
     * @param string $originDir Directorio de origen.
     * @param string $targetDir Directorio de destino.
     * @param bool $overwrite (Opcional) Indica si se sobrescribirán los archivos existentes en el directorio de destino. Por defecto es true.
     * @throws InvalidArgumentException Si el directorio de origen no existe.
     * @return void
     */
    function directoryMirror(string $originDir, string $targetDir, bool $overwrite = true): void
    {
        // Comprobar si el directorio de origen existe
        if (!is_dir($originDir = rtrim($originDir, '\\/'))) {
            throw new InvalidArgumentException(sprintf('El directorio de origen "%s" no se encontró.', $originDir));
        }

        // Crear el directorio de destino si no existe
        if (!is_dir($targetDir = rtrim($targetDir, '\\/'))) {
            @mkdir($targetDir, 0755, true);
        }

        // Longitud del directorio de origen
        $dirLen = strlen($originDir);

        // Iterar sobre los archivos y directorios en el directorio de origen
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($originDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        ) as $file) {
            $origin = $file->getPathname();
            $target = $targetDir . substr($origin, $dirLen);

            // Comprobar si es un directorio
            if ($file->isDir()) {
                // Crear el directorio en el directorio de destino si no existe
                if (!is_dir($target)) {
                    mkdir($target, 0755);
                }
            } else {
                // Comprobar si el archivo no existe en el directorio de destino o si se permite sobrescribir
                if (!is_file($target) || ($overwrite && is_file($target))) {
                    // Copiar el archivo al directorio de destino
                    copy($origin, $target);
                }
            }
        }
    }
}

if (!function_exists('writeFile')) {
    /**
     * Escribe datos en un archivo.
     *
     * @param string $path Ruta del archivo.
     * @param string $data Datos a escribir en el archivo.
     * @param string $mode (Opcional) Modo de apertura del archivo. Por defecto es 'wb'.
     * @return bool True si se escribió correctamente, false en caso contrario.
     */
    function writeFile(string $path, string $data, string $mode = 'wb'): bool
    {
        try {
            $fp = fopen($path, $mode);

            if (!$fp) {
                throw new RuntimeException(sprintf('No se pudo abrir el archivo "%s".', $path));
            }

            if (flock($fp, LOCK_EX)) {
                $result = fwrite($fp, $data);
                flock($fp, LOCK_UN);
            } else {
                throw new RuntimeException(sprintf('No se pudo bloquear el archivo "%s".', $path));
            }

            fclose($fp);

            return $result !== false;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('deleteFiles')) {
    /**
     * Elimina archivos y directorios de manera recursiva.
     *
     * @param string $path Ruta del directorio o archivo a eliminar.
     * @param bool $delDir (Opcional) Indica si se deben eliminar los directorios. Por defecto es false.
     * @param bool $htdocs (Opcional) Indica si se deben excluir los archivos especiales de htdocs. Por defecto es false.
     * @param bool $hidden (Opcional) Indica si se deben excluir los archivos ocultos. Por defecto es false.
     * @return bool True si se eliminaron los archivos y directorios correctamente, false en caso contrario.
     */
    function deleteFiles(string $path, bool $delDir = false, bool $htdocs = false, bool $hidden = false): bool
    {
        $path = realpath($path) ?: $path;
        $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $object) {
                $filename = $object->getFilename();

                if (!$hidden && $filename[0] === '.') {
                    continue;
                }

                if (!$htdocs || !preg_match('/^(\.htaccess|index\.(html|htm|php)|web\.config)$/i', $filename)) {
                    if ($object->isDir() && $delDir) {
                        rmdir($object->getPathname());
                    } else {
                        unlink($object->getPathname());
                    }
                }
            }

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('getFileNames')) {
    /**
     * Obtiene los nombres de archivo de un directorio y sus subdirectorios de manera recursiva.
     *
     * @param string $sourceDir Ruta del directorio fuente.
     * @param bool|null $includePath (Opcional) Indica si se debe incluir la ruta completa de los archivos.
     * false para solo incluir el nombre de archivo, null para incluir la ruta relativa
     * al directorio fuente y true para incluir la ruta completa.
     * Por defecto es false.
     * @param bool $hidden (Opcional) Indica si se deben incluir los archivos ocultos. Por defecto es false.
     * @return array Un array con los nombres de archivo.
     */
    function getFileNames(string $sourceDir, ?bool $includePath = false, bool $hidden = false): array
    {
        $files = [];

        $sourceDir = realpath($sourceDir) ?: $sourceDir;
        $sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $name => $object) {
                $basename = pathinfo($name, PATHINFO_BASENAME);

                if (!$hidden && $basename[0] === '.') {
                    continue;
                }

                if ($includePath === false) {
                    $files[] = $basename;
                } elseif ($includePath === null) {
                    $relativePath = str_replace($sourceDir, '', $name);
                    $files[] = ltrim($relativePath, DIRECTORY_SEPARATOR);
                } else {
                    $files[] = $name;
                }
            }
        } catch (Throwable $e) {
            return [];
        }

        sort($files);

        return $files;
    }
}

if (!function_exists('getDirFileInfo')) {
    /**
     * Obtiene información sobre los archivos y directorios de un directorio.
     *
     * @param string $sourceDir Ruta del directorio fuente.
     * @param bool $topLevelOnly (Opcional) Indica si se debe obtener información solo del nivel superior del directorio.
     *                           Por defecto es true.
     * @param bool $recursion (Opcional) Indica si se debe realizar una búsqueda recursiva en los subdirectorios.
     *                        Por defecto es false.
     * @return array Un array con información sobre los archivos y directorios.
     */
    function getDirFileInfo(string $sourceDir, bool $topLevelOnly = true, bool $recursion = false): array
    {
        $fileData = [];
        $relativePath = rtrim($sourceDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        try {
            $fp = opendir($sourceDir);

            while (false !== ($file = readdir($fp))) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $filePath = $sourceDir . $file;

                if (is_dir($filePath) && !$topLevelOnly && $recursion) {
                    $fileData[$file] = getDirFileInfo($filePath, false, true);
                } else {
                    $fileData[$file] = getFileInfo($filePath);
                    $fileData[$file]['relative_path'] = $relativePath;
                }
            }

            closedir($fp);

            return $fileData;
        } catch (Throwable $fe) {
            return [];
        }
    }
}

if (!function_exists('getFileInfo')) {
    /**
     * Get File Info
     *
     * Given a file and path, returns the name, path, size, date modified
     * Second parameter allows you to explicitly declare what information you want returned
     * Options are: name, serverPath, size, date, readable, writable, executable, fileperms
     * Returns false if the file cannot be found.
     *
     * @param string $file           Path to file
     * @param mixed  $returnedValues Array or comma separated string of information returned
     * @return array|null
     */
    function getFileInfo(string $file, $returnedValues = ['name', 'serverPath', 'size', 'date'])
    {
        if (!is_file($file)) {
            return null;
        }

        $fileInfo = [];

        if (is_string($returnedValues)) {
            $returnedValues = explode(',', $returnedValues);
        }

        foreach ($returnedValues as $key) {
            switch ($key) {
                case 'name':
                    $fileInfo['name'] = basename($file);
                    break;

                case 'serverPath':
                    $fileInfo['serverPath'] = $file;
                    break;

                case 'size':
                    $fileInfo['size'] = filesize($file);
                    break;

                case 'date':
                    $fileInfo['date'] = filemtime($file);
                    break;

                case 'readable':
                    $fileInfo['readable'] = is_readable($file);
                    break;

                case 'writable':
                    $fileInfo['writable'] = is_writable($file);
                    break;

                case 'executable':
                    $fileInfo['executable'] = is_executable($file);
                    break;

                case 'fileperms':
                    $fileInfo['fileperms'] = fileperms($file);
                    break;
            }
        }

        return $fileInfo;
    }
}

if (!function_exists('symbolicPermissions')) {
    /**
     * Convierte los permisos numéricos en una representación simbólica.
     *
     * @param int $perms Los permisos numéricos.
     * @return string La representación simbólica de los permisos.
     */
    function symbolicPermissions(int $perms): string
    {
        $symbolic = '';
        $types = [
            0xC000 => 's', // Socket
            0xA000 => 'l', // Symbolic Link
            0x8000 => '-', // Regular
            0x6000 => 'b', // Block special
            0x4000 => 'd', // Directory
            0x2000 => 'c', // Character special
            0x1000 => 'p', // FIFO pipe
        ];

        foreach ($types as $type => $char) {
            if (($perms & $type) === $type) {
                $symbolic = $char;
                break;
            }
        }

        $permissions = [
            0x0100 => 'r', // Owner read
            0x0080 => 'w', // Owner write
            0x0040 => 'x', // Owner execute
            0x0800 => 's', // Setuid
            0x0020 => 'r', // Group read
            0x0010 => 'w', // Group write
            0x0008 => 'x', // Group execute
            0x0400 => 's', // Setgid
            0x0004 => 'r', // World read
            0x0002 => 'w', // World write
            0x0001 => 'x', // World execute
            0x0200 => 't', // Sticky bit
        ];

        foreach ($permissions as $permission => $char) {
            if (($perms & $permission) === $permission) {
                $symbolic .= $char;
            } else {
                $symbolic .= '-';
            }
        }

        return $symbolic;
    }
}

if (!function_exists('octalPermissions')) {
    /**
     * Convierte los permisos numéricos en una representación octal de tres dígitos.
     *
     * @param int $perms Los permisos numéricos.
     * @return string La representación octal de los permisos.
     */
    function octalPermissions(int $perms): string
    {
        return str_pad(octdec(sprintf('%o', $perms)), 3, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('sameFile')) {
    /**
     * Verifica si dos archivos son iguales comparando sus sumas de verificación MD5.
     *
     * @param string $file1 Ruta del primer archivo.
     * @param string $file2 Ruta del segundo archivo.
     * @return bool True si los archivos son iguales, False en caso contrario.
     */
    function sameFile(string $file1, string $file2): bool
    {
        if (!is_file($file1) || !is_file($file2)) {
            return false;
        }

        if (filesize($file1) !== filesize($file2)) {
            return false;
        }

        return md5_file($file1) === md5_file($file2);
    }
}

if (!function_exists('setRealpath')) {
    /**
     * Resuelve la ruta absoluta de un archivo o directorio y agrega una barra diagonal al final si es un directorio.
     *
     * @param string $path Ruta a resolver.
     * @param bool $checkExistence Indica si se debe verificar la existencia del archivo o directorio.
     * @return string Ruta absoluta resuelta.
     * @throws InvalidArgumentException Si la ruta es una URL.
     * @throws InvalidArgumentException Si la ruta no es válida y la verificación de existencia está habilitada.
     */
    function setRealpath(string $path, bool $checkExistence = false): string
    {
        // Security check to make sure the path is NOT a URL. No remote file inclusion!
        if (preg_match('#^(https?|ftp)://#i', $path)) {
            throw new InvalidArgumentException('The path you submitted must be a local server path, not a URL');
        }

        // Resolve the path
        $resolvedPath = realpath($path);
        if ($resolvedPath === false) {
            if ($checkExistence) {
                throw new InvalidArgumentException('Not a valid path: ' . $path);
            } else {
                return $path;
            }
        }

        // Add a trailing slash, if this is a directory
        if (is_dir($resolvedPath)) {
            $resolvedPath = rtrim($resolvedPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }

        return $resolvedPath;
    }
}
