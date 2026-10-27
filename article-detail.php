<?php
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch article
$stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ? AND status = 'published'");
$stmt->execute([$id]);
$article = $stmt->fetch();

if (!$article) {
    header('Location: articles.php');
    exit();
}

// Update views count
$updateViews = $pdo->prepare("UPDATE articles SET views = views + 1 WHERE id = ?");
$updateViews->execute([$id]);

// Fetch related articles
$relatedStmt = $pdo->prepare("
    SELECT id, title, publish_date, featured_image 
    FROM articles 
    WHERE id != ? AND status = 'published' 
    ORDER BY RAND() 
    LIMIT 3
");
$relatedStmt->execute([$id]);
$relatedArticles = $relatedStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?> - guepedia blog website</title>
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

        .breadcrumb {
            max-width: 900px;
            margin: 30px auto 0;
            padding: 0 20px;
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        .breadcrumb a {
            color: #27ae60;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .article-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .article-header {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .category-badge {
            display: inline-block;
            background: #27ae60;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            font-size: 0.85rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .article-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
            line-height: 1.3;
            margin-bottom: 20px;
        }

        .article-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #ecf0f1;
            flex-wrap: wrap;
        }

        .author-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #27ae60 0%, #16a085 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .author-details {
            display: flex;
            flex-direction: column;
        }

        .author-name {
            font-weight: bold;
            color: #2c3e50;
        }

        .meta-info {
            display: flex;
            gap: 15px;
            align-items: center;
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        .featured-image {
            width: 100%;
            max-height: 450px;
            object-fit: cover;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .article-content {
            background: white;
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .article-content h2 {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-top: 35px;
            margin-bottom: 20px;
            padding-left: 20px;
            border-left: 5px solid #27ae60;
        }

        .article-content h3 {
            font-size: 1.4rem;
            color: #34495e;
            margin-top: 30px;
            margin-bottom: 15px;
        }

        .article-content p {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #555;
            margin-bottom: 20px;
            text-align: justify;
        }

        .article-content ul,
        .article-content ol {
            margin: 20px 0 20px 30px;
        }

        .article-content li {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #555;
            margin-bottom: 12px;
        }

        .article-content img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin: 20px 0;
        }

        .share-section {
            background: white;
            padding: 30px 40px;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .share-title {
            font-size: 1.1rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .share-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .share-btn {
            padding: 12px 25px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.3s;
            border: none;
            font-size: 0.95rem;
        }

        .share-btn:hover {
            transform: translateY(-2px);
        }

        .share-btn.facebook { background: #3b5998; }
        .share-btn.twitter { background: #1da1f2; }
        .share-btn.whatsapp { background: #25d366; }

        .related-articles {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .related-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 25px;
            padding-left: 20px;
            border-left: 5px solid #27ae60;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .related-card {
            background: #f8f9fa;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .related-thumbnail {
            width: 100%;
            height: 150px;
            object-fit: cover;
            background: linear-gradient(135deg, #27ae60 0%, #16a085 100%);
        }

        .related-content {
            padding: 20px;
        }

        .related-card-title {
            font-size: 1rem;
            font-weight: bold;
            color: #2c3e50;
            line-height: 1.4;
            margin-bottom: 8px;
        }

        .related-date {
            font-size: 0.8rem;
            color: #95a5a6;
        }

        footer {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            padding: 40px 20px;
            margin-top: 60px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .article-title {
                font-size: 1.8rem;
            }

            .article-header,
            .article-content {
                padding: 25px;
            }

            .related-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="index.php" class="logo">
                <span class="logo-highlight">guepedia</span><span>.com</span>
            </a>
            <nav>
                <a href="index.php">Home</a>
                <a href="articles.php">Artikel</a>
                <a href="#teknologi">Teknologi</a>
                <a href="#lifestyle">Lifestyle</a>
            </nav>
        </div>
    </header>

    <div class="breadcrumb">
        <a href="index.php">Home</a> / <a href="articles.php">Artikel</a> / <?= htmlspecialchars($article['title']) ?>
    </div>

    <div class="article-container">
        <div class="article-header">
           <span class="category-badge"><?= strtoupper(htmlspecialchars($article['category'])) ?></span>
            <h1 class="article-title"><?= htmlspecialchars($article['title']) ?></h1>
            <div class="article-meta">
                <div class="author-info">
                    <div class="author-avatar">
                        <?= strtoupper(substr($article['author'], 0, 1)) ?>
                    </div>
                    <div class="author-details">
                        <div class="author-name"><?= htmlspecialchars($article['author']) ?></div>
                    </div>
                </div>
                <div class="meta-info">
                    <span>📅 <?= date('d F Y', strtotime($article['publish_date'])) ?></span>
                    <span>•</span>
                    <span>👁️ <?= number_format($article['views']) ?> views</span>
                </div>
            </div>
        </div>

        <?php if (!empty($article['featured_image'])): ?>
        <img src="<?= htmlspecialchars($article['featured_image']) ?>" 
             alt="<?= htmlspecialchars($article['title']) ?>" 
             class="featured-image">
        <?php endif; ?>

        <div class="article-content">
            <?= $article['content'] ?>
        </div>

        <div class="share-section">
            <div class="share-title">Bagikan Artikel:</div>
            <div class="share-buttons">
                <button class="share-btn facebook" onclick="shareArticle('facebook')">📘 Facebook</button>
                <button class="share-btn twitter" onclick="shareArticle('twitter')">🐦 Twitter</button>
                <button class="share-btn whatsapp" onclick="shareArticle('whatsapp')">💬 WhatsApp</button>
            </div>
        </div>

        <?php if (!empty($relatedArticles)): ?>
        <div class="related-articles">
            <h2 class="related-title">Artikel Terkait</h2>
            <div class="related-grid">
                <?php foreach ($relatedArticles as $related): ?>
                <div class="related-card" onclick="window.location.href='article-detail.php?id=<?= $related['id'] ?>'">
                    <?php if (!empty($related['featured_image'])): ?>
                        <img src="<?= htmlspecialchars($related['featured_image']) ?>" 
                             alt="<?= htmlspecialchars($related['title']) ?>" 
                             class="related-thumbnail">
                    <?php else: ?>
                        <div class="related-thumbnail"></div>
                    <?php endif; ?>
                    <div class="related-content">
                        <h3 class="related-card-title"><?= htmlspecialchars($related['title']) ?></h3>
                        <div class="related-date"><?= date('d F Y', strtotime($related['publish_date'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer>
        <div style="max-width: 1400px; margin: 0 auto;">
            <div class="logo" style="justify-content: center; margin-bottom: 20px; font-size: 2rem;">
                <span class="logo-highlight">guepedia</span><span>.com</span>
            </div>
            <p>Platform berbagi informasi terpercaya untuk Anda</p>
            <p style="opacity: 0.6; font-size: 0.9rem; margin-top: 20px;">© 2025 guepedia.com. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function shareArticle(platform) {
            const url = window.location.href;
            const title = document.querySelector('.article-title').textContent;

            switch (platform) {
                case 'facebook':
                    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank');
                    break;
                case 'twitter':
                    window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`, '_blank');
                    break;
                case 'whatsapp':
                    window.open(`https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`, '_blank');
                    break;
            }
        }
    </script>
</body>
</html>