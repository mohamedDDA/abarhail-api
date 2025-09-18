<?php

require_once 'core/BaseController.php';
require_once 'models/Products.php';
require_once 'core/ApiResponse.php';

class ProductsController extends BaseController {
    private $products;

    public function __construct($database) {
        parent::__construct($database);
        $this->products = new Products($this->db);
    }

    // GET /api/v1/products
    public function index() {
        list($page, $limit) = $this->getPaginationParams();
        
        $productsItems = $this->products->getAll($page, $limit);
        $total = $this->products->getCount();

        ApiResponse::paginated($productsItems, $total, $page, $limit, 'Products retrieved successfully');
    }

    // GET /api/v1/products/{id}
    public function show($id) {
        if (!is_numeric($id)) {
            ApiResponse::error('Invalid product ID', 400, 'INVALID_ID');
        }

        $product = $this->products->getById($id);
        
        if (!$product) {
            ApiResponse::error('Product not found', 404, 'PRODUCT_NOT_FOUND');
        }

        ApiResponse::success($product, 'Product retrieved successfully');
    }

    // POST /api/v1/products
    public function store() {
        $data = $this->getRequestData();

        // Validate required fields
        $this->validateRequired($data, ['title', 'label', 'image']);

        // Validate Arabic fields (required)
        if (empty($data['title']['ar'])) {
            ApiResponse::error('Arabic title is required', 400, 'MISSING_AR_TITLE');
        }

        if (empty($data['label']['ar'])) {
            ApiResponse::error('Arabic label is required', 400, 'MISSING_AR_LABEL');
        }

        // Validate image URL
        if (empty($data['image'])) {
            ApiResponse::error('Image is required', 400, 'MISSING_IMAGE');
        }

        // Prepare data
        $productData = [
            'title' => [
                'ar' => $data['title']['ar'],
                'en' => $data['title']['en'] ?? ''
            ],
            'label' => [
                'ar' => $data['label']['ar'],
                'en' => $data['label']['en'] ?? ''
            ],
            'image' => $data['image']
        ];

        $id = $this->products->create($productData);

        if ($id) {
            $newProduct = $this->products->getById($id);
            ApiResponse::success($newProduct, 'Product created successfully', 201);
        } else {
            ApiResponse::error('Failed to create product', 500, 'CREATE_FAILED');
        }
    }

    // PUT /api/v1/products/{id}
    public function update($id) {
        if (!is_numeric($id)) {
            ApiResponse::error('Invalid product ID', 400, 'INVALID_ID');
        }

        // Check if product exists
        $existingProduct = $this->products->getById($id);
        if (!$existingProduct) {
            ApiResponse::error('Product not found', 404, 'PRODUCT_NOT_FOUND');
        }

        $data = $this->getRequestData();

        // Validate required fields
        $this->validateRequired($data, ['title', 'label', 'image']);

        // Validate Arabic fields (required)
        if (empty($data['title']['ar'])) {
            ApiResponse::error('Arabic title is required', 400, 'MISSING_AR_TITLE');
        }

        if (empty($data['label']['ar'])) {
            ApiResponse::error('Arabic label is required', 400, 'MISSING_AR_LABEL');
        }

        // Prepare data
        $productData = [
            'title' => [
                'ar' => $data['title']['ar'],
                'en' => $data['title']['en'] ?? ''
            ],
            'label' => [
                'ar' => $data['label']['ar'],
                'en' => $data['label']['en'] ?? ''
            ],
            'image' => $data['image']
        ];

        if ($this->products->update($id, $productData)) {
            $updatedProduct = $this->products->getById($id);
            ApiResponse::success($updatedProduct, 'Product updated successfully');
        } else {
            ApiResponse::error('Failed to update product', 500, 'UPDATE_FAILED');
        }
    }

    // DELETE /api/v1/products/{id}
    public function destroy($id) {
        if (!is_numeric($id)) {
            ApiResponse::error('Invalid product ID', 400, 'INVALID_ID');
        }

        if (!$this->products->getById($id)) {
            ApiResponse::error('Product not found', 404, 'PRODUCT_NOT_FOUND');
        }

        if ($this->products->delete($id)) {
            ApiResponse::success(null, 'Product deleted successfully');
        } else {
            ApiResponse::error('Failed to delete product', 500, 'DELETE_FAILED');
        }
    }
}