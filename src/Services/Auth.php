<?php
namespace Services;

use Services\MeowDb as Db;

class Auth {
	public function login($params) {
		$result = ["err", "msg"];

		$user = Db::get()->prepare('
			SELECT
				id AS id,
				name AS name,
				email AS email,
				password AS password
			FROM
				users
			WHERE
				email=:email
		')->fetchOne([":email" => $params["username"]]);
		
		if(is_array($user)) {
			if(password_verify($params["password"], $user["password"])) {
				$_SESSION["user"] = [
					"id" => $user["id"],
					"name" => $user["name"],
					"email" => $user["email"]
				];
				$result["err"] = 0;
				$result["msg"] = "Successful login";
			} else {
				sleep(3);
				$result["err"] = 422;
				$result["msg"] = "Invalid password";
			}
		} else {
			sleep(3);
			$result["err"] = 422;
			$result["msg"] = "Auth not found in DB";
		}
		
		return $result;
	}
	
	public function logout() {
		unset($_SESSION["user"]);
	}
}