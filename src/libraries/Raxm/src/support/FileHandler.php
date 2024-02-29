<?php

namespace Raxm\Support;

/**
 * Class FileHandler
 *  A class for handling uploaded files, providing validation and moving functionalities.
 * 
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\HTTP
 */
class FileHandler
{
    /**
     * @var array The uploaded file data.
     */
    protected $file;

    /**
     * @var array The allowed file extensions.
     */
    protected $allowedExtensions = [];

    /**
     * @var int The maximum allowed file size in bytes.
     */
    protected $maxFileSize = 5242880; // 5 MB

    /**
     * @var string The directory where files will be uploaded.
     */
    protected $uploadDir = 'uploads/';

    /**
     * @var array Errors occurred during validation.
     */
    protected $errors = [];

    /**
     * @var string File destination.
     */
    protected $destination;

    /**
     * Create a new FileHandler instance.
     * @param array $file The uploaded file data from $_FILES.
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Set the allowed file extensions.
     * @param array $extensions An array of allowed file extensions (without dot).
     */
    public function setAllowedExtensions(array $extensions)
    {
        $this->allowedExtensions = $extensions;
    }

    /**
     * Set the upload directory.
     * @param string $dir The directory path.
     */
    public function setUploadDir($dir)
    {
        $this->uploadDir = rtrim($dir, '/') . '/';
    }

    /**
     * Set the maximum allowed file size.
     * @param int $size The maximum file size in bytes.
     */
    public function setMaxFileSize($size)
    {
        $this->maxFileSize = $size;
    }

    /**
     * Get file.
     * @return string get file.
     */
    public function get()
    {
        return $this->file;
    }

    /**
     * Get directory file.
     * @return string get directory file.
     */
    public function getDir()
    {
        return $this->uploadDir;
    }

    /**
     * Check if the uploaded file is valid.
     * @return bool True if the file is valid, false otherwise.
     */
    public function isValid()
    {
        if (!$this->file || $this->errors() !== UPLOAD_ERR_OK) {
            $this->addError("File upload error.");
            return false;
        }

        $extension = strtolower(pathinfo($this->name(), PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            $this->addError("Invalid file extension.");
            return false;
        }

        if ($this->size() > $this->maxFileSize) {
            $this->addError("File size exceeds the limit.");
            return false;
        }

        return true;
    }

    /**
     * Move the uploaded file to the configured directory.
     * @return bool True if the file was moved successfully, false otherwise.
     */
    public function move(): bool
    {
        if (!$this->isValid()) return false;

        $this->destination = str_replace('\\', '/', $this->getDir() . $this->generateUniqueFileName());
        return move_uploaded_file($this->tmpName(), $this->destination);
    }

    /**
     * Generate a unique file name based on timestamp and unique ID.
     * @return string The generated unique file name.
     */
    public function generateUniqueFileName(): string
    {
        $name = $this->name();
        $meta = '-meta' . str_replace('/', '_', base64_encode($name)) . '-';
        $filename = time() . '_' . $meta . uniqid() . '.' . pathinfo($name, PATHINFO_EXTENSION);
        return $filename;
    }

    /**
     * extractOriginalFileName
     *
     * @param  mixed $generatedFileName
     * @return string
     */
    public static function extractOriginalFileName(string $generatedFileName): string
    {
        // Remove the timestamp and unique ID suffix from the generated filename
        $originalNameWithExtension = substr($generatedFileName, strpos($generatedFileName, '-') + 1);
        $originalName = substr($originalNameWithExtension, strpos($originalNameWithExtension, '-') + 1, strrpos($originalNameWithExtension, '.') - strlen($originalNameWithExtension));
        $originalName = base64_decode(str_replace('_', '/', substr($originalName, 0, strpos($originalName, '-'))));

        return $originalName;
    }

    /**
     * Get the original file name.
     * @return string The original file name.
     */
    public function name()
    {
        if ($this->ifMultiple()) {
            return data_get($this->getAllFilesMultiples(), 'name');
        }

        return $this->file['name'][0];
    }

    /**
     * Get the file size.
     * @return int The file size in bytes.
     */
    public function size()
    {
        if ($this->ifMultiple()) {
            return data_get($this->getAllFilesMultiples(), 'size');
        }

        return $this->file['size'][0];
    }

    /**
     * Get the temporal name.
     * @return string The temporal name.
     */
    public function tmpName()
    {
        if ($this->ifMultiple()) {
            return data_get($this->getAllFilesMultiples(), 'tmp_name');
        }

        return $this->file['tmp_name'][0];
    }

    /**
     * Get the MIME type of the file.
     * @return string The MIME type.
     */
    public function mime()
    {
        if ($this->ifMultiple()) {
            return data_get($this->getAllFilesMultiples(), 'type');
        }

        return $this->file['type'][0];
    }

    /**
     * Get the errors file.
     * @return string The error file.
     */
    public function errors()
    {
        if ($this->ifMultiple()) {
            return data_get($this->getAllFilesMultiples(), 'error');
        }

        return $this->file['error'][0];
    }

    /**
     * Get destination file.
     * @return string The error file.
     */
    public function destination(): string
    {
        return $this->destination;
    }

    /**
     * Get the errors occurred during validation.
     * @return array An array of errors.
     */
    public function getErrorsMessages()
    {
        return $this->errors;
    }

    /**
     * Add an error message to the error array.
     * @param string $message The error message to add.
     */
    protected function addError($message)
    {
        $this->errors[] = $message;
    }

    /**
     * ifMultiple
     * @return bool
     */
    public function ifMultiple(): bool
    {
        $count = count($this->file['name']);
        return $count > 1;
    }

    /**
     * getAllFilesMultiples
     * @return array
     */
    private function getAllFilesMultiples(): array
    {
        $files = [];
        foreach ($this->file['name'] as $file) {
            $files[] = $file;
        }

        return $files;
    }
    
    /**
     * delete
     * @return void
     */
    public function delete()
    {
        return @unlink($this->file['name']);
    }
    
    /**
     * __invoke
     * @return void
     */
    function __invoke()
    {
        return $this;
    }
}
