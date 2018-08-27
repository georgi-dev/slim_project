<?php
namespace Controllers;

trait Accounts {
	static function get_accounts($params) {
		global $container;
		
        $page = max(1, (int) $params["page"]);
		$limit = $container->get("settings")["db"]["page_size"];
		$offset = ($page - 1) * $limit;
		
		$accounts = [];
		
		$db = $container["db"]->setOptions(["calcrows"]);
		$accounts = $db->prepare('
			SELECT SQL_CALC_FOUND_ROWS
				accounts.id AS id,
				accounts.code AS code,
				accounts.name AS name,
				accounts.last_modified AS last_modified,
				GROUP_CONCAT(subaccounts.name SEPARATOR ", ") AS subaccounts
			FROM
				accounts
				LEFT JOIN account_subaccounts ON account_subaccounts.account=accounts.id
				LEFT JOIN subaccounts ON account_subaccounts.subaccount=subaccounts.id
			WHERE
				accounts.company=:company
			GROUP BY
				accounts.id
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
			"accounts" => $accounts,
			"company_name" => $company["name"],
			"totalrecords" => $db->numrows,
			"pages" => ceil($db->numrows / $container->get("settings")["db"]["page_size"])
		]);
	}
	
	static function get_account($params) {
		global $container;
		
		$db = $container["db"];
		$account = $db->prepare('
			SELECT
				accounts.id AS id,
				accounts.code AS code,
				accounts.name AS name,
				accounts.company AS company_id,
				accounts.last_modified AS last_modified,
				companies.name AS company_name
			FROM
				accounts
				LEFT JOIN companies ON companies.id=accounts.company
			WHERE
				accounts.id=:account
			GROUP BY
				accounts.id
		')->fetchOne([
			":account" => $params["id"]
		]);
		
		$account["subaccounts"] = $db->prepare('
			SELECT
				subaccounts.id AS id,
				subaccounts.name AS name,
				subaccounts.last_modified AS last_modified
			FROM
				account_subaccounts
				LEFT JOIN subaccounts ON account_subaccounts.subaccount=subaccounts.id
			WHERE
				account_subaccounts.account=:account
		')->fetchAll([
			":account" => $params["id"]
		]);
		
		$allSubaccounts = $db->prepare('
			SELECT
				subaccounts.id AS id,
				subaccounts.name AS name,
				subaccounts.last_modified AS last_modified
			FROM
				subaccounts
			WHERE
				subaccounts.company=:company
		')->fetchAll([
			":company" => $account["company_id"]
		]);
		
		return json_encode([
			"account" => $account,
			"subaccounts" => $allSubaccounts
		]);
	}
	
	static function prepare_account($params) {
		global $container;
		
		$db = $container["db"];
		
		$allSubaccounts = $db->prepare('
			SELECT
				subaccounts.id AS id,
				subaccounts.name AS name,
				subaccounts.last_modified AS last_modified
			FROM
				subaccounts
			WHERE
				subaccounts.company=:company
		')->fetchAll([
			":company" => $params["company"]
		]);
		
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
			"company_name" => $company["name"],
			"subaccounts" => $allSubaccounts
		]);
	}
	
	static function save_account($params) {
		global $container;

		if((int) $params["id"] == 0) { // Insert new account
			$db = $container["db"];
			$accountId = $db->prepare('
				INSERT INTO
					accounts
				VALUES(
					null,
					:company,
					:code,
					:name,
					NOW()
				)
			')->query([
				":company" => $params["company"],
				":code" => $params["code"],
				":name" => $params["name"]
			]);
			
			foreach(json_decode($params["subaccounts"], true) as $item) {
				$db->prepare('
					INSERT INTO
						account_subaccounts
					VALUES(
						null,
						:account,
						:subaccount,
						NOW()
					)
				')->query([
					":account" => $accountId,
					":subaccount" => $item["id"]
				]);
			}
		} else {
			$db = $container["db"];
			$db->prepare('
				UPDATE
					accounts
				SET
					code=:code,
					name=:name,
					last_modified=NOW()
				WHERE
					id=:account
			')->query([
				":code" => $params["code"],
				":name" => $params["name"],
				":account" => $params["id"]
			]);
			
			// Deleting the old subaccounts
			$db->prepare('
				DELETE FROM
					account_subaccounts
				WHERE
					account=:account
			')->query([
				":account" => $params["id"]
			]);
			
			$subaccounts = json_decode($params["subaccounts"], true);
			foreach($subaccounts as $key => $item) {
				$db->prepare('
					INSERT INTO
						account_subaccounts
					VALUES(
						null,
						:account,
						:subaccount,
						NOW()
					)
				')->query([
					":account" => $params["id"],
					":subaccount" => $item["id"]
				]);
			}
		}
		
		return json_encode(true);
	}
	
	static function delete_account($params) {
		global $container;
		
		$container["db"]->prepare('
			DELETE FROM
				accounts
			WHERE
				id=:account
		')->query([
			":account" => $params["account"]
		]);
		
		return json_encode(true);
	}
}