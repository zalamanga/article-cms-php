<?php
// Protect this page with authentication
require_once 'auth_check.php';
require_once 'config.php';

// Function to process images in content
function processContentImages($content) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    
    $images = $dom->getElementsByTagName('img');
    $processed_content = $content;
    
    for ($i = $images->length - 1; $i >= 0; $i--) {
        $img = $images->item($i);
        $src = $img->getAttribute('src');
        
        if (strpos($src, 'data:image') === 0) {
            $image_parts = explode(';base64,', $src);
            $image_type_aux = explode('image/', $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = $image_parts[1];
            
            $upload_dir = 'assets/content/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = uniqid() . '.' . $image_type;
            $file_path = $upload_dir . $file_name;
            
            file_put_contents($file_path, base64_decode($image_base64));
            $processed_content = str_replace($src, $file_path, $processed_content);
        }
    }
    
    return $processed_content;
}

// Daftar kategori yang tersedia
$categories = [
    'Kesehatan',
    'Teknologi',
    'Lifestyle',
    'Tips & Trik',
    'Olahraga',
    'Pendidikan',
    'Bisnis',
    'Kuliner',
    'Travel',
    'Entertainment'
];

// PHP configuration for file upload
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('memory_limit', '256M');
set_time_limit(300);
ini_set('max_input_time', '300');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Image resize function
function resizeAndSaveImage($sourceFile, $targetPath, $maxWidth = 800, $maxHeight = 600) {
    list($width, $height, $type) = getimagesize($sourceFile);
    
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    if($type == IMAGETYPE_PNG) {
        imagecolortransparent($new_image, imagecolorallocate($new_image, 0, 0, 0));
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
    }
    
    switch($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourceFile);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourceFile);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($sourceFile);
            break;
        default:
            return false;
    }
    
    imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    switch($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($new_image, $targetPath, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($new_image, $targetPath, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($new_image, $targetPath);
            break;
    }
    
    imagedestroy($new_image);
    imagedestroy($source);
    
    return true;
}

// Handle article operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $title = $_POST['title'];
                $category = $_POST['category'];
                $content = processContentImages($_POST['content']);
                $author = $_POST['author'];
                
                $featured_image = '';
                if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 5 * 1024 * 1024;
                    
                    $file_type = $_FILES['featured_image']['type'];
                    $file_size = $_FILES['featured_image']['size'];
                    
                    if (!in_array($file_type, $allowed_types)) {
                        die('File type not allowed');
                    }
                    
                    if ($file_size > $max_size) {
                        die('File too large');
                    }
                    
                    $upload_dir = 'assets/img/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
                    $unique_filename = uniqid() . '.' . $file_extension;
                    $featured_image = $upload_dir . $unique_filename;
                    
                    if (!resizeAndSaveImage($_FILES['featured_image']['tmp_name'], $featured_image)) {
                        die('Failed to process image');
                    }
                }
                
                $stmt = $pdo->prepare("INSERT INTO articles (title, category, content, author, featured_image, publish_date, status) VALUES (?, ?, ?, ?, ?, ?, 'draft')");
                $stmt->execute([$title, $category, $content, $author, $featured_image, $_POST['publish_date']]);
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
                break;

            case 'update':
                $id = $_POST['article_id'];
                $title = $_POST['title'];
                $category = $_POST['category'];
                $content = processContentImages($_POST['content']);
                $author = $_POST['author'];
                
                $featured_image = $_POST['current_image'];
                if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 5 * 1024 * 1024;
                    
                    $file_type = $_FILES['featured_image']['type'];
                    $file_size = $_FILES['featured_image']['size'];
                    
                    if (!in_array($file_type, $allowed_types)) {
                        die('File type not allowed');
                    }
                    
                    if ($file_size > $max_size) {
                        die('File too large');
                    }
                    
                    $upload_dir = 'assets/img/';
                    $file_extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
                    $unique_filename = uniqid() . '.' . $file_extension;
                    $featured_image = $upload_dir . $unique_filename;
                    
                    if (!resizeAndSaveImage($_FILES['featured_image']['tmp_name'], $featured_image)) {
                        die('Failed to process image');
                    }
                    
                    if (!empty($_POST['current_image']) && file_exists($_POST['current_image'])) {
                        unlink($_POST['current_image']);
                    }
                }
                
                $stmt = $pdo->prepare("UPDATE articles SET title = ?, category = ?, content = ?, author = ?, featured_image = ?, publish_date = ? WHERE id = ?");
                $stmt->execute([$title, $category, $content, $author, $featured_image, $_POST['publish_date'], $id]);
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
                break;

            case 'delete':
                $id = $_POST['article_id'];
                $stmt = $pdo->prepare("SELECT featured_image FROM articles WHERE id = ?");
                $stmt->execute([$id]);
                $article = $stmt->fetch();
                
                if (!empty($article['featured_image']) && file_exists($article['featured_image'])) {
                    unlink($article['featured_image']);
                }
                
                $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
                $stmt->execute([$id]);
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
                break;

            case 'update_status':
                $id = $_POST['article_id'];
                $status = $_POST['status'];
                $stmt = $pdo->prepare("UPDATE articles SET status = ? WHERE id = ?");
                $stmt->execute([$status, $id]);
                echo json_encode(['success' => true]);
                exit;
                break;
        }
    }
}

