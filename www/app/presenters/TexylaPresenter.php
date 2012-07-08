<?php

/**
 * Texyla presenter
 *
 * @author Jan Marek
 * @license MIT
 */
class TexylaPresenter extends BasePresenter
{
    protected $baseFolderPath;

    protected $baseFolderUri;

    protected $tempDir;

    protected $tempUri;

    public function startup()
    {
        parent::startup();
        $this->baseFolderPath = WWW_DIR . "/files";
        $this->baseFolderUri = Environment::getVariable("baseUri") . "files";
        $this->tempDir = WWW_DIR . "/webtemp";
        $this->tempUri = Environment::getVariable("baseUri") . "/webtemp";
    }

    /**
     * Texy náhled
     * @param string $texy texy k převedení
     * @param string $cfg název texy konfigurace
     */
    public function actionPreview($texy, $cfg = null)
    {
        $texyInstance = new MyTexy;
        $html = $texyInstance->process($texy);
        $this->terminate(new RenderResponse($html));
    }

    // files plugin

    /**
     * Poslat chybovou zprávu
     * @param string $msg
     */
    protected function sendError($msg)
    {
        $this->terminate(new JsonResponse(array(
            "error" => $msg,
        ), "text/plain"));
    }

    /**
     * Získá a zkontroluje cestu ke složce
     * @param string $folder
     */
    protected function getFolderPath($folder)
    {
        $folderPath = realpath($this->baseFolderPath . ($folder ? "/" . $folder : ""));

        if (!is_dir($folderPath) || !is_writable($folderPath) || !String::startsWith($folderPath, realpath($this->baseFolderPath))) {
            throw new InvalidArgumentException;
        }

        return $folderPath;
    }

    /**
     * Název souboru s cachovaným náhledem obrázku ve file browseru
     * @param string $path
     * @return string
     */
    protected function thumbnailFileName($path)
    {
        $path = realpath($path);
        return "texylapreview-" . md5($path . "|" . filemtime($path)) . ".jpg";
    }

    /**
     * File browser - projít soubory
     * @param string $folder
     */
    public function actionListFiles($folder = "")
    {
        // check rights
        if (!Environment::getUser()->isLoggedIn()) {
            $this->sendError("Access denied.");
        }

        try {
            $folderPath = $this->getFolderPath($folder);
        } catch (InvalidArgumentException $e) {
            $this->sendError("Folder does not exist or is not writeable.");
        }

        // list of files
        $folders = array();
        $files = array();

        // up
        if ($folder !== "") {
            $lastPos = strrpos($folder, "/");
            $key = $lastPos === false ? "" : substr($folder, 0, $lastPos);

            $folders[] = array(
                "type" => "up",
                "name" => "..",
                "key" => $key,
            );
        }

        foreach (new DirectoryIterator($folderPath) as $fileInfo) {
            $fileName = $fileInfo->getFileName();

            // skip hidden files, . and ..
            if (String::startsWith($fileName, ".")) continue;

            // filename with folder
            $key = ($folder ? $folder . "/" : "") . $fileName;

            // directory
            if ($fileInfo->isDir()) {
                $folders[] = array(
                    "type" => "folder",
                    "name" => $fileName,
                    "key" => $key,
                );

            // file
            } elseif ($fileInfo->isFile()) {

                // image
                if (@getImageSize($fileInfo->getPathName())) {
                    $thumbFileName = $this->thumbnailFileName($fileInfo->getPathName());

                    if (file_exists($this->tempDir . "/" . $thumbFileName)) {
                        $thumbnailKey = $this->tempUri . "/" . $thumbFileName;
                    } else {
                        $thumbnailKey = $this->link("thumbnail", $key);
                    }

                    $files[] = array(
                        "type" => "image",
                        "name" => $fileName,
                        "insertUrl" => $key,
                        "description" => $fileName,
                        "thumbnailKey" => $thumbnailKey,
                    );

                // other file
                } else {
                    $files[] = array(
                        "type" => "file",
                        "name" => $fileName,
                        "insertUrl" => $key,
                        "description" => $fileName,
                    );
                }
            }
        }

        // send response
        $this->terminate(new JsonResponse(array(
            "list" => array_merge($folders, $files),
        )));
    }

    /**
     * Vygenerovat a zobrazit náhled obrázku ve file browseru
     * @param string $key
     */
    public function actionThumbnail($key) {
        try {
            $path = $this->baseFolderPath . "/" . $key;
            $image = Image::fromFile($path)->resize(60, 40);
            $image->save($this->tempDir . "/" . $this->thumbnailFileName($path));
            @chmod($path, 0666);
            $image->send();

        } catch (Exception $e) {
            Image::fromString(Image::EMPTY_GIF)->send(Image::GIF);
        }

        $this->terminate();
    }

    /**
     * Upload souboru
     */
    public function actionUpload() {
        // check user rights
        if (!Environment::getUser()->isAllowed("files", "upload")) {
            $this->sendError("Access denied.");
        }

        // path
        $folder = Environment::getHttpRequest()->getPost("folder");

        try {
            $folderPath = $this->getFolderPath($folder);
        } catch (InvalidArgumentException $e) {
            $this->sendError("Folder does not exist or is not writeable.");
        }

        // file
        $file = Environment::getHttpRequest()->getFile("file");

        // check
        if ($file === null || !$file->isOk()) {
            $this->sendError("Upload error.");
        }

        // move
        $fileName = String::webalize($file->getName(), ".");
        $path = $folderPath . "/" . $fileName;

        if (@$file->move($path)) {
            @chmod($path, 0666);

            if ($file->isImage()) {
                $this->payload->filename = ($folder ? "$folder/" : "") . $fileName;
                $this->payload->type = "image";
            } else {
                $this->payload->filename = $this->baseFolderUri . "/" . ($folder ? "$folder/" : "") . $fileName;
                $this->payload->type = "file";
            }

            $this->terminate(new JsonResponse($this->payload, "text/plain"));

        } else {
            $this->sendError("Move failed.");
        }
    }
}