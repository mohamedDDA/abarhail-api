

<?php
class FileUpload {
    private $uploadDir;
    private $allowedTypes;
    private $maxFileSize;
    private $baseUrl;
    
    public function __construct() {
        $this->uploadDir = './uploads/images/';
        $this->allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $this->maxFileSize = 5 * 1024 * 1024; // 5MB
        $this->baseUrl = defined('BASE_URL') ? BASE_URL : 'https://abarhail.xo.je/abarhail-api/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    public function uploadImage($file) {
        // Validate file
        $validation = $this->validateFile($file);
        if (!$validation['success']) {
            return $validation;
        }
        
        // Generate unique filename
        $filename = $this->generateUniqueFilename($file['name']);
        $targetPath = $this->uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $fileUrl = $this->baseUrl . 'uploads/images/' . $filename;
            
            return [
                'success' => true,
                'data' => [
                    'filename' => $filename,
                    'url' => $fileUrl,
                    'size' => $file['size'],
                    'type' => $file['type']
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to save uploaded file',
                'error_code' => 'FILE_UPLOAD_FAILED'
            ];
        }
    }
    
    public function uploadMultipleImages($files) {
        $results = [
            'uploaded' => [],
            'failed' => [],
            'summary' => [
                'total' => count($files['name']),
                'uploaded' => 0,
                'failed' => 0
            ]
        ];
        
        for ($i = 0; $i < count($files['name']); $i++) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            $result = $this->uploadImage($file);
            
            if ($result['success']) {
                $results['uploaded'][] = $result['data'];
                $results['summary']['uploaded']++;
            } else {
                $results['failed'][] = [
                    'filename' => $file['name'],
                    'error' => $result['message']
                ];
                $results['summary']['failed']++;
            }
        }
        
        return $results;
    }
    
    public function deleteImage($filename) {
        // Security check: only allow deletion of files in our upload directory
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
            return [
                'success' => false,
                'message' => 'Invalid filename',
                'error_code' => 'INVALID_FILENAME'
            ];
        }
        
        $filePath = $this->uploadDir . $filename;
        
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'message' => 'File not found',
                'error_code' => 'FILE_NOT_FOUND'
            ];
        }
        
        if (unlink($filePath)) {
            return [
                'success' => true,
                'message' => 'File deleted successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete file',
                'error_code' => 'FILE_DELETE_FAILED'
            ];
        }
    }
    
    private function generateUniqueFilename($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $timestamp = date('Ymd_His');
        $random = mt_rand(1000, 9999);
        
        return "image_{$random}_{$timestamp}.{$extension}";
    }
    
    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => $this->getUploadErrorMessage($file['error']),
                'error_code' => 'UPLOAD_ERROR'
            ];
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return [
                'success' => false,
                'message' => 'File size exceeds maximum limit of ' . ($this->maxFileSize / 1024 / 1024) . 'MB',
                'error_code' => 'FILE_TOO_LARGE'
            ];
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedTypes)) {
            return [
                'success' => false,
                'message' => 'File type not allowed. Allowed types: ' . implode(', ', $this->allowedTypes),
                'error_code' => 'INVALID_FILE_TYPE'
            ];
        }
        
        // Check MIME type for additional security
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp'
        ];
        
        if (!in_array($mimeType, $allowedMimes)) {
            return [
                'success' => false,
                'message' => 'Invalid file type detected',
                'error_code' => 'INVALID_FILE_TYPE'
            ];
        }
        
        // Check if file is actually an image
        $imageSize = getimagesize($file['tmp_name']);
        if ($imageSize === false) {
            return [
                'success' => false,
                'message' => 'File is not a valid image',
                'error_code' => 'INVALID_FILE_TYPE'
            ];
        }
        
        return ['success' => true];
    }
    
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }
}

// ===============================

// FILE 3: Updated index.php (Add this to your existing router)
// ===============================

// Add this case to your existing switch statement in index.php:

