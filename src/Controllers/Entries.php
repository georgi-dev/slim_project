<?php
namespace Controllers;

trait Entries {
	static function get_journal($params) {
		global $container;
		
        $page = max(1, (int) $params["page"]);
		$limit = $container->get("settings")["db"]["page_size"];
		$offset = ($page - 1) * $limit;
		
		$entries = [];
		
		$db = $container["db"]->setOptions(["calcrows"]);
		$entriesRows = $db->prepare('
			SELECT SQL_CALC_FOUND_ROWS
				journal.id AS id,
				journal.date AS date,
				journal.description AS description,
				journal.amount_dr AS amount_dr,
				journal.amount_cr AS amount_cr,
				journal.last_modified AS last_modified,
				companies.id AS company_id,
				companies.name AS company_name
			FROM
				journal
				LEFT JOIN periods ON periods.id=journal.period
				LEFT JOIN companies ON companies.id=periods.company
			WHERE
				journal.period=:period
			LIMIT
				' . $limit . '
			OFFSET
				' . $offset
		)->fetchAll([
			":period" => $params["period"]
		]);
		
		$periodInfo = $container["db"]->prepare('
			SELECT
				periods.id AS id,
				periods.name AS name,
				periods.start AS start,
				periods.end AS end,
				companies.id AS company_id,
				companies.name AS company_name
			FROM
				periods
				LEFT JOIN companies ON companies.id=periods.company
		')->fetchOne([
			":period" => $params["period"]
		]);

