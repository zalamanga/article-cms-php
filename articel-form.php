<?php
require_once 'config.php';
requireLogin();

$isEdit = isset($_GET['id']);
$article = null;

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $article = $stmt->fetch();
    
    if (!$article) {
        redirect('admin.php');
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $category = $_POST['category'];
    $author_name = trim($_POST['author_name']);
    $author_title = trim($_POST['author_title']);
    $content = $_POST['content'];
    $excerpt = trim($_POST['excerpt']);
    $tags = trim($_POST['tags']);
    $status = $_POST['status'];
    $reading_time = intval($_POST['reading_time']);
    
    // Generate slug dari title
    $slug = generateSlug($title);
    
    // Handle upload gambar
    $featured_image = $article['featured_image'] ?? '';
    
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
        $file = $_FILES['featured_image'];
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $file['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $error = 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF.';
        } elseif ($file['size'] > MAX_FILE_SIZE) {
            $error = 'Ukuran file terlalu besar. Maksimal 5MB.';
        } else {
            // Buat folder uploads jika belum ada
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }
            
            // Generate nama file unik
            $new_filename = time() . '_' . uniqid() . '.' . $ext;
            $upload_path = UPLOAD_DIR . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Hapus gambar lama jika ada
                if ($featured_image && file_exists(UPLOAD_DIR . $featured_image)) {
                    unlink(UPLOAD_DIR . $featured_image);
                }
                $featured_image = $new_filename;
            } else {
                $error = 'Gagal upload file.';
            }
        }
    }
    
    if (!$error) {
        try {
            if ($isEdit) {
                // Update artikel
                $stmt = $db->prepare("
                    UPDATE articles SET 
                        title = ?, slug = ?, category = ?, author_name = ?, 
                        author_title = ?, content = ?, excerpt = ?, 
                        featured_image = ?, tags = ?, status = ?, 
                        reading_time = ?, published_date = ?
                    WHERE id = ?
                ");
                
                $published_date = ($status === 'published') ? date('Y-m-d') : $article['published_date'];
                
                $stmt->execute([
                    $title, $slug, $category, $author_name, $author_title,
                    $content, $excerpt, $featured_image, $tags, $status,
                    $reading_time, $published_date, $_GET['id']
                ]);
                
                redirect('admin.php?success=updated');
            } else {
                // Insert artikel baru
                $stmt = $db->prepare("
                    INSERT INTO articles (
                        title, slug, category, author_name, author_title,
                        content, excerpt, featured_image, tags, status,
                        reading_time, published_date
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $published_date = ($status === 'published') ? date('Y-m-d') : null;
                
                $stmt->execute([
                    $title, $slug, $category, $author_name, $author_title,
                    $content, $excerpt, $featured_image, $tags, $status,
                    $reading_time, $published_date
                ]);
                
                redirect('admin.php?success=created');
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Judul sudah digunakan. Gunakan judul yang berbeda.';
            } else {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit' : 'Tambah' ?> Artikel - MyBlog Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .header {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .card-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 1.8rem;
            color: #2c3e50;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        input[type="text"],
        input[type="number"],
        input[type="file"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: border 0.3s;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #27ae60;
        }
        
        textarea {
            min-height: 400px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }
        
        .image-preview {
            max-width: 300px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }
        
        .help-text {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <span style="color: #fff;">My</span>Blog Admin
            </div>
            <a href="admin.php" class="btn btn-secondary">← Kembali</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= $isEdit ? 'Edit' : 'Tambah' ?> Artikel</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Judul Artikel *</label>
                    <input type="text" name="title" required 
                           value="<?= htmlspecialchars($article['title'] ?? '') ?>">
                    <div class="help-text">Slug akan otomatis dibuat dari judul</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Kategori *</label>
                        <select name="category" required>
                            <option value="">Pilih Kategori</option>
                            <option value="Kesehatan" <?= ($article['category'] ?? '') == 'Kesehatan' ? 'selected' : '' ?>>Kesehatan</option>
                            <option value="Teknologi" <?= ($article['category'] ?? '') == 'Teknologi' ? 'selected' : '' ?>>Teknologi</option>
                            <option value="Lifestyle" <?= ($article['category'] ?? '') == 'Lifestyle' ? 'selected' : '' ?>>Lifestyle</option>
                            <option value="Tips & Trik" <?= ($article['category'] ?? '') == 'Tips & Trik' ? 'selected' : '' ?>>Tips & Trik</option>
                            <option value="Olahraga" <?= ($article['category'] ?? '') == 'Olahraga' ? 'selected' : '' ?>>Olahraga</option>
                            <option value="Pendidikan" <?= ($article['category'] ?? '') == 'Pendidikan' ? 'selected' : '' ?>>Pendidikan</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" required>
                            <option value="draft" <?= ($article['status'] ?? 'draft') == 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= ($article['status'] ?? '') == 'published' ? 'selected' : '' ?>>Published</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Penulis *</label>
                        <input type="text" name="author_name" required 
                               value="<?= htmlspecialchars($article['author_name'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Jabatan Penulis</label>
                        <input type="text" name="author_title" 
                               value="<?= htmlspecialchars($article['author_title'] ?? '') ?>"
                               placeholder="Contoh: Spesialis Nefrologi">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Gambar Utama</label>
                    <input type="file" name="featured_image" accept="image/*">
                    <div class="help-text">Format: JPG, PNG, GIF. Maksimal 5MB</div>
                    <?php if (($article['featured_image'] ?? '')): ?>
                        <img src="<?= UPLOAD_DIR . $article['featured_image'] ?>" 
                             class="image-preview" alt="Preview">
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>Ringkasan/Excerpt *</label>
                    <textarea name="excerpt" rows="3" required><?= htmlspecialchars($article['excerpt'] ?? '') ?></textarea>
                    <div class="help-text">Ringkasan singkat artikel (150-200 karakter)</div>
                </div>
                
                <div class="form-group">
                    <label>Konten Artikel *</label>
                    <textarea name="content" required><?= htmlspecialchars($article['content'] ?? '') ?></textarea>
                    <div class="help-text">Gunakan HTML untuk formatting. Contoh: &lt;h2&gt;Judul&lt;/h2&gt; &lt;p&gt;Paragraf&lt;/p&gt; &lt;ul&gt;&lt;li&gt;List&lt;/li&gt;&lt;/ul&gt;</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tags</label>
                        <input type="text" name="tags" 
                               value="<?= htmlspecialchars($article['tags'] ?? '') ?>"
                               placeholder="Contoh: Kesehatan, Tips, Ginjal">
                        <div class="help-text">Pisahkan dengan koma (,)</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Waktu Baca (menit) *</label>
                        <input type="number" name="reading_time" min="1" required 
                               value="<?= $article['reading_time'] ?? 5 ?>">
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-success">
                        <?= $isEdit ? '💾 Update' : '✅ Simpan' ?> Artikel
                    </button>
                    <a href="admin.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>