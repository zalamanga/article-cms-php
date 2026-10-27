<?php
require_once 'config.php';

// Fetch featured articles for banner slider (latest 3 published articles)
$featuredStmt = $pdo->query("
    SELECT * FROM articles 
    WHERE status = 'published' 
    ORDER BY publish_date DESC 
    LIMIT 3
");
$featuredArticles = $featuredStmt->fetchAll();

// Fetch all published articles for the grid
$articlesStmt = $pdo->query("
    SELECT * FROM articles 
    WHERE status = 'published' 
    ORDER BY publish_date DESC 
    LIMIT 9
");
$articles = $articlesStmt->fetchAll();

// Fetch popular articles (most viewed)
$popularStmt = $pdo->query("
    SELECT * FROM articles 
    WHERE status = 'published' 
    ORDER BY views DESC 
    LIMIT 3
");
$popularArticles = $popularStmt->fetchAll();

// Get unique categories with article count
$categoriesStmt = $pdo->query("
    SELECT category, COUNT(*) as count 
    FROM articles 
    WHERE status = 'published' 
    GROUP BY category 
    ORDER BY count DESC
");
$categories = $categoriesStmt->fetchAll();

// Helper function to get excerpt
function getExcerpt($content, $length = 150) {
    $text = strip_tags($content);
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}

// Helper function to format date in Indonesian
function formatIndonesianDate($date) {
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $months[date('n', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "$day $month $year";
}

// Category colors
$categoryColors = [
    'Kesehatan' => '#27ae60',
    'Teknologi' => '#3498db',
    'Lifestyle' => '#e74c3c',
    'Tips & Trik' => '#f39c12',
    'Olahraga' => '#9b59b6',
    'Pendidikan' => '#1abc9c',
    'Bisnis' => '#34495e',
    'Kuliner' => '#e67e22',
    'Travel' => '#16a085',
    'Entertainment' => '#c0392b'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>guepedia blog website</title>
    <style>   
    /* ===== BASE STYLES ===== */
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

/* ===== HEADER ===== */
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

.search-icon,
.theme-toggle {
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: background 0.3s;
}

.search-icon:hover,
.theme-toggle:hover {
    background: rgba(255, 255, 255, 0.1);
}

/* ===== HOT NEWS BANNER ===== */
.hot-news {
    background: white;
    padding: 15px 0;
    border-bottom: 2px solid #e74c3c;
}

.hot-news-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.hot-badge {
    background: #27ae60;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
}

/* ===== FEATURED BANNER SLIDER ===== */
.featured-banner {
    max-width: 1400px;
    margin: 30px auto;
    padding: 0 20px;
}

.banner-slider {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    height: 500px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.banner-slide {
    position: absolute;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.5s ease-in-out;
    cursor: pointer;
}

.banner-slide.active {
    opacity: 1;
}

.banner-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.banner-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.95));
    padding: 80px 50px 50px;
    color: white;
}

.category-badge {
    display: inline-block;
    background: #27ae60;
    color: white;
    padding: 10px 25px;
    border-radius: 5px;
    font-size: 0.9rem;
    font-weight: bold;
    margin-bottom: 20px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.banner-title {
    font-size: 3rem;
    font-weight: bold;
    margin-bottom: 20px;
    line-height: 1.2;
}

.banner-excerpt {
    font-size: 1.2rem;
    line-height: 1.8;
    opacity: 0.95;
    margin-bottom: 25px;
    max-width: 800px;
}

.post-meta {
    display: flex;
    align-items: center;
    gap: 20px;
    font-size: 1rem;
    opacity: 0.9;
    flex-wrap: wrap;
}

.author {
    display: flex;
    align-items: center;
    gap: 10px;
}

.author-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: #7fda40;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    flex-shrink: 0;
}

.author-details {
    display: flex;
    flex-direction: column;
}

.slider-controls {
    position: absolute;
    bottom: 30px;
    right: 50px;
    display: flex;
    gap: 15px;
    z-index: 10;
}

.slider-btn {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid white;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.slider-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

.slider-dots {
    position: absolute;
    bottom: 30px;
    left: 50px;
    display: flex;
    gap: 10px;
}

.dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.4);
    cursor: pointer;
    transition: all 0.3s;
}

.dot.active {
    background: white;
    width: 40px;
    border-radius: 6px;
}

/* ===== CATEGORY FILTER ===== */
.category-filter-section {
    max-width: 1400px;
    margin: 30px auto;
    padding: 0 20px;
}

.category-filters {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    background: white;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
}

.category-filter-btn {
    padding: 10px 20px;
    border: 2px solid #e0e0e0;
    border-radius: 25px;
    background: white;
    color: #333;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    white-space: nowrap;
}

.category-filter-btn:hover {
    border-color: #27ae60;
    background: #27ae60;
    color: white;
}

.category-filter-btn.active {
    border-color: #27ae60;
    background: #27ae60;
    color: white;
}

/* ===== MAIN CONTAINER ===== */
.container {
    max-width: 1400px;
    margin: 30px auto;
    padding: 0 20px;
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

/* ===== SIDEBAR ===== */
.sidebar {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.sidebar-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
}

.sidebar-card h3 {
    margin-bottom: 20px;
    font-size: 1.3rem;
    color: #2c3e50;
}

.weather-widget {
    text-align: center;
}

.weather-temp {
    font-size: 4rem;
    font-weight: bold;
    color: #2c3e50;
}

.weather-location {
    font-size: 1.5rem;
    color: #7f8c8d;
    margin-top: 10px;
}

.weather-details {
    margin-top: 20px;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    font-size: 0.9rem;
    color: #7f8c8d;
}

/* ===== EDITOR'S PICK ===== */
.editors-pick {
    background: white;
    padding: 30px;
    margin: 30px 0;
    border-radius: 15px;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
}

.section-title {
    font-size: 2rem;
    margin-bottom: 30px;
    color: #2c3e50;
    position: relative;
    padding-left: 20px;
}

.section-title::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 5px;
    background: #27ae60;
    border-radius: 3px;
}

.posts-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
}

.post-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    cursor: pointer;
}

.post-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.post-thumbnail {
    width: 100%;
    height: 200px;
    background: linear-gradient(135deg, #27ae60 0%, #16a085 100%);
    position: relative;
    overflow: hidden;
}

.post-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.post-content {
    padding: 20px;
}

.post-category {
    display: inline-block;
    background: #27ae60;
    color: white;
    padding: 5px 12px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: bold;
    margin-bottom: 10px;
    text-transform: uppercase;
}

.post-title {
    font-size: 1.2rem;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 10px;
    line-height: 1.4;
}

.post-excerpt {
    font-size: 0.9rem;
    color: #7f8c8d;
    line-height: 1.6;
    margin-bottom: 15px;
}

.post-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
    color: #95a5a6;
    padding-top: 15px;
    border-top: 1px solid #ecf0f1;
}

/* ===== SOCIAL FOLLOW ===== */
.social-follow {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.social-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 15px;
    transition: transform 0.3s;
    cursor: pointer;
}

.social-card:hover {
    transform: translateY(-3px);
}

.social-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    flex-shrink: 0;
}

.social-icon.facebook {
    background: #3b5998;
}

.social-icon.twitter {
    background: #1da1f2;
}

.social-icon.instagram {
    background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
}

.social-icon.youtube {
    background: #ff0000;
}

.social-info {
    flex: 1;
}

.follower-count {
    font-size: 1.2rem;
    font-weight: bold;
    color: #2c3e50;
}

.social-label {
    font-size: 0.85rem;
    color: #7f8c8d;
}

/* ===== RESPONSIVE DESIGN ===== */

/* Tablet Large (1024px - 1200px) */
@media (max-width: 1200px) {
    .header-container {
        padding: 0 15px;
    }
    
    .banner-overlay {
        padding: 60px 30px 30px;
    }
    
    .banner-title {
        font-size: 2.5rem;
    }
    
    .slider-controls {
        right: 30px;
    }
    
    .slider-dots {
        left: 30px;
    }
}

/* Tablet (768px - 1024px) */
@media (max-width: 1024px) {
    .container {
        grid-template-columns: 1fr;
    }
    
    .posts-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .banner-title {
        font-size: 2rem;
    }
    
    .banner-excerpt {
        font-size: 1rem;
    }
    
    nav {
        gap: 20px;
    }
    
    nav a {
        padding: 6px 10px;
        font-size: 0.9rem;
    }
    
    .sidebar {
        order: -1; /* Sidebar moves to top on tablet */
    }
    
    .weather-temp {
        font-size: 3rem;
    }
}

/* Mobile Large (600px - 768px) */
@media (max-width: 768px) {
    .logo {
        font-size: 1.5rem;
    }
    
    nav a:not(.search-icon) {
        display: none;
    }
    
    nav a:nth-child(1),
    nav a:nth-child(2) {
        display: block;
    }
    
    nav {
        gap: 10px;
    }
    
    .banner-slider {
        height: 400px;
        border-radius: 15px;
    }
    
    .banner-overlay {
        padding: 40px 20px 20px;
    }
    
    .banner-title {
        font-size: 1.5rem;
        margin-bottom: 15px;
    }
    
    .banner-excerpt {
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 15px;
    }
    
    .category-badge {
        padding: 6px 15px;
        font-size: 0.75rem;
        margin-bottom: 12px;
    }
    
    .post-meta {
        font-size: 0.85rem;
        gap: 10px;
    }
    
    .author-avatar {
        width: 30px;
        height: 30px;
        font-size: 0.9rem;
    }
    
    .slider-controls {
        display: none;
    }
    
    .slider-dots {
        left: 20px;
        bottom: 20px;
    }
    
    .hot-news-container {
        font-size: 0.9rem;
        gap: 10px;
    }
    
    .hot-news-container span:last-child {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .category-filters {
        padding: 15px;
        gap: 8px;
    }
    
    .category-filter-btn {
        padding: 8px 16px;
        font-size: 0.85rem;
    }
    
    .posts-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .editors-pick {
        padding: 20px;
    }
    
    .section-title {
        font-size: 1.5rem;
        margin-bottom: 20px;
        padding-left: 15px;
    }
    
    .post-thumbnail {
        height: 180px;
    }
    
    .post-content {
        padding: 15px;
    }
    
    .post-title {
        font-size: 1.1rem;
    }
    
    .sidebar-card {
        padding: 20px;
    }
    
    .weather-temp {
        font-size: 2.5rem;
    }
    
    .weather-location {
        font-size: 1.2rem;
    }
    
    .social-follow {
        grid-template-columns: 1fr;
    }
}

/* Mobile Small (320px - 600px) */
@media (max-width: 600px) {
    .header-container {
        padding: 0 10px;
    }
    
    .logo {
        font-size: 1.3rem;
    }
    
    nav a:nth-child(2) {
        display: none;
    }
    
    .featured-banner {
        margin: 20px auto;
        padding: 0 10px;
    }
    
    .banner-slider {
        height: 350px;
        border-radius: 12px;
    }
    
    .banner-overlay {
        padding: 30px 15px 15px;
    }
    
    .banner-title {
        font-size: 1.2rem;
        margin-bottom: 10px;
    }
    
    .banner-excerpt {
        font-size: 0.85rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .category-badge {
        padding: 5px 12px;
        font-size: 0.7rem;
        margin-bottom: 10px;
    }
    
    .post-meta {
        font-size: 0.75rem;
        gap: 8px;
    }
    
    .author span {
        font-size: 0.8rem;
    }
    
    .slider-dots {
        left: 15px;
        bottom: 15px;
    }
    
    .dot {
        width: 8px;
        height: 8px;
    }
    
    .dot.active {
        width: 24px;
    }
    
    .category-filter-section {
        margin: 20px auto;
        padding: 0 10px;
    }
    
    .category-filters {
        padding: 12px;
        gap: 6px;
        overflow-x: auto;
        flex-wrap: nowrap;
        -webkit-overflow-scrolling: touch;
    }
    
    .category-filters::-webkit-scrollbar {
        display: none;
    }
    
    .category-filter-btn {
        padding: 6px 14px;
        font-size: 0.8rem;
    }
    
    .container {
        margin: 20px auto;
        padding: 0 10px;
        gap: 20px;
    }
    
    .editors-pick {
        padding: 15px;
        margin: 0;
    }
    
    .section-title {
        font-size: 1.3rem;
        margin-bottom: 15px;
        padding-left: 12px;
    }
    
    .section-title::before {
        width: 4px;
    }
    
    .posts-grid {
        gap: 15px;
    }
    
    .post-thumbnail {
        height: 160px;
    }
    
    .post-content {
        padding: 12px;
    }
    
    .post-category {
        padding: 4px 10px;
        font-size: 0.7rem;
    }
    
    .post-title {
        font-size: 1rem;
        margin-bottom: 8px;
    }
    
    .post-excerpt {
        font-size: 0.85rem;
        margin-bottom: 12px;
    }
    
    .post-footer {
        font-size: 0.75rem;
        padding-top: 12px;
    }
    
    .sidebar-card {
        padding: 15px;
    }
    
    .sidebar-card h3 {
        font-size: 1.1rem;
        margin-bottom: 15px;
    }
    
    .weather-temp {
        font-size: 2.2rem;
    }
    
    .weather-location {
        font-size: 1rem;
    }
    
    .weather-details {
        font-size: 0.8rem;
        gap: 8px;
    }
    
    .social-icon {
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
    }
    
    .follower-count {
        font-size: 1rem;
    }
    
    .social-label {
        font-size: 0.75rem;
    }
}

/* Mobile Extra Small (< 375px) */
@media (max-width: 374px) {
    .logo {
        font-size: 1.1rem;
    }
    
    .banner-slider {
        height: 300px;
    }
    
    .banner-title {
        font-size: 1.1rem;
    }
    
    .banner-excerpt {
        font-size: 0.8rem;
    }
    
    .section-title {
        font-size: 1.2rem;
    }
    
    .post-title {
        font-size: 0.95rem;
    }
}

/* Landscape Mode Fix for Mobile */
@media (max-height: 500px) and (orientation: landscape) {
    .banner-slider {
        height: 300px;
    }
    
    .banner-overlay {
        padding: 20px 15px 15px;
    }
    
    .banner-title {
        font-size: 1.2rem;
        margin-bottom: 8px;
    }
    
    .banner-excerpt {
        display: none;
    }
    
    .post-meta {
        font-size: 0.75rem;
    }
}

/* Touch Device Optimization */
@media (hover: none) and (pointer: coarse) {
    .post-card:hover {
        transform: none;
    }
    
    .post-card:active {
        transform: scale(0.98);
    }
    
    .category-filter-btn:hover {
        background: white;
        color: #333;
        border-color: #e0e0e0;
    }
    
    .category-filter-btn:active {
        background: #27ae60;
        color: white;
        border-color: #27ae60;
    }
}

/* Print Styles */
@media print {
    header,
    .hot-news,
    .slider-controls,
    .slider-dots,
    .category-filter-section,
    .sidebar,
    footer {
        display: none;
    }
    
    .container {
        grid-template-columns: 1fr;
    }
    
    .posts-grid {
        grid-template-columns: 1fr;
    }
    
    .post-card {
        page-break-inside: avoid;
        box-shadow: none;
        border: 1px solid #ddd;
    }
}
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <a href="index.php" class="logo">
                <span class="logo-highlight">Guepedia</span><span>.com</span>
            </a>
            <nav>
                <a href="index.php">Home</a>
                <?php foreach (array_slice($categories, 0, 4) as $cat): ?>
                <a href="index.php?category=<?= urlencode($cat['category']) ?>"><?= htmlspecialchars($cat['category']) ?></a>
                <?php endforeach; ?>
                <span class="search-icon">🔍</span>
            </nav>
        </div>
    </header>

    <!-- Hot News Banner -->
    <?php if (!empty($featuredArticles)): ?>
    <div class="hot-news">
        <div class="hot-news-container">
            <span class="hot-badge">🔥 Hot News</span>
            <span><?= htmlspecialchars($featuredArticles[0]['title']) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Featured Banner Slider -->
    <?php if (!empty($featuredArticles)): ?>
    <div class="featured-banner">
        <div class="banner-slider">
            <?php foreach ($featuredArticles as $index => $featured): ?>
            <div class="banner-slide <?= $index === 0 ? 'active' : '' ?>" onclick="window.location.href='article-detail.php?id=<?= $featured['id'] ?>'">
                <?php if (!empty($featured['featured_image'])): ?>
                    <img src="<?= htmlspecialchars($featured['featured_image']) ?>" alt="<?= htmlspecialchars($featured['title']) ?>">
                <?php else: ?>
                    <img src="data:image/svg+xml,%3Csvg width='1400' height='500' xmlns='http://www.w3.org/2000/svg'%3E%3Cdefs%3E%3ClinearGradient id='grad<?= $index ?>' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%2327ae60;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%2316a085;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='1400' height='500' fill='url(%23grad<?= $index ?>)'/%3E%3C/svg%3E" alt="Banner">
                <?php endif; ?>
                <div class="banner-overlay">
                    <span class="category-badge" style="background: <?= $categoryColors[$featured['category']] ?? '#27ae60' ?>">
                        <?= strtoupper(htmlspecialchars($featured['category'])) ?>
                    </span>
                    <h1 class="banner-title"><?= htmlspecialchars($featured['title']) ?></h1>
                    <p class="banner-excerpt"><?= htmlspecialchars(getExcerpt($featured['content'], 200)) ?></p>
                    <div class="post-meta">
                        <div class="author">
                            <div class="author-avatar"><?= strtoupper(substr($featured['author'], 0, 1)) ?></div>
                            <span><?= htmlspecialchars($featured['author']) ?></span>
                        </div>
                        <span>•</span>
                        <span><?= formatIndonesianDate($featured['publish_date']) ?></span>
                        <span>•</span>
                        <span>👁️ <?= number_format($featured['views']) ?> views</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (count($featuredArticles) > 1): ?>
            <!-- Slider Controls -->
            <div class="slider-controls">
                <button class="slider-btn" onclick="event.stopPropagation(); changeSlide(-1)">‹</button>
                <button class="slider-btn" onclick="event.stopPropagation(); changeSlide(1)">›</button>
            </div>

            <!-- Slider Dots -->
            <div class="slider-dots">
                <?php foreach ($featuredArticles as $index => $featured): ?>
                <span class="dot <?= $index === 0 ? 'active' : '' ?>" onclick="event.stopPropagation(); currentSlide(<?= $index ?>)"></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Category Filter -->
    <div class="category-filter-section">
        <div class="category-filters">
            <a href="index.php" class="category-filter-btn <?= !isset($_GET['category']) ? 'active' : '' ?>">
                Semua
            </a>
            <?php foreach ($categories as $cat): ?>
            <a href="index.php?category=<?= urlencode($cat['category']) ?>" 
               class="category-filter-btn <?= (isset($_GET['category']) && $_GET['category'] === $cat['category']) ? 'active' : '' ?>">
                <?= htmlspecialchars($cat['category']) ?> (<?= $cat['count'] ?>)
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Main Container with Sidebar -->
    <div class="container">
        <div style="display: flex; flex-direction: column; gap: 30px;">
            <!-- Articles Section -->
            <section class="editors-pick" style="margin: 0;">
                <h2 class="section-title">
                    <?= isset($_GET['category']) ? htmlspecialchars($_GET['category']) : 'Artikel Terbaru' ?>
                </h2>
                <div class="posts-grid">
                    <?php 
                    // Filter by category if set
                    if (isset($_GET['category'])) {
                        $categoryFilter = $_GET['category'];
                        $stmt = $pdo->prepare("SELECT * FROM articles WHERE status = 'published' AND category = ? ORDER BY publish_date DESC LIMIT 9");
                        $stmt->execute([$categoryFilter]);
                        $articles = $stmt->fetchAll();
                    }
                    
                    foreach ($articles as $article): 
                    ?>
                    <article class="post-card" onclick="window.location.href='article-detail.php?id=<?= $article['id'] ?>'">
                        <div class="post-thumbnail" style="background: linear-gradient(135deg, <?= $categoryColors[$article['category']] ?? '#27ae60' ?> 0%, #16a085 100%);">
                            <?php if (!empty($article['featured_image'])): ?>
                                <img src="<?= htmlspecialchars($article['featured_image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>">
                            <?php endif; ?>
                        </div>
                        <div class="post-content">
                            <span class="post-category" style="background: <?= $categoryColors[$article['category']] ?? '#27ae60' ?>">
                                <?= strtoupper(htmlspecialchars($article['category'])) ?>
                            </span>
                            <h3 class="post-title"><?= htmlspecialchars($article['title']) ?></h3>
                            <p class="post-excerpt"><?= htmlspecialchars(getExcerpt($article['content'], 100)) ?></p>
                            <div class="post-footer">
                                <div class="author">
                                    <div class="author-avatar"><?= strtoupper(substr($article['author'], 0, 1)) ?></div>
                                    <span><?= htmlspecialchars($article['author']) ?></span>
                                </div>
                                <span><?= formatIndonesianDate($article['publish_date']) ?></span>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <!-- Sidebar -->
        <aside class="sidebar">
            <!-- Weather Widget -->
            <div class="sidebar-card weather-widget">
                <h3>Cuaca</h3>
                <div class="weather-temp">28°C</div>
                <div class="weather-location">Jakarta</div>
                <div style="margin: 20px 0; opacity: 0.6;">Berawan</div>
                <div class="weather-details">
                    <div>🌡️ 29° / 28°</div>
                    <div>💧 72%</div>
                    <div>🌬️ 3 km/h</div>
                    <div>☁️ Mendung</div>
                </div>
            </div>

            <!-- Popular Posts -->
            <div class="sidebar-card">
                <h3>Artikel Popular</h3>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php foreach ($popularArticles as $popular): ?>
                    <div style="display: flex; gap: 12px; padding-bottom: 15px; border-bottom: 1px solid #ecf0f1; cursor: pointer;"
                        onclick="window.location.href='article-detail.php?id=<?= $popular['id'] ?>'">
                        <div style="width: 80px; height: 60px; background: linear-gradient(135deg, <?= $categoryColors[$popular['category']] ?? '#27ae60' ?> 0%, #16a085 100%); border-radius: 8px; overflow: hidden;">
                            <?php if (!empty($popular['featured_image'])): ?>
                                <img src="<?= htmlspecialchars($popular['featured_image']) ?>" alt="<?= htmlspecialchars($popular['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: bold; font-size: 0.95rem; margin-bottom: 5px;">
                                <?= htmlspecialchars($popular['title']) ?>
                            </div>
                            <div style="font-size: 0.8rem; color: #95a5a6;">
                                <?= formatIndonesianDate($popular['publish_date']) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Social Follow -->
            <!-- <div class="sidebar-card">
                <h3>Follow Us</h3>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div class="social-card" style="box-shadow: none; padding: 15px;">
                        <div class="social-icon facebook">f</div>
                        <div class="social-info">
                            <div class="follower-count">248.1k</div>
                            <div class="social-label">Followers</div>
                        </div>
                    </div>
                    <div class="social-card" style="box-shadow: none; padding: 15px;">
                        <div class="social-icon twitter">𝕏</div>
                        <div class="social-info">
                            <div class="follower-count">69.1k</div>
                            <div class="social-label">Followers</div>
                        </div>
                    </div>
                    <div class="social-card" style="box-shadow: none; padding: 15px;">
                        <div class="social-icon instagram">📷</div>
                        <div class="social-info">
                            <div class="follower-count">134k</div>
                            <div class="social-label">Followers</div>
                        </div>
                    </div>
                    <div class="social-card" style="box-shadow: none; padding: 15px;">
                        <div class="social-icon youtube">▶</div>
                        <div class="social-info">
                            <div class="follower-count">54.3k</div>
                            <div class="social-label">Subscribers</div>
                        </div>
                    </div>
                </div>
            </div> -->
        </aside>
    </div>

    <!-- Footer -->
    <footer style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; padding: 40px 20px; margin-top: 60px; text-align: center;">
        <div style="max-width: 1400px; margin: 0 auto;">
            <div class="logo" style="justify-content: center; margin-bottom: 20px; font-size: 2rem;">
                <span class="logo-highlight">guepedia</span></span><span>.com</span>
            </div>
            <p style="opacity: 0.8; margin-bottom: 20px;">Platform berbagi informasi terpercaya untuk Anda</p>
            <div style="display: flex; justify-content: center; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                <a href="#" style="color: white; text-decoration: none;">Tentang Kami</a>
                <a href="#" style="color: white; text-decoration: none;">Kontak</a>
                <a href="#" style="color: white; text-decoration: none;">Privacy Policy</a>
                <a href="#" style="color: white; text-decoration: none;">Terms of Use</a>
            </div>
            <p style="opacity: 0.6; font-size: 0.9rem;">© 2025 guepedia.com. All rights reserved.</p>
        </div>
    </footer>

    <script>
        let currentSlideIndex = 0;
        const slides = document.querySelectorAll('.banner-slide');
        const dots = document.querySelectorAll('.dot');

        function showSlide(n) {
            if (slides.length === 0) return;
            
            if (n >= slides.length) currentSlideIndex = 0;
            if (n < 0) currentSlideIndex = slides.length - 1;

            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));

            slides[currentSlideIndex].classList.add('active');
            if (dots[currentSlideIndex]) {
                dots[currentSlideIndex].classList.add('active');
            }
        }

        function changeSlide(n) {
            currentSlideIndex += n;
            showSlide(currentSlideIndex);
        }

        function currentSlide(n) {
            currentSlideIndex = n;
            showSlide(currentSlideIndex);
        }

        // Auto slide every 5 seconds
        if (slides.length > 1) {
            setInterval(() => {
                currentSlideIndex++;
                showSlide(currentSlideIndex);
            }, 5000);
        }
    </script>
</body>
</html>