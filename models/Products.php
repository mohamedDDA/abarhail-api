<?php

class Products {
    private $conn;
    private $table = 'products';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT id, title, label, image FROM {$this->table} 
                  ORDER BY id DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($results as &$item) {
            $item['title'] = json_decode($item['title'], true);
            $item['label'] = json_decode($item['label'], true);
        }
        
        return $results;
    }

    public function getCount() {
        $query = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }

    public function getById($id) {
        $query = "SELECT id, title, label, image FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        if ($result) {
            // Decode JSON fields
            $result['title'] = json_decode($result['title'], true);
            $result['label'] = json_decode($result['label'], true);
        }
        
        return $result;
    }

    public function create($data) {
        $query = "INSERT INTO {$this->table} (title, label, image) 
                  VALUES (:title, :label, :image)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':title', json_encode($data['title'], JSON_UNESCAPED_UNICODE));
        $stmt->bindParam(':label', json_encode($data['label'], JSON_UNESCAPED_UNICODE));
        $stmt->bindParam(':image', $data['image']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} 
                  SET title = :title, label = :label, image = :image
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':title', json_encode($data['title'], JSON_UNESCAPED_UNICODE));
        $stmt->bindParam(':label', json_encode($data['label'], JSON_UNESCAPED_UNICODE));
        $stmt->bindParam(':image', $data['image']);
        
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}