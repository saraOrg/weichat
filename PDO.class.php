<?php

/**
 * =================================================
 * 数据库PDO操作类
 * ================================================
 * @category happy
 * @package Admin/
 * @subpackage Action
 * @author Happy <yangbai6644@163.com>
 * @dateTime 2014-2-22 23:13:39
 * ================================================
 */
class PDOAction {

    static $pdo = null;

    function __construct() {
        
    }

    public static function getInstance() {
        if (is_null(self::$pdo)) {
            try {
                self::$pdo = new PDO('mysql:dbname=' . SAE_MYSQL_DB . ';host=' . SAE_MYSQL_HOST_M . ';port=' . SAE_MYSQL_PORT, SAE_MYSQL_USER, SAE_MYSQL_PASS);
            } catch (PDOException $e) {
                exit($e->getMessage());
            }
        }
        return new PDOAction();
    }

    public function insert($data) {
        $sql  = '';
        $stmt = '';
        try {
            $sql  = 'insert into wx_info (content, send_date) values (?, ?)';
            $stmt = self::$pdo->prepare($sql);
            $stmt->bindParam(1, $data['content'], PDO::PARAM_STR);
            $stmt->bindParam(2, $data['send_date'], PDO::PARAM_INT);
            if (false !== $stmt->execute()) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $exc) {
            exit($exc->getTraceAsString());
        }
    }

    public function select($sql) {
        $stmt = '';
        $res  = array();
        try {
            $stmt = self::$pdo->prepare($sql);
            $stmt->execute();
            $res  = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $res;
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
    }

    public function setField($fieldName, $value, $tableName) {
        $sql = '';
        try {
            $sql = 'update ' . $tableName . ' set ' . $fieldName . ' = ' . $value;
            return self::$pdo->exec($sql);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
    }

    public function writeLog($data) {
        $sql  = '';
        $stmt = '';
        try {
            $sql  = 'insert into wx_log (content, create_date) values (?, ?)';
            $stmt = self::$pdo->prepare($sql);
            $stmt->bindParam(1, $data['content'], PDO::PARAM_STR);
            $stmt->bindParam(2, $data['create_date'], PDO::PARAM_STR);
            if (false !== $stmt->execute()) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $exc) {
            exit($exc->getTraceAsString());
        }
    }

}