		return json_encode([
			"name" => $periodInfo["name"],
			"start" => $periodInfo["start"],
			"end" => $periodInfo["end"],
			"company_id" => $periodInfo["company_id"],
			"company_name" => $periodInfo["company_name"],
			"entries" => $entriesRows,
			"totalrecords" => $db->numrows,
			"pages" => ceil($db->numrows / $container->get("settings")["db"]["page_size"])
		]);
	}
	
	static function prepare_entry($params) {
		global $container;
		
		$data = [];

		$periodInfo = $container["db"]->prepare('
			SELECT
				periods.id AS id,
				periods.name AS name,
				periods.start AS start,
				periods.end AS end,
				companies.id AS company_id,
				companies.name AS company_name
			FROM
				periods
				LEFT JOIN companies ON companies.id=periods.company
		')->fetchOne([
			":period" => $params["period"]
		]);
		
		$data["period_name"] = $periodInfo["name"];
		$data["period_start"] = $periodInfo["start"];
		$data["period_end"] = $periodInfo["end"];
		$data["company_id"] = $periodInfo["company_id"];
		$data["company_name"] = $periodInfo["company_name"];
		
		$accountRows = $container["db"]->prepare('
			SELECT
				accounts.id AS account_id,
				accounts.name AS account_name,
				accounts.code AS account_code,
				subaccounts.id AS subaccount_id,
				subaccounts.name AS subaccount_name,
				subaccount_options.subaccount AS options_subaccount,
				subaccount_options.id AS options_id,
				subaccount_options.name AS options_name
			FROM
				accounts
				LEFT JOIN account_subaccounts ON account_subaccounts.account=accounts.id
				LEFT JOIN subaccounts ON account_subaccounts.subaccount=subaccounts.id
				LEFT JOIN subaccount_options ON subaccount_options.subaccount=subaccounts.id
			WHERE
				accounts.company=:company
		')->fetchAll([
			":company" => $data["company_id"]
		]);
		
		foreach($accountRows as $row) {
			$data["accounts"][$row["account_id"]]["code"] = $row["account_code"];
			$data["accounts"][$row["account_id"]]["name"] = $row["account_name"];
			if($row["subaccount_id"]) {
				$data["accounts"][$row["account_id"]]["subaccounts"][$row["subaccount_id"]]["name"] = $row["subaccount_name"];
				$data["accounts"][$row["account_id"]]["subaccounts"][$row["subaccount_id"]]["options"][$row["options_id"]] = $row["options_name"];
			}
		}

		return json_encode($data);
	}
	
	static function save_entry($params) {
		global $container;

		$entry_id = "null";
		if((int) $params["entry_id"] > 0) {
			$entry_id = $params["entry_id"];
			$container["db"]->prepare('
				DELETE FROM
					journal
				WHERE
					id=:id
			')->query([
				":id" => $params["entry_id"]
			]);
		}
		
		$data = json_decode($params["data"], true);

		$entry_dr = 0;
		$entry_cr = 0;
		
		foreach($data as $row) {
			$entry_dr += $row["dr"];
			$entry_cr += $row["cr"];
		}
		
		$db = $container["db"];
		$entryId = $db->prepare('
			INSERT INTO
				journal
			VALUES(
				' . $entry_id . ',
				:period,
				:date,
				:description,
				:dr,
				:cr,
				NOW()
			)
		')->query([
			":period" => $params["period"],
			":date" => date("Y-m-d", strtotime($params["entry_date"])),
			":description" => $params["description"],
			":dr" => $entry_dr,
			":cr" => $entry_cr
		]);
		
		foreach($data as $row) {
			$itemId = $container["db"]->prepare('
				INSERT INTO
					journal_items
				VALUES(
					null,
					:entry,
					:account,
					:type,
					:amount,
					:description,
					NOW()
				)
			')->query([
				":entry" => $entryId,
				":account" => $row["account"],
				":type" => (float) $row["dr"] != 0 ? "dr" : "cr",
				":amount" => $row["dr"] + $row["cr"],
				":description" => $row["notes"]
			]);
			
			foreach($row["refs"] as $key => $value) {
				$container["db"]->prepare('
					INSERT INTO
						journal_item_options
					VALUES(
						null,
						:item,
						:subaccount,
						:subaccount_option,
						:subaccount_value,
						NOW()
					)
				')->query([
					":item" => $itemId,
					":subaccount" => $key,
					":subaccount_option" => (int) $value["option"] == 0 ? null : $value["option"],
					":subaccount_value" => $value["value"]
				]);
			}
		}
	}
	
	static function get_entry($params) {
		global $container;
		
		$data = [];
		
		$periodInfo = $container["db"]->prepare('
			SELECT
				periods.id AS id,
				periods.name AS name,
				periods.start AS start,
				periods.end AS end,
				companies.id AS company_id,
				companies.name AS company_name
			FROM
				periods
				LEFT JOIN companies ON companies.id=periods.company
		')->fetchOne([
			":period" => $params["period"]
		]);
		
		$data["period_name"] = $periodInfo["name"];
		$data["period_start"] = $periodInfo["start"];
		$data["period_end"] = $periodInfo["end"];
		$data["company_id"] = $periodInfo["company_id"];
		$data["company_name"] = $periodInfo["company_name"];
		
		$entryRows = $container["db"]->prepare('
			SELECT
				journal.id AS entry_id,
				journal.date AS entry_date,
				journal.description AS description,
				journal.amount_dr AS total_dr,
				journal.amount_cr AS total_cr,
				journal.last_modified AS last_modified,
				journal_items.id AS item_id,
				journal_items.account AS account,
				journal_items.type AS item_type,
				journal_items.amount AS item_amount,
				journal_items.notes AS notes,
				journal_item_options.id AS option_id,
				journal_item_options.subaccount AS subaccount,
				journal_item_options.subaccount_option AS subaccount_option
			FROM
				journal
				LEFT JOIN journal_items ON journal_items.journal_entry=journal.id
				LEFT JOIN journal_item_options ON journal_item_options.journal_item=journal_items.id
			WHERE
				journal.id=:id
		')->fetchAll([
			":id" => $params["id"]
		]);

		$accountRows = $container["db"]->prepare('
			SELECT
				accounts.id AS account_id,
				accounts.name AS account_name,
				accounts.code AS account_code,
				subaccounts.id AS subaccount_id,
				subaccounts.name AS subaccount_name,
				subaccount_options.subaccount AS options_subaccount,
				subaccount_options.id AS options_id,
				subaccount_options.name AS options_name
			FROM
				accounts
				LEFT JOIN account_subaccounts ON account_subaccounts.account=accounts.id
				LEFT JOIN subaccounts ON account_subaccounts.subaccount=subaccounts.id
				LEFT JOIN subaccount_options ON subaccount_options.subaccount=subaccounts.id
			WHERE
				accounts.company=:company
		')->fetchAll([
			":company" => $data["company_id"]
		]);
		
		foreach($entryRows as $row) {
			$data["entry_id"] = $row["entry_id"];
			$data["entry_date"] = $row["entry_date"];
			$data["description"] = $row["description"];
			$data["total_dr"] = $row["total_dr"];
			$data["total_cr"] = $row["total_cr"];
			$data["last_modified"] = $row["last_modified"];
			$data["rows"][$row["item_id"]]["account"] = $row["account"];
			$data["rows"][$row["item_id"]]["notes"] = $row["notes"];
			$data["rows"][$row["item_id"]]["notes"] = $row["notes"];
			$data["rows"][$row["item_id"]]["dr"] = $row["item_type"] == "dr" ? $row["item_amount"] : 0;
			$data["rows"][$row["item_id"]]["cr"] = $row["item_type"] == "cr" ? $row["item_amount"] : 0;
			if($row["subaccount"]) {
				$data["rows"][$row["item_id"]]["refs"][$row["option_id"]][$row["subaccount"]] = $row["subaccount_option"];
			}
		}
		
		foreach($accountRows as $row) {
			$data["accounts"][$row["account_id"]]["code"] = $row["account_code"];
			$data["accounts"][$row["account_id"]]["name"] = $row["account_name"];
			if($row["subaccount_id"]) {
				$data["accounts"][$row["account_id"]]["subaccounts"][$row["subaccount_id"]]["name"] = $row["subaccount_name"];
				$data["accounts"][$row["account_id"]]["subaccounts"][$row["subaccount_id"]]["options"][$row["options_id"]] = $row["options_name"];
			}
		}

		return json_encode($data);
	}
}