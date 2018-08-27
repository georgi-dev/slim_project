<?php

//use Controllers\Test;
$app->get('/', function ($request, $response, $args) {
    return $this->renderer->render($response, "/home.php", $args);
});

$app->get('/hello/{name}', function ($request, $response, $args) {
    return $this->renderer->render($response, "/home.php", $args);
});

$app->post("/Test", function($request, $response, $args) {
    print_r($_POST);
});

$app->get('/postajob', function ($request, $response, $args) {
    return $this->renderer->render($response, "/post_a_job.php", $args);
});
$app->post('/save_a_posted_job', '\Controllers\Test:post_a_job');
// $app->get('/post_a_job', function ($request, $response, $args) {
    
// });