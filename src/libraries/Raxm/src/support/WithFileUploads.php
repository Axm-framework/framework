<?php

namespace Raxm\Support;

/**
 * A trait for handling file uploads in Raxm components.
 */
trait WithFileUploads
{
    /**
     * Start the file upload process.
     *
     * @param string $name       The name of the file input.
     * @param bool   $isMultiple Whether the file input allows multiple files.
     */
    public function startUpload($name, $isMultiple)
    {
        $name = $this->params[0];
        $pathSignedUrl = app()->request->signed('/raxm/upload-file');

        $this->emit('upload:generatedSignedUrl', $name, $pathSignedUrl)->self();
    }

    /**
     * Finish the file upload process.
     */
    public function finishUpload()
    {
        $name       = $this->params[0];
        $tmpPath    = $this->params[1];
        $isMultiple = $this->params[2];

        $this->cleanupOldUploads($tmpPath);

        if ($isMultiple) {
            $file = array_map(function ($i) {
                return FileHandler::extractOriginalFileName($i);
            }, $tmpPath);

            $filenames = array_map(function ($f) {
                return $f->name();
            }, $file);

            $this->emit('upload:finished', $name, $filenames)->self();
        } else {

            $file = $tmpPath;
            $this->emit('upload:finished', $name, [$tmpPath])->self();
            // If the property is an array, but the upload ISN'T set to "multiple"
            // then APPEND the upload to the array, rather than replacing it.
            $value = $this->getPropertyValue($name);

            if (is_array($value)) {
                $file = array_merge($value, [$tmpPath]);
            }
        }

        $this->setProtectedPropertyValue($name, $file);
    }

    /**
     * Handle errors that occurred during file upload.
     */
    public function uploadErrored()
    {
        $name         = $this->params[0];
        $errorsInJson = $this->params[1];
        $isMultiple   = $this->params[2];

        $this->emit('upload:errored', $name)->self();

        if (is_null($errorsInJson)) {

            $this->addError($name, app()->request->getCookie('message_errorFile_upload'));

            $this->messages = $this->getErrorBag();
            app()->request->deleteCookie('message_errorFile_upload');

            return;
        }

        $errorsInJson = $isMultiple
            ? str_ireplace('files',   $name, $errorsInJson)
            : str_ireplace('files.0', $name, $errorsInJson);

        $errors = json_decode($errorsInJson, true)['errors'];

        throw new \Exception($errors);
    }

    /**
     * Remove a specific file upload.
     *
     * @param string $name        The name of the file input.
     * @param string $tmpFilename The temporary filename to remove.
     */
    public function removeUpload($name, $tmpFilename)
    {
        $uploads = $this->getPropertyValue($name);
        if (is_array($uploads) && isset($uploads[0]) && $uploads[0] instanceof FileHandler) {
            $this->emit('upload:removed', $name, $tmpFilename)->self();

            $this->syncInput($name, array_values(array_filter($uploads, function ($upload) use ($tmpFilename) {
                if ($upload->getFilename() === $tmpFilename) {
                    $upload->delete();
                    return false;
                }
                return true;
            })));
        } elseif ($uploads instanceof FileHandler && $uploads->name() === $tmpFilename) {

            $uploads->delete();

            $this->emit('upload:removed', $name, $tmpFilename)->self();

            $this->syncInput($name, null);
        }
    }

    /**
     * Clean up old temporary uploads.
     * @param array $tmpPath The temporary file paths.
     */
    protected function cleanupOldUploads($tmpPath)
    {
        if (!isset($tmpPath[1])) return;

        $path = dirname($tmpPath[1]);
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $filePathname = $path . DIRECTORY_SEPARATOR . $file;

            if (!file_exists($filePathname)) continue;

            $yesterdaysStamp = now()->subDay()->timestamp;
            $lastModified = filemtime($filePathname);

            if ($lastModified < $yesterdaysStamp) {
                unlink($filePathname);
            }
        }
    }
}
