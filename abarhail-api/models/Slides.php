<?php

class Slides {
    private $conn;
    private $table = 'slides';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT * FROM {$this->table} 
                  WHERE status = 'published' 
                  ORDER BY sort_order ASC, created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($results as &$item) {
            $item['title'] = json_decode($item['title'], true);
            $item['description'] = json_decode($item['description'], true);
            $item['button_text'] = json_decode($item['button_text'], true);
        }
        
        return $results;
    }

    public function getCount($status = 'published') {
        $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = :status";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }

    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        if ($result) {
            // Decode JSON fields
            $result['title'] = json_decode($result['title'], true);
            $result['description'] = json_decode($result['description'], true);
            $result['button_text'] = json_decode($result['button_text'], true);
        }
        
        return $result;
    }

    public function create($data) {
        $query = "INSERT INTO {$this->table} 
                  (image, title, description, button_text, link, sort_order, status, created_at, updated_at) 
                  VALUES (:image, :title, :description, :button_text, :link, :sort_order, :status, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':image', $data['image']);
        $stmt->bindParam(':title', json_encode($data['title'], JSON_UNESCAPED_UNICODE));
        $stmt->bindParam(':description', json_encode($data['description'], JSON_UNESCAPED_UNICODE));
        $stmt->bindParam(':button_text', json_encode($data['button_text'], JSON_UNESCAPED_UNICODE));
        $stmt->bindParam(':link', $data['link']);
        $stmt->bindParam(':sort_order', $data['sort_order']);
        $stmt->bindParam(':status', $data['status']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} 
                  SET image = :image, title = :title, description = :description, 
                      button_text = :button_text, link = :link, sort_order = :sort_order, 
                      status = :status, updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':image', $data['image']);
        $stmt->bindParam(':title', json_encode($data['title'], JSON_UNESCAPED_UNICODE));
        $stmt->bindParam(':description', json_encode($data['description'], JSON_UNESCAPED_UNICODE));
        $stmt->bindParam(':button_text', json_encode($data['button_text'], JSON_UNESCAPED_UNICODE));
        $stmt->bindParam(':link', $data['link']);
        $stmt->bindParam(':sort_order', $data['sort_order']);
        $stmt->bindParam(':status', $data['status']);
        
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "UPDATE {$this->table} SET status = 'deleted', updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getByStatus($status, $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT * FROM {$this->table} 
                  WHERE status = :status 
                  ORDER BY sort_order ASC, created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($results as &$item) {
            $item['title'] = json_decode($item['title'], true);
            $item['description'] = json_decode($item['description'], true);
            $item['button_text'] = json_decode($item['button_text'], true);
        }
        
        return $results;
    }

    public function updateSortOrder($id, $sortOrder) {
        $query = "UPDATE {$this->table} SET sort_order = :sort_order, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':sort_order', $sortOrder, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function reorderSlides($slideIds) {
        $this->conn->beginTransaction();
        
        try {
            foreach ($slideIds as $index => $slideId) {
                $this->updateSortOrder($slideId, $index + 1);
            }
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}