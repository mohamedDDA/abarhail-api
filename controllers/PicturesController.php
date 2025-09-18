<?php
require_once 'core/BaseController.php';
require_once 'models/Pictures.php';
require_once 'core/ApiResponse.php';

class PicturesController extends BaseController {
    private $pictures;

    public function __construct($database) {
        parent::__construct($database);
        $this->pictures = new Pictures($this->db);
    }

    // GET /api/v1/pictures
    public function index() {
        list($page, $limit) = $this->getPaginationParams();
        $category = $_GET['category'] ?? null;

        if ($category) {
            $picturesData = $this->pictures->getByCategory($category);
            ApiResponse::success($picturesData, 'Pictures retrieved by category successfully');
        } else {
            $picturesData = $this->pictures->getAll($page, $limit);
            // Since Pictures model doesn't have a getCount method, we'll use the count of results
            $total = count($picturesData);
            ApiResponse::paginated($picturesData, $total, $page, $limit, 'Pictures retrieved successfully');
        }
    }

    // GET /api/v1/pictures/{id}
    public function show($id) {
        if (!is_numeric($id)) {
            ApiResponse::error('Invalid picture ID', 400, 'INVALID_ID');
        }

        $picture = $this->pictures->getById($id);
        
        if (!$picture) {
            ApiResponse::error('Picture not found', 404, 'PICTURE_NOT_FOUND');
        }

        ApiResponse::success($picture, 'Picture retrieved successfully');
    }

    // GET /api/v1/pictures/structured
    public function structured() {
        $structuredImages = $this->pictures->getStructuredImages();
        ApiResponse::success($structuredImages, 'Structured pictures retrieved successfully');
    }

    // GET /api/v1/pictures/categories
    public function categories() {
        $categories = $this->pictures->getCategories();
        ApiResponse::success($categories, 'Categories retrieved successfully');
    }

    // GET /api/v1/pictures/categories/{category}/subcategories
    public function subcategories($category) {
        if (empty($category)) {
            ApiResponse::error('Category is required', 400, 'MISSING_CATEGORY');
        }

        $subcategories = $this->pictures->getSubcategories($category);
        ApiResponse::success($subcategories, 'Subcategories retrieved successfully');
    }

    // POST /api/v1/pictures
    public function store() {
        $data = $this->getRequestData();

        // Validate required fields
        $this->validateRequired($data, ['category', 'key_name', 'image_url']);

        // Validate category and key_name
        if (empty($data['category'])) {
            ApiResponse::error('Category is required', 400, 'MISSING_CATEGORY');
        }

        if (empty($data['key_name'])) {
            ApiResponse::error('Key name is required', 400, 'MISSING_KEY_NAME');
        }

        if (empty($data['image_url'])) {
            ApiResponse::error('Image URL is required', 400, 'MISSING_IMAGE_URL');
        }

        // Prepare data
        $pictureData = [
            'category' => $data['category'],
            'subcategory' => $data['subcategory'] ?? null,
            'key_name' => $data['key_name'],
            'image_url' => $data['image_url'],
            'alt_text' => $data['alt_text'] ?? [],
            'title' => $data['title'] ?? [],
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? 'active'
        ];

        $id = $this->pictures->create($pictureData);

        if ($id) {
            $newPicture = $this->pictures->getById($id);
            ApiResponse::success($newPicture, 'Picture created successfully', 201);
        } else {
            ApiResponse::error('Failed to create picture', 500, 'CREATE_FAILED');
        }
    }

    // PUT /api/v1/pictures/{id}
    public function update($id) {
        if (!is_numeric($id)) {
            ApiResponse::error('Invalid picture ID', 400, 'INVALID_ID');
        }

        // Check if picture exists
        $existingPicture = $this->pictures->getById($id);
        if (!$existingPicture) {
            ApiResponse::error('Picture not found', 404, 'PICTURE_NOT_FOUND');
        }

        $data = $this->getRequestData();

        // Validate required fields
        $this->validateRequired($data, ['category', 'key_name', 'image_url']);

        // Prepare data
        $pictureData = [
            'category' => $data['category'],
            'subcategory' => $data['subcategory'] ?? null,
            'key_name' => $data['key_name'],
            'image_url' => $data['image_url'],
            'alt_text' => $data['alt_text'] ?? [],
            'title' => $data['title'] ?? [],
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? 'active'
        ];

        if ($this->pictures->update($id, $pictureData)) {
            $updatedPicture = $this->pictures->getById($id);
            ApiResponse::success($updatedPicture, 'Picture updated successfully');
        } else {
            ApiResponse::error('Failed to update picture', 500, 'UPDATE_FAILED');
        }
    }

    // DELETE /api/v1/pictures/{id}
    public function destroy($id) {
        if (!is_numeric($id)) {
            ApiResponse::error('Invalid picture ID', 400, 'INVALID_ID');
        }

        if (!$this->pictures->getById($id)) {
            ApiResponse::error('Picture not found', 404, 'PICTURE_NOT_FOUND');
        }

        if ($this->pictures->delete($id)) {
            ApiResponse::success(null, 'Picture deleted successfully');
        } else {
            ApiResponse::error('Failed to delete picture', 500, 'DELETE_FAILED');
        }
    }

    // POST /api/v1/pictures/bulk
    public function bulkInsert() {
        $data = $this->getRequestData();

        if (!isset($data['images']) || !is_array($data['images'])) {
            ApiResponse::error('Images array is required', 400, 'MISSING_IMAGES_ARRAY');
        }

        if (empty($data['images'])) {
            ApiResponse::error('Images array cannot be empty', 400, 'EMPTY_IMAGES_ARRAY');
        }

        // Validate each image data
        foreach ($data['images'] as $index => $imageData) {
            if (empty($imageData['category'])) {
                ApiResponse::error("Category is required for image at index {$index}", 400, 'MISSING_CATEGORY');
            }
            if (empty($imageData['key_name'])) {
                ApiResponse::error("Key name is required for image at index {$index}", 400, 'MISSING_KEY_NAME');
            }
            if (empty($imageData['image_url'])) {
                ApiResponse::error("Image URL is required for image at index {$index}", 400, 'MISSING_IMAGE_URL');
            }
        }

        // Process images data
        $processedImages = [];
        foreach ($data['images'] as $imageData) {
            $processedImages[] = [
                'category' => $imageData['category'],
                'subcategory' => $imageData['subcategory'] ?? null,
                'key_name' => $imageData['key_name'],
                'image_url' => $imageData['image_url'],
                'alt_text' => $imageData['alt_text'] ?? [],
                'title' => $imageData['title'] ?? [],
                'sort_order' => $imageData['sort_order'] ?? 0,
                'status' => $imageData['status'] ?? 'active'
            ];
        }

        if ($this->pictures->bulkInsert($processedImages)) {
            ApiResponse::success(null, 'Pictures bulk inserted successfully', 201);
        } else {
            ApiResponse::error('Failed to bulk insert pictures', 500, 'BULK_INSERT_FAILED');
        }
    }
}