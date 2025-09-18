<?php
abstract class BaseController {
    protected $db;

    public function __construct($database) {
        $this->db = $database->getConnection();
    }

    protected function getRequestData() {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    protected function validateRequired($data, $requiredFields) {
        $missing = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            ApiResponse::error(
                'Missing required fields: ' . implode(', ', $missing), 
                400, 
                'VALIDATION_ERROR'
            );
        }
    }

    protected function getPaginationParams() {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
        return [$page, $limit];
    }
}