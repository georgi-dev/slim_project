<?php
return [
    "settings" => [
		"applicationName" => "Accounting",
        "displayErrorDetails" => true, // set to false in production
        "addContentLengthHeader" => false, // Allow the web server to send the content-length header

        "views_path" => __DIR__ . "/../templates/",
		
		// Database credentials
		"db" => [
			"conn_string" => "mysql:dbname=maistori;host=localhost;charset=utf8",
			"user" => "root",
			"pass" => "",
			"page_size" => 30
		]
    ],
];
