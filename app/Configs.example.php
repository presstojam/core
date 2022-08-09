<?php

//this file should be moved out the root directory

use PressToJamCore\env;

return ["pdo", [
    "name"=>env('dbname'),
    "host"=>env('dbhost'),
    "port"=>env('dbport', 3306),
    "user"=>env('dbuser'),
    "pass"=>env('dbpass'),
    "cert"=>env('dbcert')
],
"aws", [
    "s3bucket"=>env("s3bucket"),
    "s3path"=>env("s3path"),
    "sqsarn"=>env("sqsarn"),
    "cfdistributionid"=>env("cfdistid"),
    "settings"=> [
        "region" =>"eu-west-1",
        "version" => "latest",
        "credentials"=>[
            "user"=>env("awsuser"),
            "pass"=>env("pass")
        ]
    ]
]
        ];

