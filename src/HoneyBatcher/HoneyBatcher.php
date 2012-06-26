<?php

/*
* This file is part of the HoneyBatcher utility.
*
* (c) Leander Damme <leander@wesrc.com>
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace HoneyBatcher;

/**
 * Main entry point for HoneyBatcher.
 *
 * @author Leander Damme <leander@wesrc.com>
 */
class HoneyBatcher
{
    const VERSION = '0.1';

    protected $targetDir;

    protected $destinationDir;

    protected $alreadyImportedDir = null;

    protected $alreadyImportedFiles;

    protected $targetFiles;

    protected $batchSize = 300;

    public function __construct($target, $destination, $alreadyImported = null)
    {
        $this->setTargetDir(realpath($target));
        $this->setDestinationDir(realpath($destination));

        if (null !== $alreadyImported) {
            $this->setAlreadyImportedDir(realpath($alreadyImported));
            $this->scanAlreadyImportedFiles();
        }

        $this->scanTargetDir();
        $this->moveBatchesToFolders();

    }

    public function moveBatchesToFolders()
    {

        $excludeImportedFiles = true;
        $targetFiles = $this->getTargetFiles($excludeImportedFiles);

        $batches = array_chunk($targetFiles, $this->batchSize);

        foreach ($batches as $bnum => $filebatch) {

            if (!is_dir($this->destinationDir . '/batch' . $bnum)) {
                mkdir($this->destinationDir . '/batch' . $bnum);
            }

            foreach ($filebatch as $file) {
                echo "moving {$file} to {$this->destinationDir}/batch{$bnum}/{$file}" . PHP_EOL;
                rename($this->targetDir . '/' . $file, $this->destinationDir . '/batch' . $bnum . '/' . $file);
            }
        }

    }

    public function scanTargetDir()
    {
        // open this directory
        $myDirectory = opendir($this->getTargetDir());
        $fileArray = array();
        // get each entry
        while ($entryName = readdir($myDirectory)) {

            //ignore parent dir etc.
            if ('.' !== $entryName && '..' !== $entryName) {
                $type = filetype($this->getTargetDir() . '/' . $entryName);
                if ('dir' !== $type)
                    //only add if suffix is contained in filename
                    $fileArray[] = $entryName;
            }
        }
        // close directory
        closedir($myDirectory);
        $this->setTargetFiles($fileArray);
        return $this;
    }

    public function scanAlreadyImportedFiles($suffix = '.done')
    {
        // open this directory
        $myDirectory = opendir($this->getAlreadyImportedDir());
        $suffixLength = strlen($suffix);
        $fileArray = array();
        // get each entry
        while ($entryName = readdir($myDirectory)) {
            //ignore parent dir etc.
            if ('.' !== $entryName && '..' !== $entryName) {
                //only add if suffix is contained in filename
                if ($suffix == substr($entryName, -$suffixLength, $suffixLength)) {
                    $fileNameWithoutSuffix = str_replace($suffix, '', $entryName);
                    $fileArray[] = $fileNameWithoutSuffix;
                }
            }
        }
        // close directory
        closedir($myDirectory);
        $this->setAlreadyImportedFiles($fileArray);
        return $this;
    }


    public function setAlreadyImportedDir($alreadyImportedDir)
    {
        $this->alreadyImportedDir = $alreadyImportedDir;
    }

    public function getAlreadyImportedDir()
    {
        return $this->alreadyImportedDir;
    }

    public function setDestinationDir($destinationDir)
    {
        $this->destinationDir = $destinationDir;
    }

    public function getDestinationDir()
    {
        return $this->destinationDir;
    }

    public function setTargetDir($targetDir)
    {
        $this->targetDir = $targetDir;
    }

    public function getTargetDir()
    {
        return $this->targetDir;
    }

    public function setAlreadyImportedFiles(array $alreadyImportedFiles)
    {
        $this->alreadyImportedFiles = $alreadyImportedFiles;
    }

    public function getAlreadyImportedFiles()
    {
        return $this->alreadyImportedFiles;
    }

    public function setTargetFiles($targetFiles)
    {
        $this->targetFiles = $targetFiles;
    }

    public function getTargetFiles($excludeAlreadyImported = false)
    {
        if ($excludeAlreadyImported) {
            $filesToImport = array_diff($this->targetFiles, $this->alreadyImportedFiles);
            return $filesToImport;
        }

        return $this->targetFiles;
    }
}