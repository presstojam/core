<?php

namespace PressToJamCore\Services;

class StaticFileWriter {

    private $s3host;
    private $theme_key;

    public function __construct($project) {
        $this->s3host = new AmazonS3Writer();
    }

    function setTheme($theme) {
        $this->theme_key = $theme;
    }

    function writeFile($file_key) {
        $template_str = $this->s3host->get($this->theme_key);
        $file_str = $this->s3host->get($file_key);
        $dom = new \DomDocument();
        @$dom->loadHTML($template_str);
        $cdom = new \DomDocument();
        @$cdom->loadHTML($file_str);
        $x = new \DomXPath($cdom);

        $temps = $cdom->getElementsByTagName("template");
        $slots = $dom->getElementsByTagName("slot");
        for($i = count($slots) - 1; $i>=0; --$i) {
            $slot = $slots[$i];
            $xslot = $x->query("//template[@name='" . $slot->getAttribute("name") . "']");
            if (count($xslot) > 0) {
                $imp = $dom->importNode($xslot, true);
                $slot->parentNode->insertBefore($slot, $imp);
                $slot->parentNode->removeChild($slot);
            }
        }


        $html_str = $dom->saveHTML();
        $this->s3host->push($file, $html_str);
    }


}