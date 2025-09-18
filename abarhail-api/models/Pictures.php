<?php

class Pictures {
    private $conn;
    private $table = 'pictures';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($page = 1, $limit = 50) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT * FROM {$this->table} 
                  WHERE status = 'active' 
                  ORDER BY category ASC, subcategory ASC, sort_order ASC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($results as &$item) {
            $item['alt_text'] = json_decode($item['alt_text'], true);
            $item['title'] = json_decode($item['title'], true);
        }
        
        return $results;
    }

    public function getByCategory($category) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE category = :category AND status = 'active' 
                  ORDER BY subcategory ASC, sort_order ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category', $category);
        $stmt->execute();
        
        $results = $stmt->fetchAll();
        
        // Decode JSON fields and organize by subcategory
        $organized = [];
        
        foreach ($results as $item) {
            $item['alt_text'] = json_decode($item['alt_text'], true);
            $item['title'] = json_decode($item['title'], true);
            
            $subcategory = $item['subcategory'] ?: 'default';
            
            if (!isset($organized[$subcategory])) {
                $organized[$subcategory] = [];
            }
            
            $organized[$subcategory][$item['key_name']] = $item['image_url'];
        }
        
        return $organized;
    }

    public function getStructuredImages() {
        $query = "SELECT * FROM {$this->table} 
                  WHERE status = 'active' 
                  ORDER BY category ASC, subcategory ASC, sort_order ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $results = $stmt->fetchAll();
        $structured = [];
        
        foreach ($results as $item) {
            $category = $item['category'];
            $subcategory = $item['subcategory'];
            $keyName = $item['key_name'];
            $imageUrl = $item['image_url'];
            
            if (!isset($structured[$category])) {
                $structured[$category] = [];
            }
            
            if ($subcategory) {
                if (!isset($structured[$category][$subcategory])) {
                    $structured[$category][$subcategory] = [];
                }
                $structured[$category][$subcategory][$keyName] = $imageUrl;
            } else {
                $structured[$category][$keyName] = $imageUrl;
            }
        }
        
        return $structured;
    }

    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        if ($result) {
            $result['alt_text'] = json_decode($result['alt_text'], true);
            $result['title'] = json_decode($result['title'], true);
        }
        
        return $result;
    }

    public function create($data) {
        $query = "INSERT INTO {$this->table} 
                  (category, subcategory, key_name, image_url, alt_text, title, sort_order, status, created_at, updated_at) 
                  VALUES (:category, :subcategory, :key_name, :image_url, :alt_text, :title, :sort_order, :status, NOW(), NOW())
                  ON DUPLICATE KEY UPDATE 
                  image_url = :image_url_update, alt_text = :alt_text_update, title = :title_update, 
                  sort_order = :sort_order_update, status = :status_update, updated_at = NOW()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':subcategory', $data['subcategory']);
        $stmt->bindParam(':key_name', $data['key_name']);
        $stmt->bindParam(':image_url', $data['image_url']);
        $stmt->bindParam(':alt_text', json_encode($data['alt_text'] ?? [], JSON_UNESCAPED_UNICODE));
        $stmt->bindParam(':title', json_encode($data['title'] ?? [], JSON_UNESCAPED_UNICODE));
        $stmt->bindParam(':sort_order', $data['sort_order'] ?? 0);
        $stmt->bindParam(':status', $data['status'] ?? 'active');
        
        // For ON DUPLICATE KEY UPDATE
        $stmt->bindParam(':image_url_update', $data['image_url']);
        $stmt->bindParam(':alt_text_update', json_encode($data['alt_text'] ?? [], JSON_UNESCAPED_UNICODE));
        $stmt->bindParam(':title_update', json_encode($data['title'] ?? [], JSON_UNESCAPED_UNICODE));
        $stmt->bindParam(':sort_order_update', $data['sort_order'] ?? 0);
        $stmt->bindParam(':status_update', $data['status'] ?? 'active');
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId() ?: $this->getIdByKey($data['category'], $data['subcategory'], $data['key_name']);
        }
        return false;
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} 
                  SET category = :category, subcategory = :subcategory, key_name = :key_name,
                      image_url = :image_url, alt_text = :alt_text, title = :title,
                      sort_order = :sort_order, status = :status, updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':subcategory', $data['subcategory']);
        $stmt->bindParam(':key_name', $data['key_name']);
        $stmt->bindParam(':image_url', $data['image_url']);
        $stmt->bindParam(':alt_text', json_encode($data['alt_text'] ?? [], JSON_UNESCAPED_UNICODE));
        $stmt->bindParam(':title', json_encode($data['title'] ?? [], JSON_UNESCAPED_UNICODE));
        $stmt->bindParam(':sort_order', $data['sort_order'] ?? 0);
        $stmt->bindParam(':status', $data['status'] ?? 'active');
        
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "UPDATE {$this->table} SET status = 'deleted', updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function bulkInsert($images) {
        $this->conn->beginTransaction();
        
        try {
            foreach ($images as $imageData) {
                $this->create($imageData);
            }
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    private function getIdByKey($category, $subcategory, $keyName) {
        $query = "SELECT id FROM {$this->table} 
                  WHERE category = :category AND subcategory = :subcategory AND key_name = :key_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':subcategory', $subcategory);
        $stmt->bindParam(':key_name', $keyName);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    }

    public function getCategories() {
        $query = "SELECT DISTINCT category FROM {$this->table} WHERE status = 'active' ORDER BY category ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getSubcategories($category) {
        $query = "SELECT DISTINCT subcategory FROM {$this->table} 
                  WHERE category = :category AND status = 'active' 
                  ORDER BY subcategory ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category', $category);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}