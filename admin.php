<?php
require_once 'config.php';
requireLogin();

// Ambil statistik
$stmt = $db->query("SELECT COUNT(*) as total FROM articles");
$totalArticles = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM articles WHERE status = 'published'");
$publishedArticles = $stmt->fetch()['total'];

$stmt = $db->query("SELECT SUM(views) as total FROM articles");
$totalViews = $stmt->fetch()['total'] ?? 0;

// Ambil artikel terbaru
$stmt = $db->query("SELECT * FROM articles ORDER BY created_at DESC LIMIT 15");
$recentArticles = $stmt->fetchAll();

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Hapus gambar jika ada
    $stmt = $db->prepare("SELECT featured_image FROM articles WHERE id = ?");
    $stmt->execute([$id]);
    $article = $stmt->fetch();
    if ($article && $article['featured_image'] && file_exists(UPLOAD_DIR . $article['featured_image'])) {
        unlink(UPLOAD_DIR . $article['featured_image']);
    }
    
    // Hapus artikel
    $stmt = $db->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->execute([$id]);
    redirect('admin.php?success=deleted');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MyBlog</title>
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
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #27ae60;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .card-title {
            font-size: 1.5rem;
            color: #2c3e50;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }

        @media (max-width: 768px) {
            table {
                font-size: 0.875rem;
            }
            
            .actions {
                flex-direction: column;
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
            <div class="user-info">
                <a href="index.php" class="btn btn-primary btn-sm" target="_blank">🌐 Lihat Website</a>
                <span>Halo, <?= $_SESSION['admin_username'] ?></span>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <?php
                    if ($_GET['success'] == 'created') echo '✅ Artikel berhasil dibuat!';
                    if ($_GET['success'] == 'updated') echo '✅ Artikel berhasil diupdate!';
                    if ($_GET['success'] == 'deleted') echo '✅ Artikel berhasil dihapus!';
                ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $totalArticles ?></div>
                <div class="stat-label">Total Artikel</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?= $publishedArticles ?></div>
                <div class="stat-label">Artikel Published</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?= number_format($totalViews) ?></div>
                <div class="stat-label">Total Views</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?= $totalArticles - $publishedArticles ?></div>
                <div class="stat-label">Draft</div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Kelola Artikel</h2>
                <a href="article-form.php" class="btn btn-success">+ Tambah Artikel</a>
            </div>
            
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Penulis</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentArticles as $article): ?>
                        <tr>
                            <td><?= htmlspecialchars(substr($article['title'], 0, 50)) ?><?= strlen($article['title']) > 50 ? '...' : '' ?></td>
                            <td><?= htmlspecialchars($article['category']) ?></td>
                            <td><?= htmlspecialchars($article['author_name']) ?></td>
                            <td>
                                <span class="badge <?= $article['status'] == 'published' ? 'badge-success' : 'badge-warning' ?>">
                                    <?= ucfirst($article['status']) ?>
                                </span>
                            </td>
                            <td><?= number_format($article['views']) ?></td>
                            <td><?= date('d/m/Y', strtotime($article['created_at'])) ?></td>
                            <td class="actions">
                                <a href="article-detail.php?id=<?= $article['id'] ?>" class="btn btn-primary btn-sm" target="_blank">👁️ Lihat</a>
                                <a href="article-form.php?id=<?= $article['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                                <a href="admin.php?delete=<?= $article['id'] ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Yakin ingin menghapus artikel ini?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>