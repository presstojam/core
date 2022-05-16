<?php

namespace PressToJamCore;


class Cors {

    public $headers=["Content-Type",
    "X-Requested-With",
    "Accept",
    "Origin",
    "Authorization",
    "Referer",
    "sec-ch-ua",
    "sec-ch-ua-mobile",
    "User-Agent"];
    public $origin;
}