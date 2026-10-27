<?php
require_once 'config.php';

// Fetch published articles
$stmt = $pdo->query("
    SELECT id, title, content, author, featured_image, publish_date, views 
    FROM articles 
    WHERE status = 'published' 
    ORDER BY publish_date DESC
");
$articles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artikel - Guepedia.com</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        header {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            padding: 0.5rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            text-decoration: none;
            color: white;
        }

        .logo-highlight {
            color: #7fda40;
        }

        nav {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            padding: 8px 15px;
            border-radius: 5px;
        }

        nav a:hover {
            color: #a8e6cf;
            background: rgba(168, 230, 207, 0.15);
        }

        .articles-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-title {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 40px;
            text-align: center;
        }

        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .article-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .article-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            background: linear-gradient(135deg, #27ae60 0%, #16a085 100%);
        }

        .article-content {
            padding: 25px;
        }

        .article-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 0.85rem;
            color: #7f8c8d;
        }

        .article-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .article-excerpt {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .read-more {
            color: #27ae60;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .read-more:hover {
            color: #229954;
        }

        footer {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            padding: 40px 20px;
            margin-top: 60px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .articles-grid {
                grid-template-columns: 1fr;
            }
            
            .page-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="index.php" class="logo">
                <span class="logo-highlight">Guepedia</span><span>.com</span>
            </a>
            <nav>
                <a href="index.php">Home</a>
                <a href="articles.php">Artikel</a>
                <a href="#teknologi">Teknologi</a>
                <a href="#lifestyle">Lifestyle</a>
            </nav>
        </div>
    </header>

    <div class="articles-container">
        <h1 class="page-title">Semua Artikel</h1>
        
        <div class="articles-grid">
            <?php foreach ($articles as $article): ?>
            <div class="article-card" onclick="window.location.href='article-detail.php?id=<?= $article['id'] ?>'">
                <?php if (!empty($article['featured_image'])): ?>
                    <img src="<?= htmlspecialchars($article['featured_image']) ?>" 
                         alt="<?= htmlspecialchars($article['title']) ?>" 
                         class="article-image">
                <?php else: ?>
                    <div class="article-image"></div>
                <?php endif; ?>
                
                <div class="article-content">
                    <div class="article-meta">
                        <span>📅 <?= date('d F Y', strtotime($article['publish_date'])) ?></span>
                        <span>👁️ <?= number_format($article['views']) ?></span>
                    </div>
                    
                    <h3 class="article-title"><?= htmlspecialchars($article['title']) ?></h3>
                    
                    <p class="article-excerpt">
                        <?= htmlspecialchars(strip_tags(substr($article['content'], 0, 150))) ?>...
                    </p>
                    
                    <a href="article-detail.php?id=<?= $article['id'] ?>" class="read-more">
                        Baca Selengkapnya →
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <footer>
        <div style="max-width: 1400px; margin: 0 auto;">
            <div class="logo" style="justify-content: center; margin-bottom: 20px; font-size: 2rem;">
                <span class="logo-highlight">Guepedia</span><span>.com</span>
            </div>
            <p>Platform berbagi informasi terpercaya untuk Anda</p>
            <p style="opacity: 0.6; font-size: 0.9rem; margin-top: 20px;">© 2025 guepedia.com. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>