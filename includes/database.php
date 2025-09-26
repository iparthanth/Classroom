<?php

class Database extends PDO {
    public function fetchOne($query, $params = []) {
        $stmt = $this->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchAll($query, $params = []) {
        $stmt = $this->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function executeQuery($query, $params = []) {
        $stmt = $this->prepare($query);
        return $stmt->execute($params);
    }
}

?>