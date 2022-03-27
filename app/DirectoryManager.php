<?php

namespace PressToJamCore;

class DirectoryManager
{
    public static function getRelativeFileName($dir, $file)
    {
        $file = str_replace("\\", "/", $file);
        $dir = str_replace("\\", "/", $dir);
        return str_replace($dir, "", $file);
    }

    public static function getRecursiveFiles($dir)
    {
        $it = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
        return new \RecursiveIteratorIterator($it);
    }

    public static function getRecursiveFilesReverse($dir)
    {
        $it = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
        return new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
    }

    public static function zipDir($dir, $name)
    {
        $zip = new \ZipArchive();
        $zip->open($name, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $files = DirectoryManager::getRecursiveFiles($dir);
        foreach ($files as $file) {
            if ($file->getType() == "link") {
                continue;
            }
            $filename = $file->getFilename();
            $rel_name = ltrim($this->getRelativeFileName($dir, $filename), "/");
            if ($file->getType() == "dir") {
                $zip->addEmptyDir($rel_name);
            } elseif ($file->getType() == "file") {
                $zip->addFromString($rel_name, file_get_contents($file->getRealPath()));
            }
        }
        $zip->close();
    }
}
