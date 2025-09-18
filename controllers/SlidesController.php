<?php

require_once 'core/BaseController.php';
require_once 'models/Slides.php';
require_once 'core/ApiResponse.php';

class SlidesController extends BaseController {
    private $slides;

    public function __construct($database) {
        parent::__construct($database);
        $this->slides = new Slides($this->db);
    }

    // GET /api/v1/slides
    public function index() {
        list($page, $limit) = $this->getPaginationParams();
        $status = $_GET['status'] ?? 'published';

        $slidesItems = $this->slides->getAll($page, $limit);
        $total = $this->slides->getCount($status);

        ApiResponse::paginated($slidesItems, $total, $page, $limit, 'Slides retrieved successfully');
    }

    // GET /api/v1/slides/{id}
    public function show($id) {
        if (!is_numeric($id)) {
            ApiResponse::error('Invalid slide ID', 400, 'INVALID_ID');
        }

        $slide = $this->slides->getById($id);
        
        if (!$slide) {
            ApiResponse::error('Slide not found', 404, 'SLIDE_NOT_FOUND');
        }

        ApiResponse::success($slide, 'Slide retrieved successfully');
    }

    // POST /api/v1/slides
    public function store() {
        $data = $this->getRequestData();

        // Validate required fields
        $this->validateRequired($data, ['image', 'title']);

        // Validate Arabic title (required)
        if (empty($data['title']['ar'])) {
            ApiResponse::error('Arabic title is required', 400, 'MISSING_AR_TITLE');
        }

        // Validate image URL
        if (empty($data['image'])) {
            ApiResponse::error('Image is required', 400, 'MISSING_IMAGE');
        }

        // Prepare data
        $slideData = [
            'image' => $data['image'],
            'title' => [
                'ar' => $data['title']['ar'],
                'en' => $data['title']['en'] ?? ''
            ],
            'description' => [
                'ar' => $data['description']['ar'] ?? '',
                'en' => $data['description']['en'] ?? ''
            ],
            'button_text' => [
                'ar' => $data['button_text']['ar'] ?? '',
                'en' => $data['button_text']['en'] ?? ''
            ],
            'link' => $data['link'] ?? '',
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? 'published'
        ];

        $id = $this->slides->create($slideData);

        if ($id) {
            $newSlide = $this->slides->getById($id);
            ApiResponse::success($newSlide, 'Slide created successfully', 201);
        } else {
            ApiResponse::error('Failed to create slide', 500, 'CREATE_FAILED');
        }
    }

    // PUT /api/v1/slides/{id}
    public function update($id) {
        if (!is_numeric($id)) {
            ApiResponse::error('Invalid slide ID', 400, 'INVALID_ID');
        }

        // Check if slide exists
        $existingSlide = $this->slides->getById($id);
        if (!$existingSlide) {
            ApiResponse::error('Slide not found', 404, 'SLIDE_NOT_FOUND');
        }

        $data = $this->getRequestData();

        // Validate required fields
        $this->validateRequired($data, ['image', 'title']);

        // Prepare data
        $slideData = [
            'image' => $data['image'],
            'title' => [
                'ar' => $data['title']['ar'],
                'en' => $data['title']['en'] ?? ''
            ],
            'description' => [
                'ar' => $data['description']['ar'] ?? '',
                'en' => $data['description']['en'] ?? ''
            ],
            'button_text' => [
                'ar' => $data['button_text']['ar'] ?? '',
                'en' => $data['button_text']['en'] ?? ''
            ],
            'link' => $data['link'] ?? '',
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? 'published'
        ];

        if ($this->slides->update($id, $slideData)) {
            $updatedSlide = $this->slides->getById($id);
            ApiResponse::success($updatedSlide, 'Slide updated successfully');
        } else {
            ApiResponse::error('Failed to update slide', 500, 'UPDATE_FAILED');
        }
    }

    // DELETE /api/v1/slides/{id}
    public function destroy($id) {
        if (!is_numeric($id)) {
            ApiResponse::error('Invalid slide ID', 400, 'INVALID_ID');
        }

        if (!$this->slides->getById($id)) {
            ApiResponse::error('Slide not found', 404, 'SLIDE_NOT_FOUND');
        }

        if ($this->slides->delete($id)) {
            ApiResponse::success(null, 'Slide deleted successfully');
        } else {
            ApiResponse::error('Failed to delete slide', 500, 'DELETE_FAILED');
        }
    }

    // POST /api/v1/slides/reorder
    public function reorder() {
        $data = $this->getRequestData();
        
        if (!isset($data['slide_ids']) || !is_array($data['slide_ids'])) {
            ApiResponse::error('Slide IDs array is required', 400, 'MISSING_SLIDE_IDS');
        }

        if ($this->slides->reorderSlides($data['slide_ids'])) {
            ApiResponse::success(null, 'Slides reordered successfully');
        } else {
            ApiResponse::error('Failed to reorder slides', 500, 'REORDER_FAILED');
        }
    }

    // PATCH /api/v1/slides/{id}/sort-order
    public function updateSortOrder($id) {
        if (!is_numeric($id)) {
            ApiResponse::error('Invalid slide ID', 400, 'INVALID_ID');
        }

        if (!$this->slides->getById($id)) {
            ApiResponse::error('Slide not found', 404, 'SLIDE_NOT_FOUND');
        }

        $data = $this->getRequestData();
        
        if (!isset($data['sort_order']) || !is_numeric($data['sort_order'])) {
            ApiResponse::error('Sort order is required and must be numeric', 400, 'INVALID_SORT_ORDER');
        }

        if ($this->slides->updateSortOrder($id, $data['sort_order'])) {
            $updatedSlide = $this->slides->getById($id);
            ApiResponse::success($updatedSlide, 'Slide sort order updated successfully');
        } else {
            ApiResponse::error('Failed to update slide sort order', 500, 'UPDATE_SORT_ORDER_FAILED');
        }
    }
}