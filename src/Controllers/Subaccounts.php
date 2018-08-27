<?php
namespace Controllers;

trait Subaccounts {
	static function get_subaccounts($params) {
		global $container;
		
        $page = max(1, (int) $params["page"]);
		$limit = $container->get("settings")["db"]["page_size"];
		$offset = ($page - 1) * $limit;
		
		$subaccounts = [];
		
		$db = $container["db"]->setOptions(["calcrows"]);
		$subaccounts = $db->prepare('
			SELECT SQL_CALC_FOUND_ROWS
				subaccounts.id AS id,
				subaccounts.name AS name,
				subaccounts.last_modified AS last_modified,
				GROUP_CONCAT(subaccount_options.name SEPARATOR ", ") AS options
			FROM
				subaccounts
				LEFT JOIN subaccount_options ON subaccount_options.subaccount=subaccounts.id
			WHERE
				subaccounts.company=:company
			GROUP BY
				subaccounts.id
			LIMIT
				' . $limit . '
			OFFSET
				' . $offset
		)->fetchAll([
			":company" => $params["company"]
		]);
		
		$company = $db->prepare('
			SELECT
				companies.id AS id,
				companies.name AS name,
				companies.last_modified AS last_modified
			FROM
				companies
			WHERE
				companies.id=:company'
		)->fetchOne([
			":company" => $params["company"]
		]);

		return json_encode([
			"subaccounts" => $subaccounts,
			"company_name" => $company["name"],
			"totalrecords" => $db->numrows,
			"pages" => ceil($db->numrows / $container->get("settings")["db"]["page_size"])
		]);
	}
		
	static function get_subaccount($params) {
		global $container;
		
		$db = $container["db"];
		$subaccount = $db->prepare('
			SELECT
				subaccounts.id AS id,
				subaccounts.name AS name,
				subaccounts.company AS company_id,
				subaccounts.last_modified AS last_modified,
				companies.name AS company_name
			FROM
				subaccounts
				LEFT JOIN companies ON companies.id=subaccounts.company
			WHERE
				subaccounts.id=:subaccount
		')->fetchOne([
			":subaccount" => $params["id"]
		]);
		
		$subaccount["options"] = $db->prepare('
			SELECT
				id AS id,
				name AS name,
				last_modified AS last_modified
			FROM
				subaccount_options
			WHERE
				subaccount_options.subaccount=:subaccount
		')->fetchAll([
			":subaccount" => $params["id"]
		]);
		
		return json_encode([
			"subaccount" => $subaccount
		]);
	}
	
	static function prepare_subaccount($params) {
		global $container;
		
		$db = $container["db"];
		
		$company = $db->prepare('
			SELECT
				id AS id,
				name AS name
			FROM
				companies
			WHERE
				companies.id=:company
		')->fetchOne([
			":company" => $params["company"]
		]);
		
		return json_encode([
			"company_id" => $company["id"],
			"company_name" => $company["name"]
		]);
	}
	
	static function save_subaccount($params) {
		global $container;
		
		if((int) $params["id"] == 0) { // Insert new account
			$db = $container["db"];
			$subaccountId = $db->prepare('
				INSERT INTO
					subaccounts
				VALUES(
					null,
					:name,
					:company,
					NOW()
				)
			')->query([
				":name" => $params["name"],
				":company" => $params["company"]
			]);
			
			foreach(json_decode($params["options"], true) as $item) {
				$db->prepare('
					INSERT INTO
						subaccount_options
					VALUES(
						null,
						:subaccount,
						:name,
						NOW()
					)
				')->query([
					":name" => $item["name"],
					":subaccount" => $subaccountId
				]);
			}
		} else {
			$db = $container["db"];
			$db->prepare('
				UPDATE
					subaccounts
				SET
					name=:name,
					last_modified=NOW()
				WHERE
					id=:subaccount
			')->query([
				":name" => $params["name"],
				":subaccount" => $params["id"]
			]);
			
			$oldOptions = [];
			$oldOptionsObj = $db->prepare('
				SELECT
					id AS id
				FROM
					subaccount_options
				WHERE
					subaccount_options.subaccount=:subaccount
			')->fetchAll([
				":subaccount" => $params["id"]
			]);
			foreach($oldOptionsObj as $item) {
				$oldOptions[$item["id"]] = true;
			}
	
			$newOptions = [];
			foreach(json_decode($params["options"], true) as $item) {
				if($item["id"] === "new") {
					$db->prepare('
						INSERT INTO
							subaccount_options
						VALUES(
							null,
							:subaccount,
							:name,
							NOW()
						)
					')->query([
						":name" => $item["name"],
						":subaccount" => $params["id"]
					]);
				} else {
					$db->prepare('
						UPDATE
							subaccount_options
						SET
							subaccount_options.name=:name,
							subaccount_options.last_modified=NOW()
						WHERE
							subaccount_options.id=:id
					')->query([
						":name" => $item["name"],
						":id" => $item["id"]
					]);
					
					unset($oldOptions[$item["id"]]);
				}
			}
	
			foreach($oldOptions as $key => $item) {
				$db->prepare('
					DELETE FROM
						subaccount_options
					WHERE
						id=:id
				')->query([
					":id" => $key
				]);
			}
		}
		
		return json_encode(true);
	}
	
	static function delete_subaccount($params) {
		global $container;
		
		$container["db"]->prepare('
			DELETE FROM
				subaccounts
			WHERE
				id=:subaccount
		')->query([
			":subaccount" => $params["subaccount"]
		]);
		
		return json_encode(true);
	}
}