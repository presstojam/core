<?php

namespace PressToJamCore;

class Il8n {

    static private $lang;

    static function setLang($lang) {
        self::$lang = $lang;
    }

    static function getLang() {
        return self::$lang;
    }

    
}