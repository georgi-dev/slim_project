<?php

$container = $app->getContainer();

$container["utils"] = function($container) {
	return new Services\MeowUtils;
};

$container["db"] = function($container) {
	return Services\MeowDb::get();
};
