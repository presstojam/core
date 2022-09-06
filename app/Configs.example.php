<?php

//this file should be moved out the root directory

use PressToJamCore\env;

return ["pdo" => [
    "name"=>PressToJamCore\env('dbname'),
    "host"=>PressToJamCore\env('dbhost'),
    "port"=>PressToJamCore\env('dbport', 3306),
    "user"=>PressToJamCore\env('dbuser'),
    "pass"=>PressToJamCore\env('dbpass'),
    "cert"=>PressToJamCore\env('dbcert')
],
"jwt" => [
    "secret"=>PressToJamCore\env("jwtkey")
],
"aws" => [
    "s3bucket"=>PressToJamCore\env("s3bucket"),
    "s3path"=>PressToJamCore\env("s3path"),
    "sqsarn"=>PressToJamCore\env("sqsarn"),
    "cfdistributionid"=>PressToJamCore\env("cfdistid"),
    "settings"=> [
        "region" =>"eu-west-1",
        "version" => "latest",
        "credentials"=>[
            "user"=>PressToJamCore\env("awsuser"),
            "pass"=>PressToJamCore\env("pass")
        ]
   ]
]];

