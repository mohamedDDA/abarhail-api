<?php
require_once 'core/BaseController.php';
require_once 'models/Social.php';
require_once 'core/ApiResponse.php';

class SocialController extends BaseController {
    private $social;

    public function __construct($database) {
        parent::__construct($database);
        $this->social = new Social($this->db);
    }

    // GET /api/v1/social
    public function index() {
        list($page, $limit) = $this->getPaginationParams();
        $status = $_GET['status'] ?? 'published';

        $socialPosts = $this->social->getAll($page, $limit);
        $total = $this->social->getCount($status);

        ApiResponse::paginated($socialPosts, $total, $page, $limit, 'Social posts retrieved successfully');
    }

    // GET /api/v1/social/{id}
    public function show($id) {
        if (!is_numeric($id)) {
            ApiResponse::error('Invalid social post ID', 400, 'INVALID_ID');
        }

        $socialPost = $this->social->getById($id);
        
        if (!$socialPost) {
            ApiResponse::error('Social post not found', 404, 'SOCIAL_NOT_FOUND');
        }

        ApiResponse::success($socialPost, 'Social post retrieved successfully');
    }

    // POST /api/v1/social
    public function store() {
        $data = $this->getRequestData();

        // Validate required fields
        $this->validateRequired($data, ['title', 'content']);

        // Validate Arabic title and content (required)
        if (empty($data['title']['ar'])) {
            ApiResponse::error('Arabic title is required', 400, 'MISSING_AR_TITLE');
        }

        if (empty($data['content']['ar'])) {
            ApiResponse::error('Arabic content is required', 400, 'MISSING_AR_CONTENT');
        }

        // Prepare data
        $socialData = [
            'title' => [
                'ar' => $data['title']['ar'],
                'en' => $data['title']['en'] ?? ''
            ],
            'content' => [
                'ar' => $data['content']['ar'],
                'en' => $data['content']['en'] ?? ''
            ],
            'excerpt' => [
                'ar' => $data['excerpt']['ar'] ?? '',
                'en' => $data['excerpt']['en'] ?? ''
            ],
            'images' => $data['images'] ?? [],
            'status' => $data['status'] ?? 'published'
        ];

        $id = $this->social->create($socialData);

        if ($id) {
            $newSocial = $this->social->getById($id);
            ApiResponse::success($newSocial, 'Social post created successfully', 201);
        } else {
            ApiResponse::error('Failed to create social post', 500, 'CREATE_FAILED');
        }
    }

    // PUT /api/v1/social/{id}
    public function update($id) {
        if (!is_numeric($id)) {
            ApiResponse::error('Invalid social post ID', 400, 'INVALID_ID');
        }

        // Check if post exists
        $existingSocial = $this->social->getById($id);
        if (!$existingSocial) {
            ApiResponse::error('Social post not found', 404, 'SOCIAL_NOT_FOUND');
        }

        $data = $this->getRequestData();

        // Validate required fields
        $this->validateRequired($data, ['title', 'content']);

        // Prepare data
        $socialData = [
            'title' => [
                'ar' => $data['title']['ar'],
                'en' => $data['title']['en'] ?? ''
            ],
            'content' => [
                'ar' => $data['content']['ar'],
                'en' => $data['content']['en'] ?? ''
            ],
            'excerpt' => [
                'ar' => $data['excerpt']['ar'] ?? '',
                'en' => $data['excerpt']['en'] ?? ''
            ],
            'images' => $data['images'] ?? [],
            'status' => $data['status'] ?? 'published'
        ];

        if ($this->social->update($id, $socialData)) {
            $updatedSocial = $this->social->getById($id);
            ApiResponse::success($updatedSocial, 'Social post updated successfully');
        } else {
            ApiResponse::error('Failed to update social post', 500, 'UPDATE_FAILED');
        }
    }

    // DELETE /api/v1/social/{id}
    public function destroy($id) {
        if (!is_numeric($id)) {
            ApiResponse::error('Invalid social post ID', 400, 'INVALID_ID');
        }

        if (!$this->social->getById($id)) {
            ApiResponse::error('Social post not found', 404, 'SOCIAL_NOT_FOUND');
        }

        if ($this->social->delete($id)) {
            ApiResponse::success(null, 'Social post deleted successfully');
        } else {
            ApiResponse::error('Failed to delete social post', 500, 'DELETE_FAILED');
        }
    }
}
