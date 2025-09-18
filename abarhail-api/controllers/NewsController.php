<?php
require_once 'core/BaseController.php';
require_once 'models/News.php';
require_once 'core/ApiResponse.php';

class NewsController extends BaseController {
    private $news;

    public function __construct($database) {
        parent::__construct($database);
        $this->news = new News($this->db);
    }

    // GET /api/v1/news
    public function index() {
        list($page, $limit) = $this->getPaginationParams();
        $status = $_GET['status'] ?? 'published';

        $newsItems = $this->news->getAll($page, $limit);
        $total = $this->news->getCount($status);

        ApiResponse::paginated($newsItems, $total, $page, $limit, 'News retrieved successfully');
    }

    // GET /api/v1/news/{id}
    public function show($id) {
        if (!is_numeric($id)) {
            ApiResponse::error('Invalid news ID', 400, 'INVALID_ID');
        }

        $newsItem = $this->news->getById($id);
        
        if (!$newsItem) {
            ApiResponse::error('News not found', 404, 'NEWS_NOT_FOUND');
        }

        ApiResponse::success($newsItem, 'News retrieved successfully');
    }

    // POST /api/v1/news
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
        $newsData = [
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

        $id = $this->news->create($newsData);

        if ($id) {
            $newNews = $this->news->getById($id);
            ApiResponse::success($newNews, 'News created successfully', 201);
        } else {
            ApiResponse::error('Failed to create news', 500, 'CREATE_FAILED');
        }
    }

    // PUT /api/v1/news/{id}
    public function update($id) {
        if (!is_numeric($id)) {
            ApiResponse::error('Invalid news ID', 400, 'INVALID_ID');
        }

        // Check if news exists
        $existingNews = $this->news->getById($id);
        if (!$existingNews) {
            ApiResponse::error('News not found', 404, 'NEWS_NOT_FOUND');
        }

        $data = $this->getRequestData();

        // Validate required fields
        $this->validateRequired($data, ['title', 'content']);

        // Prepare data
        $newsData = [
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

        if ($this->news->update($id, $newsData)) {
            $updatedNews = $this->news->getById($id);
            ApiResponse::success($updatedNews, 'News updated successfully');
        } else {
            ApiResponse::error('Failed to update news', 500, 'UPDATE_FAILED');
        }
    }

    // DELETE /api/v1/news/{id}
    public function destroy($id) {
        if (!is_numeric($id)) {
            ApiResponse::error('Invalid news ID', 400, 'INVALID_ID');
        }

        if (!$this->news->getById($id)) {
            ApiResponse::error('News not found', 404, 'NEWS_NOT_FOUND');
        }

        if ($this->news->delete($id)) {
            ApiResponse::success(null, 'News deleted successfully');
        } else {
            ApiResponse::error('Failed to delete news', 500, 'DELETE_FAILED');
        }
    }
}
