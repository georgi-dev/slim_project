<?php
namespace Controllers;

class Receiver {
	use Entries, Companies, Accounts, Subaccounts;

	
	public function logout() {
		\Services\Auth::logout();
		
		return true;
	}
	
	
	/**
	 *  Not callable method
	 *
	 */
    static function __callStatic($method, $params) {
		return "Method \"" . $method . "\" was not found";
    }
}