<?php
namespace PressToJamCore\Wrappers;

class LocalHost {
    protected $dir;
   

    function __construct($dir) {
        $this->dir = rtrim($dir, "/") . "/";

    }

    public function getDir() {
        return $this->dir;
    }

    public function connect()
    {
       
    }

    public function mkFolder($dir) {
        $dir = ltrim($dir . "/");
        $pdir = dirname($dir);
        if ($pdir AND $pdir != "/" AND $pdir != "." AND !file_exists($this->dir . $pdir)) $this->mkFolder($pdir);
        try {
            mkdir($this->dir . $dir);
        } catch (\Exception $e) {
            $this->logger->addLog("Make folder on localhost failed: Directory: " . $this->dir . " " . $dir . " " . $e->getMessage());
        }
    }

    
    public function push($file_name, $contents)
    {
        $file_name = ltrim($file_name);
        $dir = dirname($file_name);
        if (!file_exists($this->dir . $dir)) $this->mkFolder($dir);
        echo "\nWriting " . $this->dir . $file_name;
        file_put_contents($this->dir . $file_name, $contents);
    }

    public function get($file_name) {
        return file_get_contents($this->dir . ltrim($file_name, "/"));
    }

    public function fileExists($file_name) {
        return file_exists($this->dir . $file_name);
    }

    public function list($dir) {
        $dir = opendir($this->dir . $dir);
        $arr=array();
        while ($file = readdir($dir)) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $arr[] = $file;
        }
        closedir($dir);
        return $arr;
    }

    public function append($file_name, $contents) {
        $data = "";
        if (file_exists($this->dir . $file_name)) $data = $this->get($file_name);
        $data .= $contents;
        $this->push($file_name, $data);
    }


}