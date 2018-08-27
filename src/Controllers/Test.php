<?php
namespace Controllers;
class Test {
	

	
	public function index() {
		print_r($_POST);
		
		return true;
	}
	
	
	public function post_a_job() {
		global $container;

		$params = $_POST;


		//print_r($params);

		//die();
		$db = $container["db"];
			$accountId = $db->prepare('
				INSERT INTO
					job_posts
				VALUES(
					null,
					:job_id,
					:job_kind,
					:job_title,
					:job_description,
					:client_email
					
				)
			')->query([
				":job_id" => 2,
				":job_kind" => $params["job_kind"],
				":job_title" => $params["job_title"],
				":job_description" => $params["job_description"],
				":client_email" => $params["client_email"]
				
			]);

		return json_encode(true);
	}
}