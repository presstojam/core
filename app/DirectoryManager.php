<?php

namespace PressToJamCore;

class DirectoryManager {


    static function getRelativeFileName($file) {
        $file = str_replace("\\", "/", $file);
        $dir = str_replace("\\", "/", $this->config->dir);
        return str_replace($dir, "", $file);
    }

    static function getRecursiveFiles($dir) {
        $it = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
        return new \RecursiveIteratorIterator($it);
    }


}