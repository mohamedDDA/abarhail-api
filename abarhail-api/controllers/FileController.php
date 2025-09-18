// FILE 2: controllers/FileController.php
// ===============================

<?php
require_once 'core/BaseController.php';
require_once 'core/FileUpload.php';

class FileController extends BaseController {
    private $fileUpload;
    
    public function __construct($database) {
        parent::__construct($database);
        $this->fileUpload = new FileUpload();
    }
    
    public function uploadSingle() {
        try {
            // Check if file was uploaded
            if (!isset($_FILES['image'])) {
                ApiResponse::error('No file uploaded', 400, 'NO_FILE_UPLOADED');
                return;
            }
            
            $result = $this->fileUpload->uploadImage($_FILES['image']);
            
            if ($result['success']) {
                ApiResponse::success($result['data'], 'Image uploaded successfully', 201);
            } else {
                ApiResponse::error(
                    $result['message'], 
                    400, 
                    $result['error_code'] ?? 'FILE_UPLOAD_FAILED'
                );
            }
            
        } catch (Exception $e) {
            error_log("File upload error: " . $e->getMessage());
            ApiResponse::error('File upload failed', 500, 'FILE_UPLOAD_FAILED');
        }
    }
    
    public function uploadMultiple() {
        try {
            // Check if files were uploaded
            if (!isset($_FILES['images'])) {
                ApiResponse::error('No files uploaded', 400, 'NO_FILE_UPLOADED');
                return;
            }
            
            $result = $this->fileUpload->uploadMultipleImages($_FILES['images']);
            
            $totalUploaded = $result['summary']['uploaded'];
            $totalFailed = $result['summary']['failed'];
            
            if ($totalUploaded > 0) {
                $message = $totalFailed > 0 
                    ? "{$totalUploaded} images uploaded successfully, {$totalFailed} failed"
                    : "{$totalUploaded} images uploaded successfully";
                
                ApiResponse::success($result, $message, 201);
            } else {
                ApiResponse::error('All uploads failed', 400, 'ALL_UPLOADS_FAILED');
            }
            
        } catch (Exception $e) {
            error_log("Multiple file upload error: " . $e->getMessage());
            ApiResponse::error('File upload failed', 500, 'FILE_UPLOAD_FAILED');
        }
    }
    
    public function deleteImage($filename) {
        try {
            if (empty($filename)) {
                ApiResponse::error('Filename is required', 400, 'MISSING_FILENAME');
                return;
            }
            
            $result = $this->fileUpload->deleteImage($filename);
            
            if ($result['success']) {
                ApiResponse::success(null, $result['message']);
            } else {
                $statusCode = $result['error_code'] === 'FILE_NOT_FOUND' ? 404 : 400;
                ApiResponse::error(
                    $result['message'], 
                    $statusCode, 
                    $result['error_code']
                );
            }
            
        } catch (Exception $e) {
            error_log("File delete error: " . $e->getMessage());
            ApiResponse::error('File deletion failed', 500, 'FILE_DELETE_FAILED');
        }
    }
}

// ===============================