<?php

namespace PressToJamCore;


class Cors {

    public $headers=["content-type",
    "x-requested-with",
    "x-force-auth-cookies",
    "accept",
    "origin",
    "authorization",
    "referer",
    "sec-ch-ua",
    "sec-ch-ua-mobile",
    "user-agent"];
    public $origin;
}