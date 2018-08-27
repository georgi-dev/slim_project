<?php
namespace Controllers;

trait Companies {
	static function get_companies($params) {
		global $container;
		
        $page = max(1, (int) $params["page"]);
		$limit = $container->get("settings")["db"]["page_size"];
		$offset = ($page - 1) * $limit;
		
		$companies = [];
		
		$db = $container["db"];
		$companiesRows = $db->prepare('
			SELECT
				companies.id AS id,
				companies.name AS name,
				companies.last_modified AS last_modified,
				periods.id AS period_id,
				periods.name AS period_name,
				periods.start AS period_start,
				periods.end AS period_end
			FROM
				companies
				LEFT JOIN periods ON companies.id=periods.company
			LIMIT
				' . $limit . '
			OFFSET
				' . $offset
			)->fetchAll();

		foreach($companiesRows as $row) {
			$companies[$row["id"]]["id"] = $row["id"];
			$companies[$row["id"]]["name"] = $row["name"];
			$companies[$row["id"]]["last_modified"] = $row["last_modified"];
			if($row["period_id"] > 0) {
				$companies[$row["id"]]["periods"][$row["period_id"]]["id"] = $row["period_id"];
				$companies[$row["id"]]["periods"][$row["period_id"]]["name"] = $row["period_name"];
				$companies[$row["id"]]["periods"][$row["period_id"]]["start"] = $row["period_start"];
				$companies[$row["id"]]["periods"][$row["period_id"]]["end"] = $row["period_end"];
			}
		}
		
		foreach($companies as $key => $company) {
			if(array_key_exists("periods", $companies[$key])) {
				$companies[$key]["periods"] = array_values($companies[$key]["periods"]);
			}
		}

		return json_encode([
			"companies" => array_values($companies),
			"totalrecords" => count($companies),
			"pages" => ceil(count($companies) / $container->get("settings")["db"]["page_size"])
		]);
	}
	
	static function get_company($params) {
		global $container;
		
		$company = ["periods" => []];
		
		$db = $container["db"];
		$companyRows = $db->prepare('
			SELECT
				companies.id AS id,
				companies.name AS name,
				companies.description AS description,
				companies.last_modified AS last_modified,
				periods.id AS period_id,
				periods.name AS period_name,
				periods.start AS period_start,
				periods.end AS period_end,
				periods.last_modified AS last_modified
			FROM
				companies
				LEFT JOIN periods ON companies.id=periods.company
			WHERE
				companies.id=:company
			')->fetchAll([
				":company" => $params["id"]
			]);
		
		foreach($companyRows as $row) {
			$company["id"] = $row["id"];
			$company["name"] = $row["name"];
			$company["description"] = $row["description"];
			$company["last_modified"] = $row["last_modified"];
			if($row["period_id"] > 0) {
				$company["periods"][] = [
					"id" => $row["period_id"],
					"name" => $row["period_name"],
					"start" => $row["period_start"],
					"end" => $row["period_end"],
					"last_modified" => $row["last_modified"]
				];
			}
		}
		
		return json_encode([
			"company" => $company
		]);
	}
	
	static function save_company($params) {
		global $container;
		
		if((int) $params["id"] == 0) { // Insert new company
			$companyId = $container["db"]->prepare('
				INSERT INTO
					companies
				VALUES(
					null,
					:name,
					:description,
					NOW()
				)
			')->query([
				":name" => $params["name"],
				":description" => $params["description"]
			]);
			
			$newPeriods = json_decode($params["periods"], true);
			foreach($newPeriods as $period) {
				$container["db"]->prepare('
					INSERT INTO
						periods
					VALUES(
						null,
						:company,
						:name,
						:start,
						:end,
						NOW()
					)
				')->query([
					":name" => $period["name"],
					":company" => $companyId,
					":start" => $period["start"],
					":end" => $period["end"]
				]);
			}
		} else { // Update existing company
			$container["db"]->prepare('
				UPDATE
					companies
				SET
					name=:name,
					description=:description,
					last_modified=NOW()
				WHERE
					id=:id
			')->query([
				":name" => $params["name"],
				":description" => $params["description"],
				":id" => $params["id"]
			]);
			
			// Fetch old periods
			$oldPeriodsObj = $container["db"]->prepare('
				SELECT
					GROUP_CONCAT(id) AS ids
				FROM
					periods
				WHERE
					periods.company=:company
				GROUP BY
					periods.company
			')->fetchOne([
				":company" => $params["id"]
			]);
			
			foreach(explode(",", $oldPeriodsObj["ids"]) as $key => $item) {
				$oldPeriods[$item] = true;
			}
	
			$newPeriods = json_decode($params["periods"], true);
			foreach($newPeriods as $period) {
				if($period["id"] == "") {
					$container["db"]->prepare('
						INSERT INTO
							periods
						VALUES(
							null,
							:company,
							:name,
							:start,
							:end,
							NOW()
						)
					')->query([
						":name" => $period["name"],
						":company" => $params["id"],
						":start" => $period["start"],
						":end" => $period["end"]
					]);
				} else {
					$container["db"]->prepare('
						UPDATE
							periods
						SET
							name=:name,
							start=:start,
							end=:end,
							last_modified=NOW()
						WHERE
							id=:id
					')->query([
						":name" => $period["name"],
						":start" => $period["start"],
						":end" => $period["end"],
						":id" => $period["id"]
					]);
					
					unset($oldPeriods[$period["id"]]);
				}
			}
			
			foreach($oldPeriods as $key => $item) {
				$container["db"]->prepare('
					DELETE FROM
						periods
					WHERE
						id=:id
				')->query([
					":id" => $key
				]);
			}
		}
		
		return json_encode(true);
	}
}