// Fetch articles dengan filter kategori
$filterCategory = isset($_GET['category']) ? $_GET['category'] : '';
if ($filterCategory) {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE category = ? ORDER BY created_at DESC");
    $stmt->execute([$filterCategory]);
    $articles = $stmt->fetchAll();
} else {
    $articles = $pdo->query("SELECT * FROM articles ORDER BY created_at DESC")->fetchAll();
}

// Hitung jumlah artikel per kategori
$categoryCount = [];
foreach ($categories as $cat) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM articles WHERE category = ?");
    $stmt->execute([$cat]);
    $result = $stmt->fetch();
    $categoryCount[$cat] = $result['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Article Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tiny.cloud/1/z879h2vkvp2801s702gci1i4gvps4263c2xwb6t06fa91302/tinymce/6/tinymce.min.js"></script>
    <style>
        .preview-image {
            max-width: 100%;
            height: auto;
            object-fit: contain;
        }
        
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        
        .sidebar-overlay {
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            position: fixed;
            inset: 0;
            z-index: 40;
        }
        
        .modal-close:hover {
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 50%;
        }

        .category-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .category-filter {
            cursor: pointer;
            transition: all 0.3s;
        }

        .category-filter:hover {
            background-color: #f3f4f6;
        }

        .category-filter.active {
            background-color: #3b82f6;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <div id="sidebarOverlay" class="sidebar-overlay lg:hidden"></div>
        
        <aside class="w-64 bg-gray-800 text-white fixed h-full z-50 sidebar-transition lg:translate-x-0" id="sidebar">
            <div class="p-4">
                <div class="flex items-center justify-between mb-8">
                    <h1 class="text-2xl font-bold">Admin Panel</h1>
                    <button id="closeSidebar" class="lg:hidden text-white hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <nav>
                    <ul class="space-y-2">
                        <li>
                            <a href="admin_articles.php" class="flex items-center p-2 rounded bg-gray-700">
                                <i class="fas fa-newspaper w-6"></i>
                                <span>Articles</span>
                            </a>
                        </li>
                        <li>
                            <a href="logout.php" class="flex items-center p-2 rounded bg-red-500 hover:bg-red-700">
                                <i class="fas fa-sign-out-alt w-6"></i>
                                <span>Logout</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <main class="w-full lg:ml-64 transition-all duration-300">
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between p-4">
                    <div class="flex items-center">
                        <button id="sidebarToggle" class="text-gray-500 hover:text-gray-600 lg:hidden">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-gray-700">
                            <i class="fas fa-user-circle"></i> 
                            <?= htmlspecialchars($_SESSION['admin_username']) ?>
                        </span>
                    </div>
                </div>
            </header>

            <div class="container mx-auto px-4 py-8">
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-2xl font-bold">Article Management</h1>
                    <button onclick="showCreateForm()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                        <i class="fas fa-plus"></i> New Article
                    </button>
                </div>

                <!-- Category Filter -->
                <div class="bg-white rounded-lg shadow p-4 mb-6">
                    <h3 class="font-semibold mb-3 text-gray-700">Filter by Category:</h3>
                    <div class="flex flex-wrap gap-2">
                        <a href="admin_articles.php" class="category-filter px-4 py-2 rounded-lg border <?= !$filterCategory ? 'active' : '' ?>">
                            All (<?= count($articles) ?>)
                        </a>
                        <?php foreach ($categories as $cat): ?>
                        <a href="admin_articles.php?category=<?= urlencode($cat) ?>" 
                           class="category-filter px-4 py-2 rounded-lg border <?= $filterCategory === $cat ? 'active' : '' ?>">
                            <?= htmlspecialchars($cat) ?> (<?= $categoryCount[$cat] ?>)
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6 overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-200 text-gray-700">
                                <th class="text-left py-2 px-4">Title</th>
                                <th class="text-left py-2 px-4">Category</th>
                                <th class="text-left py-2 px-4">Author</th>
                                <th class="text-left py-2 px-4">Date</th>
                                <th class="text-left py-2 px-4">Image</th>
                                <th class="text-left py-2 px-4">Status</th>
                                <th class="text-left py-2 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($articles as $article): ?>
                            <tr class="border-t hover:bg-gray-100">
                                <td class="py-2 px-4"><?= htmlspecialchars($article['title']) ?></td>
                                <td class="py-2 px-4">
                                    <span class="category-badge bg-blue-100 text-blue-800">
                                        <?= htmlspecialchars($article['category']) ?>
                                    </span>
                                </td>
                                <td class="py-2 px-4"><?= htmlspecialchars($article['author']) ?></td>
                                <td class="py-2 px-4"><?= $article['publish_date'] ?></td>
                                <td class="py-2 px-4">
                                    <?php if (!empty($article['featured_image'])): ?>
                                        <img src="<?= htmlspecialchars($article['featured_image']) ?>" alt="Featured" class="h-16 w-16 object-cover rounded">
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-4">
                                    <select onchange="updateStatus(<?= $article['id'] ?>, this.value)" class="border rounded px-2 py-1">
                                        <option value="draft" <?= $article['status'] == 'draft' ? 'selected' : '' ?>>Draft</option>
                                        <option value="published" <?= $article['status'] == 'published' ? 'selected' : '' ?>>Published</option>
                                    </select>
                                </td>
                                <td class="py-2 px-4">
                                    <button onclick="editArticle(<?= $article['id'] ?>)" class="text-blue-500 hover:underline">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button onclick="deleteArticle(<?= $article['id'] ?>)" class="text-red-500 hover:underline ml-2">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Form Modal -->
                <div id="articleForm" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg p-8 max-w-4xl mx-auto mt-20 overflow-y-auto max-h-screen relative">
                        <button onclick="hideForm()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 modal-close p-2">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                        
                        <h2 id="formTitle" class="text-xl font-bold mb-4">Create New Article</h2>
                        <form id="articleFormElement" action="" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="create">
                            <input type="hidden" name="article_id" id="articleId">
                            <input type="hidden" name="current_image" id="currentImage">
                            
                            <div class="mb-4">
                                <label for="articleTitle" class="block mb-2">Title</label>
                                <input type="text" name="title" id="articleTitle" class="w-full border p-2 rounded" required>
                            </div>

                            <div class="mb-4">
                                <label for="articleCategory" class="block mb-2">Category</label>
                                <select name="category" id="articleCategory" class="w-full border p-2 rounded" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="articleAuthor" class="block mb-2">Author</label>
                                <input type="text" name="author" id="articleAuthor" class="w-full border p-2 rounded" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="content" class="block mb-2">Content</label>
                                <textarea id="content" name="content" class="w-full border p-2 rounded"></textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label for="featuredImage" class="block mb-2">Featured Image</label>
                                <input type="file" name="featured_image" id="featuredImage" accept="image/*">
                                <div id="imagePreview" class="mt-2"></div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="publishDate" class="block mb-2">Publish Date</label>
                                <input type="date" name="publish_date" id="publishDate" class="border p-2 rounded" required>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                                    <i class="fas fa-save"></i> Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        tinymce.init({
            selector: '#content',
            height: 500,
            plugins: 'lists link image code',
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image | code',
            image_dimensions: false,
            content_style: 'img {max-width: 100%; height: auto;}',
            setup: function (editor) {
                editor.on('BeforeSetContent', function (e) {
                    if (e.content.includes('<img')) {
                        e.content = e.content.replace(/width=".*?"/g, '');
                        e.content = e.content.replace(/height=".*?"/g, '');
                    }
                });
            }
        });

        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const closeSidebar = document.getElementById('closeSidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.style.display = sidebar.classList.contains('-translate-x-full') ? 'none' : 'block';
        }

        sidebarToggle.addEventListener('click', toggleSidebar);
        closeSidebar.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', toggleSidebar);

        function showCreateForm() {
            document.getElementById('formTitle').textContent = 'Create New Article';
            document.getElementById('articleFormElement').reset();
            document.getElementById('imagePreview').innerHTML = '';
            document.getElementById('articleForm').classList.remove('hidden');
            document.querySelector('input[name="action"]').value = 'create';
        }

        function hideForm() {
            document.getElementById('articleForm').classList.add('hidden');
        }

        async function editArticle(id) {
            try {
                const response = await fetch(`get_article.php?id=${id}`);
                const article = await response.json();
                
                document.getElementById('formTitle').textContent = 'Edit Article';
                document.getElementById('articleId').value = article.id;
                document.getElementById('articleTitle').value = article.title;
                document.getElementById('articleCategory').value = article.category;
                document.getElementById('articleAuthor').value = article.author;
                document.getElementById('currentImage').value = article.featured_image;
                document.getElementById('publishDate').value = article.publish_date;
                tinymce.get('content').setContent(article.content);
                
                const imagePreview = document.getElementById('imagePreview');
                imagePreview.innerHTML = article.featured_image ? 
                    `<img src="${article.featured_image}" alt="Current" class="preview-image mt-2">` : '';
                
                document.querySelector('input[name="action"]').value = 'update';
                document.getElementById('articleForm').classList.remove('hidden');
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to load article');
            }
        }

        function deleteArticle(id) {
            if (confirm('Are you sure you want to delete this article?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="article_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        async function updateStatus(id, status) {
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=update_status&article_id=${id}&status=${status}`
                });
                const result = await response.json();
                if (result.success) {
                    console.log('Status updated');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        document.getElementById('featuredImage').onchange = function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.createElement('img');
                    preview.src = e.target.result;
                    preview.className = 'preview-image mt-2';
                    const container = document.getElementById('imagePreview');
                    container.innerHTML = '';
                    container.appendChild(preview);
                }
                reader.readAsDataURL(file);
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') hideForm();
        });

        function handleResize() {
            if (window.innerWidth >= 1024) {
                sidebar.classList.remove('-translate-x-full');
                sidebarOverlay.style.display = 'none';
            } else {
                sidebar.classList.add('-translate-x-full');
            }
        }

        window.addEventListener('resize', handleResize);
        handleResize();

        document.getElementById('articleForm').addEventListener('click', function(e) {
            if (e.target === this) hideForm();
        });
    </script>
</body>
</html>