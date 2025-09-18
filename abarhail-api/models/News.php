<?php
class News {
    private $conn;
    private $table = 'news';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT * FROM {$this->table} 
                  WHERE status = 'published' 
                  ORDER BY created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($results as &$item) {
            $item['title']   = $item['title']   ? json_decode($item['title'], true)   : [];
            $item['content'] = $item['content'] ? json_decode($item['content'], true) : [];
            $item['excerpt'] = $item['excerpt'] ? json_decode($item['excerpt'], true) : [];
            $item['images']  = $item['images']  ? json_decode($item['images'], true)  : [];
        }
        
        return $results;
    }

    public function getCount($status = 'published') {
        $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = :status";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $result['title']   = $result['title']   ? json_decode($result['title'], true)   : [];
            $result['content'] = $result['content'] ? json_decode($result['content'], true) : [];
            $result['excerpt'] = $result['excerpt'] ? json_decode($result['excerpt'], true) : [];
            $result['images']  = $result['images']  ? json_decode($result['images'], true)  : [];
        }
        
        return $result;
    }

    public function create($data) {
        $query = "INSERT INTO {$this->table} 
                  (title, content, excerpt, images, status, created_at, updated_at) 
                  VALUES (:title, :content, :excerpt, :images, :status, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($query);

        // Store JSON in variables first
        $title   = json_encode($data['title'], JSON_UNESCAPED_UNICODE);
        $content = json_encode($data['content'], JSON_UNESCAPED_UNICODE);
        $excerpt = json_encode($data['excerpt'], JSON_UNESCAPED_UNICODE);
        $images  = json_encode($data['images'], JSON_UNESCAPED_UNICODE);
        $status  = $data['status'];

        $stmt->bindParam(':title',   $title);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':excerpt', $excerpt);
        $stmt->bindParam(':images',  $images);
        $stmt->bindParam(':status',  $status);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} 
                  SET title = :title, content = :content, excerpt = :excerpt, 
                      images = :images, status = :status, updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        // Store JSON in variables first
        $title   = json_encode($data['title'], JSON_UNESCAPED_UNICODE);
        $content = json_encode($data['content'], JSON_UNESCAPED_UNICODE);
        $excerpt = json_encode($data['excerpt'], JSON_UNESCAPED_UNICODE);
        $images  = json_encode($data['images'], JSON_UNESCAPED_UNICODE);
        $status  = $data['status'];

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':title',   $title);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':excerpt', $excerpt);
        $stmt->bindParam(':images',  $images);
        $stmt->bindParam(':status',  $status);
        
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
                  ORDER BY created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as &$item) {
            $item['title']   = $item['title']   ? json_decode($item['title'], true)   : [];
            $item['content'] = $item['content'] ? json_decode($item['content'], true) : [];
            $item['excerpt'] = $item['excerpt'] ? json_decode($item['excerpt'], true) : [];
            $item['images']  = $item['images']  ? json_decode($item['images'], true)  : [];
        }
        return $results;
    }
}
