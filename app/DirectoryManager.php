<?php

namespace PressToJamCore;

class DirectoryManager {


    static function getRelativeFileName($dir, $file) {
        $file = str_replace("\\", "/", $file);
        $dir = str_replace("\\", "/", $dir);
        return str_replace($dir, "", $file);
    }

    static function getRecursiveFiles($dir) {
        $it = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
        return new \RecursiveIteratorIterator($it);
    }

    static function getRecursiveFilesReverse($dir) {
        $it = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
        return new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
    }


}