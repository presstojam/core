<?php

namespace PressToJamCore;

class DObjectStructTypes {
    public const parent = 0;
    public const child = 1;
    public const ref = 2;
    public const circular = 3;
}

class DObjectStruct {
    public $from;
    public $to;
    public $from_col;
    public $to_col;
    public $required;
    public $display_name;
    public $class_name;
    public $type;
}
