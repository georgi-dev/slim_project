<?php
namespace Services;

use Services\MeowUtils as Utils;

/**
 * @version 1.0.0
 * Example usage:
 * $db = \Db::get(["debug", "transaction", "calcrows"]);
 * $user = $db->prepare('SELECT SQL_CALC_FOUND_ROWS * FROM users WHERE users_id=:userid')->fetchOne([":userid" => 2]);
 * echo $db->numrows;
 *
 */
class MeowDb {
    private static $_instance = null;

    protected $_query;
    protected $_rst;

    public $pdo;
    public $calcrows = false;
    public $transaction = false;
    public $debug = false;
    public $result;
    public $numrows;

    
	/*
	 * Constructor disabled due to singleton pattern
	 * DB credentials fetched from the configuration file
	 */
    private function __construct($options = null) {
		global $container;

		if($options) {
			foreach($options as $option) {
				$this->{$option} = true;
			}
		}

        $this->pdo = new \PDO($container->get("settings")["db"]["conn_string"], $container->get("settings")["db"]["user"], $container->get("settings")["db"]["pass"]);
    }

	/*
	 * Creates an instance of the object
	 * @param $options array - list of options, "debug", "transaction", "calcrows"
	 * @return instance object
	 */
    public static function get($options = null) {
        if(self::$_instance === null) {
            self::$_instance = new MeowDb($options);
        }

		self::$_instance->calcrows = self::$_instance->debug = self::$_instance->transaction = false;
		if($options) {
			foreach($options as $option) {
				self::$_instance->{$option} = true;
			}
		}

        return self::$_instance;
    }
    
    /*
     * Sets options
     */
    public static function setOptions($options) {
        foreach($options as $option) {
            self::$_instance->{$option} = true;
        }
        
        return self::$_instance;
    }

	/*
	 * Prepares a prepared statement
	 * @param $query string - the SQL query
	 * @param $returnPdo boolean - returns the singleton or the the statement
	 * @return instance object
	 */
	public function prepare($query, $returnPdo = false) {
	    $this->_rst = $this->pdo->prepare($query);

		return $returnPdo ? $this->pdo : $this;
	}

	/*
	 * Fetches ONE row from the database
	 * @param $binds array - the parameters to be binded
	 * @param $fetch string - the fetch type. Default is FETCH_ASSOC
	 * @return mixed - array of record on success, false on error
	 */
    public function fetchOne($binds = null, $fetch = \PDO::FETCH_ASSOC) {
        if($this->debug) {
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $this->_sql_debug($this->_rst->queryString, $binds);
            try {
                if($this->transaction) {
                    $this->pdo->beginTransaction();
                    $this->_rst->execute($binds);
                    $this->pdo->commit();
                    $this->result = $this->_rst->fetch($fetch);
                } else {
                    $this->_rst->execute($binds);
                    $this->result = $this->_rst->fetch($fetch);
                }

                return $this->result;
            } catch(\PDOException $e) {
                Utils::dump("PDO ERROR: " . print_r($e->errorInfo, true));
				$this->pdo->rollBack();

                return false;
            }
        } else {
            if($this->transaction) {
                $this->pdo->beginTransaction();
                $this->_rst->execute($binds);
                $this->pdo->commit();
                $this->result = $this->_rst->fetch($fetch);
            } else {
                $this->_rst->execute($binds);
                $this->result = $this->_rst->fetch($fetch);
            }

            return $this->result;
        }
    }

	/*
	 * Fetches ALL rows from the database
	 * @param $binds array - the parameters to be binded
	 * @param $fetch string - the fetch type. Default is FETCH_ASSOC
	 * @return mixed - array of records on success, false on error
	 */
    public function fetchAll($binds = null) {
        if($this->debug) {
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $this->_sql_debug($this->_rst->queryString, $binds);
            try {
                if($this->transaction) {
                    $this->pdo->beginTransaction();
                    $this->_rst->execute($binds);
                    $this->pdo->commit();
                } else {
                    $this->_rst->execute($binds);
                    $this->result = $this->_rst->fetchAll(\PDO::FETCH_ASSOC);
                }

                if($this->calcrows) {
                    $this->numrows = $this->pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
                }

                return $this->result;
            } catch(PDOException $e) {
				\Utils::dump("PDO ERROR: " . print_r($e->errorInfo, true));
				$this->pdo->rollBack();

                return false;
            }
        } else {
            if($this->transaction) {
                $this->pdo->beginTransaction();
                $this->_rst->execute($binds);
                $this->pdo->commit();
            } else {
                $this->_rst->execute($binds);
                $this->result = $this->_rst->fetchAll(\PDO::FETCH_ASSOC);
            }

			if($this->calcrows) {
				$this->numrows = $this->pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
			}

            return $this->result;
        }
    }

	/*
	 * Executes arbitrary query
	 * @param $binds array - the parameters to be binded
	 * @return mixed - attempts to return the LAST_INSERT_ID()
	 */
    public function query($binds = null) {
        if($this->debug) {
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $this->_sql_debug($this->_rst->queryString, $binds);
            try {
                if($this->transaction) {
                    $this->pdo->beginTransaction();
                    $this->_rst->execute($binds);
                    $this->pdo->commit();
                } else {
                    $this->_rst->execute($binds);
                }

                return $this->pdo->lastInsertId();
            } catch (PDOException $e) {
                Utils::dump("PDO ERROR: " . print_r($e->errorInfo, true));
            }
        } else {
            if($this->transaction) {
                $this->pdo->beginTransaction();
                $this->_rst->execute($binds);
                $this->pdo->commit();
            } else {
                $this->_rst->execute($binds);
            }

            return $this->pdo->lastInsertId();
        }
    }

    private function _sql_debug($_query, $binds = null) {
        if($binds !== null) {
            foreach ($binds as $key => $value) {
                $searches[] = "/" . preg_quote($key) . "\b/";
                $replaces[] = "'" . $value . "'";
            }
            $_query = preg_replace($searches, $replaces, $_query);
        }

        Utils::dump($_query);
    }

    private function __clone() {
        return false;
    }

    private function __wakeup()	{
        return false;
    }
}
