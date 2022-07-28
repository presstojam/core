<?php

namespace PressToJamCore;


class Cors {

    public $headers=["Content-Type",
    "X-Requested-With",
    "X-Force-Auth-Cookies",
    "Accept",
    "Origin",
    "Authorization",
    "Referer",
    "sec-ch-ua",
    "sec-ch-ua-mobile",
    "User-Agent"];
    public $origin;
}