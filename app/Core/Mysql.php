<?php
class Core_Mysql {
    protected $_connection;

    public function __construct() {
        $this->connect();
    }

    protected function connect($new = false) {
        if (!$new && $this->_connection) {
            return;
        } else {
            $host = Core::getConfig()->db->host;
            $user = Core::getConfig()->db->user;
            $pass = Core::getConfig()->db->pass;
            $name = Core::getConfig()->db->name;

            $this->_connection = new mysqli($host, $user, $pass, $name);
        }
    }

    public function query($query) {
        $result = $this->_connection->query($query);
        return $result;
    }
}
