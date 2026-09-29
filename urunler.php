<?php
/**
 * Modern Ürünler Sayfası - NK Teknoloji
 * Premium e-ticaret tasarımı - Responsive & Sepet/Hesap & Sıralama Özelliği
 */

require_once 'config.php';

// Parametreleri al ve temizle
$kategori_slug = isset($_GET['kategori']) ? clean($_GET['kategori']) : '';
$arama = isset($_GET['q']) ? clean($_GET['q']) : '';
$sort = isset($_GET['sort']) ? clean($_GET['sort']) : 'default'; // Sıralama parametresi

// URL Oluşturucu (Mevcut filtreleri korumak için)
function getUrlWithSort($sortKey) {
    $params = $_GET;
    $params['sort'] = $sortKey;
    return '?' . http_build_query($params);
}

// Kategorileri çek
$kategoriler = $db->fetchAll("
    SELECT kategori_id, kategori_adi, kategori_slug, 
           (SELECT COUNT(*) FROM urunler WHERE kategori_id = k.kategori_id AND aktif = 1) as urun_sayisi
    FROM kategoriler k
    WHERE aktif = 1
    ORDER BY sira_no, kategori_adi
");

// Ürün sorgusu - Başlangıç
$sql = "SELECT u.*, k.kategori_adi, k.kategori_slug as kategori_slug
        FROM urunler u
        LEFT JOIN kategoriler k ON u.kategori_id = k.kategori_id
        WHERE u.aktif = 1";

$params = [];

// Kategori filtresi
if (!empty($kategori_slug)) {
    $sql .= " AND k.kategori_slug = ?";
    $params[] = $kategori_slug;
}

// Arama filtresi
if (!empty($arama)) {
    $sql .= " AND (u.urun_adi LIKE ? OR u.marka LIKE ? OR u.urun_aciklama LIKE ?)";
    $searchTerm = "%{$arama}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// SIRALAMA MANTIĞI (GÜNCELLENDİ)
switch ($sort) {
    case 'price_asc': // Fiyat Artan
        $order_sql = " ORDER BY u.doviz_satis_fiyati ASC";
        break;
    case 'price_desc': // Fiyat Azalan
        $order_sql = " ORDER BY u.doviz_satis_fiyati DESC";
        break;
    case 'newest': // En Yeniler
        $order_sql = " ORDER BY u.olusturma_tarihi DESC";
        break;
    case 'popular': // En Çok Satan (Görüntülenmeye göre)
        $order_sql = " ORDER BY u.goruntulenme_sayisi DESC";
        break;
    default: // Varsayılan (Vitrin -> Yeni -> Tarih)
        $order_sql = " ORDER BY u.vitrin_urun DESC, u.yeni_urun DESC, u.olusturma_tarihi DESC";
        break;
}

$sql .= $order_sql;

$urunler = $db->fetchAll($sql, $params);

// Aktif kategori bilgisi
$aktif_kategori = null;
if (!empty($kategori_slug)) {
    $aktif_kategori = $db->fetchOne("SELECT * FROM kategoriler WHERE kategori_slug = ?", [$kategori_slug]);
}

// Sayfa başlığı
$page_title = 'Tüm Ürünler';
if ($aktif_kategori) {
    $page_title = $aktif_kategori['kategori_adi'];
} elseif (!empty($arama)) {
    $page_title = "'{$arama}' için arama sonuçları";
}

// WhatsApp numarası
$whatsapp = getSetting('whatsapp_number', '905551234567');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
   <link rel="icon" type="image/png" sizes="32x32" href="https://www.nkteknoloji.com.tr/logo-favicon.png">
<link rel="icon" type="image/png" sizes="16x16" href="https://www.nkteknoloji.com.tr/logo-favicon.png">

<link rel="apple-touch-icon" sizes="180x180" href="https://www.nkteknoloji.com.tr/logo.png">
<meta property="og:image" content="https://www.nkteknoloji.com.tr/logo-kare-png.png">
<meta property="og:image:width" content="512">
<meta property="og:image:height" content="512">
<meta property="og:type" content="website">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo SITE_NAME; ?> - Kaliteli ses ve teknoloji ürünleri">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        
        :root {
            --primary: #141E30;
            --secondary: #35577D;
            --light-bg: #F8F9FB;
            --white: #FFFFFF;
            --text-dark: #1A1A1A;
            --text-light: #6B7280;
            --border: #E5E7EB;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --gradient: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-dark);
            background: var(--light-bg);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Header */
        .main-header {
            background: var(--white);
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 15px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
            flex-wrap: wrap;
        }

        .logo {
            font-size: 24px;
            font-weight: 900;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            margin-right: auto;
        }

        /* Header Actions */
        .header-actions {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .header-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: var(--text-dark);
            text-decoration: none;
            font-size: 11px;
            position: relative;
            transition: color 0.3s;
        }
        
        .header-action-btn:hover { color: var(--primary); }
        .header-action-btn .icon { font-size: 22px; margin-bottom: 2px; }
        
        .badge {
            position: absolute; top: -5px; right: -8px;
            background: var(--danger); color: var(--white);
            border-radius: 50%; width: 18px; height: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 700;
        }

        /* Search Bar */
        .search-container {
            flex: 1;
            max-width: 500px;
            margin: 0 20px;
        }

        .search-box { position: relative; width: 100%; }

        .search-box input {
            width: 100%;
            padding: 10px 45px 10px 15px;
            border: 2px solid var(--border);
            border-radius: 30px;
            font-size: 14px;
            background: var(--light-bg);
        }

        .search-box input:focus {
            outline: none; border-color: var(--primary); background: var(--white);
        }

        .search-btn {
            position: absolute; right: 5px; top: 50%; transform: translateY(-50%);
            background: var(--gradient); color: var(--white); border: none;
            padding: 6px 15px; border-radius: 25px; cursor: pointer;
            font-weight: 600; font-size: 12px;
        }

        .nav-links { display: flex; gap: 25px; }

        .nav-links a {
            color: var(--text-dark); text-decoration: none;
            font-weight: 500; transition: color 0.3s; font-size: 14px;
        }
        .nav-links a:hover { color: var(--primary); }

        /* Page Header */
        .page-header {
            background: var(--gradient); padding: 40px 20px;
            color: var(--white); text-align: center;
        }
        .page-header h1 { font-size: 32px; font-weight: 900; margin-bottom: 10px; }
        .page-header p { font-size: 16px; opacity: 0.9; }

        /* Breadcrumb */
        .breadcrumb {
            background: var(--white); border-bottom: 1px solid var(--border); padding: 15px 0;
            overflow-x: auto; white-space: nowrap; 
        }
        .breadcrumb-content {
            max-width: 1400px; margin: 0 auto; padding: 0 40px;
            display: flex; align-items: center; gap: 10px; font-size: 13px;
        }
        .breadcrumb a { color: var(--text-light); text-decoration: none; }
        .breadcrumb-separator { color: var(--text-light); }

        /* Container */
        .container {
            max-width: 1400px; margin: 0 auto; padding: 40px;
        }

        /* Layout */
        .shop-layout {
            display: grid; grid-template-columns: 280px 1fr; gap: 40px;
        }

        /* Sidebar */
        .sidebar { position: sticky; top: 100px; height: fit-content; }

        .sidebar-section {
            background: var(--white); border-radius: 16px; padding: 25px;
            margin-bottom: 20px; box-shadow: var(--shadow-sm);
        }
        .sidebar-section h3 {
            font-size: 18px; font-weight: 700; margin-bottom: 20px; color: var(--text-dark);
        }

        /* Mobile Filter Toggle Button */
        .mobile-filter-btn {
            display: none; 
            width: 100%; padding: 12px; margin-bottom: 15px;
            background: var(--white); border: 1px solid var(--primary);
            color: var(--primary); border-radius: 8px; font-weight: 600;
            cursor: pointer; align-items: center; justify-content: center; gap: 8px;
        }

        .filter-item {
            display: flex; align-items: center; gap: 10px; padding: 10px 0;
            cursor: pointer; transition: all 0.3s;
        }
        .filter-item input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
        .filter-item label { cursor: pointer; font-size: 14px; color: var(--text-dark); flex: 1; }
        .filter-count {
            font-size: 12px; color: var(--text-light); background: var(--light-bg);
            padding: 2px 8px; border-radius: 10px;
        }

        .category-link {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 15px; margin-bottom: 8px; text-decoration: none;
            color: var(--text-dark); border-radius: 10px; transition: all 0.3s;
            border: 1px solid transparent;
        }
        .category-link:hover { background: var(--light-bg); border-color: var(--border); }
        .category-link.active {
            background: var(--primary); color: var(--white); font-weight: 600;
        }
        .category-link.active .filter-count { background: rgba(255, 255, 255, 0.2); color: var(--white); }

        /* Main Content & Toolbar */
        .main-content { min-height: 500px; }

        .shop-toolbar {
            background: var(--white); border-radius: 16px; padding: 15px 20px;
            margin-bottom: 20px; display: flex; justify-content: space-between;
            align-items: center; box-shadow: var(--shadow-sm); flex-wrap: wrap; gap: 15px;
        }
        
        .result-count { font-size: 14px; color: var(--text-light); }
        
        .toolbar-right { display: flex; gap: 15px; align-items: center; }
        .sort-select {
            padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;
            font-size: 13px; background: var(--white); cursor: pointer;
            outline: none;
        }
        .sort-select:focus { border-color: var(--primary); }

        /* Products Grid */
        .products-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px;
        }

        .product-card {
            background: var(--white); border-radius: 12px; overflow: hidden;
            transition: all 0.3s; text-decoration: none; color: inherit;
            border: 1px solid var(--border); position: relative;
            display: flex; flex-direction: column;
        }

        .product-badges {
            position: absolute; top: 10px; left: 10px; display: flex;
            flex-direction: column; gap: 5px; z-index: 10;
        }
        .product-badge {
            padding: 4px 8px; border-radius: 4px; font-size: 10px;
            font-weight: 700; text-transform: uppercase; color: white;
        }
        .badge-new { background: var(--success); }
        .badge-sale { background: var(--danger); }
        .badge-featured { background: var(--warning); }

        .product-image-container {
            position: relative; width: 100%; padding-top: 100%; /* Kare oran */
            background: var(--light-bg); overflow: hidden;
        }
        .product-image {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            object-fit: contain; padding: 15px; transition: transform 0.5s;
        }
        .product-placeholder {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            font-size: 60px; background: var(--gradient); color: rgba(255,255,255,0.2);
        }

        .product-info { padding: 15px; display: flex; flex-direction: column; flex: 1; }
        
        .product-category {
            font-size: 10px; color: var(--text-light); text-transform: uppercase;
            font-weight: 600; margin-bottom: 4px;
        }
        .product-title {
            font-size: 14px; font-weight: 600; color: var(--text-dark);
            margin-bottom: 8px; line-height: 1.4;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden; height: 40px;
        }
        
        .product-rating { display: flex; align-items: center; gap: 5px; margin-bottom: 8px; }
        .stars { color: #FCD34D; font-size: 12px; letter-spacing: 1px; }
        .rating-count { font-size: 11px; color: var(--text-light); }

        .product-price-container { margin-top: auto; margin-bottom: 10px; }

        /* DÖVİZ FİYAT GÖSTERİMİ */
        .price-dual { display: flex; flex-direction: column; gap: 2px; }
        .price-usd { font-size: 16px; font-weight: 800; color: #10B981; line-height: 1.2; }
        .price-try { font-size: 12px; font-weight: 600; color: #666; line-height: 1.2; }

        .view-details-btn {
            display: block; width: 100%; padding: 10px;
            background: linear-gradient(135deg, #141E30 0%, #35577D 100%);
            color: white !important; text-align: center; border-radius: 8px;
            font-size: 13px; font-weight: 700; text-decoration: none;
            transition: all 0.3s;
        }

        .empty-state {
            background: var(--white); border-radius: 16px; padding: 60px 20px;
            text-align: center; box-shadow: var(--shadow-sm);
        }
        .btn-primary {
            background: var(--gradient); color: var(--white); padding: 12px 24px;
            border-radius: 25px; text-decoration: none; font-weight: 600;
            display: inline-block; font-size: 14px; margin-top: 15px;
        }

        /* Footer */
        .footer {
            background: var(--primary); color: var(--white);
            padding: 30px 20px; margin-top: 60px; text-align: center; font-size: 13px;
        }

        /* =================== RESPONSIVE CSS =================== */
        @media (max-width: 1024px) {
            .products-grid { grid-template-columns: repeat(3, 1fr); gap: 15px; }
            .nav-links { display: none; }
        }

        @media (max-width: 768px) {
            .header-content { padding: 15px 20px; gap: 15px; }
            
            .logo { font-size: 20px; }
            .header-actions { gap: 12px; }
            .header-action-btn .label { display: none; }
            .search-container { order: 3; flex-basis: 100%; max-width: 100%; margin: 0; margin-top: 5px; }
            
            .container { padding: 20px 15px; }
            .breadcrumb-content { padding: 0 15px; }

            /* Layout */
            .shop-layout { grid-template-columns: 1fr; gap: 20px; }
            
            /* Sidebar Toggle */
            .sidebar { display: none; }
            .sidebar.active { display: block; animation: slideDown 0.3s ease-out; }
            .mobile-filter-btn { display: flex; }

            /* Ürünler 2'li Grid */
            .products-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            
            /* Toolbar */
            .shop-toolbar { flex-direction: column; align-items: flex-start; gap: 10px; }
            .toolbar-right { width: 100%; justify-content: space-between; }
            .sort-select { width: 100%; }
            
            .product-info { padding: 10px; }
            .view-details-btn { padding: 8px; font-size: 12px; }
            .product-title { font-size: 13px; height: 36px; }
            
            .footer { margin-top: 40px; }
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="header-content">
            <a href="index.php" class="logo"><?php echo SITE_NAME; ?></a>

            <nav class="nav-links">
                <a href="index.php">Ana Sayfa</a>
                <a href="urunler.php" style="color: var(--primary);">Ürünler</a>
                <a href="sepet.php">Sepet</a>
            </nav>

            <div class="search-container">
                <form action="urunler.php" method="GET" class="search-box">
                    <?php if ($kategori_slug): ?>
                        <input type="hidden" name="kategori" value="<?php echo htmlspecialchars($kategori_slug); ?>">
                    <?php endif; ?>
                    <?php if ($sort !== 'default'): ?>
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                    <?php endif; ?>
                    <input type="text" name="q" placeholder="Ürün, marka veya kategori ara..." value="<?php echo htmlspecialchars($arama); ?>">
                    <button type="submit" class="search-btn">Ara</button>
                </form>
            </div>

            <div class="header-actions">
                <a href="<?php echo isLoggedIn() ? 'hesabim.php' : 'uye-giris.php'; ?>" class="header-action-btn">
                    <span class="icon">👤</span>
                    <span class="label"><?php echo isLoggedIn() ? 'Hesabım' : 'Giriş'; ?></span>
                </a>
                
                <a href="sepet.php" class="header-action-btn">
                    <span class="icon">🛒</span>
                    <span class="label">Sepet</span>
                    <?php if (isset($_SESSION['sepet']) && count($_SESSION['sepet']) > 0): ?>
                        <span class="badge"><?php echo array_sum($_SESSION['sepet']); ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </header>

    <div class="page-header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <p><?php echo count($urunler); ?> ürün bulundu</p>
    </div>

    <div class="breadcrumb">
        <div class="breadcrumb-content">
            <a href="index.php">Ana Sayfa</a>
            <span class="breadcrumb-separator">›</span>
            <a href="urunler.php">Ürünler</a>
            <?php if ($aktif_kategori): ?>
                <span class="breadcrumb-separator">›</span>
                <span><?php echo htmlspecialchars($aktif_kategori['kategori_adi']); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <div class="shop-layout">
            
            <button class="mobile-filter-btn" onclick="toggleFilters()">
                🔍 Filtrele / Kategoriler
            </button>

            <aside class="sidebar" id="productSidebar">
                <div class="sidebar-section">
                    <h3>Kategoriler</h3>
                    <a href="urunler.php" class="category-link <?php echo empty($kategori_slug) ? 'active' : ''; ?>">
                        <span>Tüm Ürünler</span>
                        <span class="filter-count"><?php echo count($db->fetchAll("SELECT urun_id FROM urunler WHERE aktif = 1")); ?></span>
                    </a>
                    <?php foreach ($kategoriler as $kat): ?>
                        <a href="<?php echo getUrlWithSort($sort); ?>&kategori=<?php echo urlencode($kat['kategori_slug']); ?>" 
                           class="category-link <?php echo $kategori_slug === $kat['kategori_slug'] ? 'active' : ''; ?>">
                            <span><?php echo htmlspecialchars($kat['kategori_adi']); ?></span>
                            <span class="filter-count"><?php echo $kat['urun_sayisi']; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="sidebar-section">
                    <h3>Fiyat Aralığı</h3>
                    <div class="filter-item">
                        <input type="checkbox" id="price1">
                        <label for="price1">0 - 500 ₺</label>
                    </div>
                    <div class="filter-item">
                        <input type="checkbox" id="price2">
                        <label for="price2">500 - 1000 ₺</label>
                    </div>
                    <div class="filter-item">
                        <input type="checkbox" id="price3">
                        <label for="price3">1000 - 2000 ₺</label>
                    </div>
                    <div class="filter-item">
                        <input type="checkbox" id="price4">
                        <label for="price4">2000 ₺ +</label>
                    </div>
                </div>
            </aside>

            <main class="main-content">
                <div class="shop-toolbar">
                    <div class="result-count">
                        <strong><?php echo count($urunler); ?> ürün</strong> listeleniyor
                    </div>
                    <div class="toolbar-right">
                        <select class="sort-select" onchange="window.location.href = this.value">
                            <option value="<?php echo getUrlWithSort('default'); ?>" <?php echo $sort == 'default' ? 'selected' : ''; ?>>Önerilen Sıralama</option>
                            <option value="<?php echo getUrlWithSort('price_asc'); ?>" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Fiyat: Düşükten Yükseğe</option>
                            <option value="<?php echo getUrlWithSort('price_desc'); ?>" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Fiyat: Yüksekten Düşüğe</option>
                            <option value="<?php echo getUrlWithSort('newest'); ?>" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>En Yeniler</option>
                            <option value="<?php echo getUrlWithSort('popular'); ?>" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>En Çok Satanlar</option>
                        </select>
                    </div>
                </div>

                <?php if (count($urunler) > 0): ?>
                    <div class="products-grid">
                        <?php foreach ($urunler as $urun): 
                            $gercek_fiyat = $urun['indirimli_fiyat'] ?? $urun['satis_fiyati'];
                            $indirim_var = !empty($urun['indirimli_fiyat']) && $urun['indirimli_fiyat'] < $urun['satis_fiyati'];
                            $indirim_oran = 0;
                            if ($indirim_var) {
                                $indirim_oran = round((($urun['satis_fiyati'] - $urun['indirimli_fiyat']) / $urun['satis_fiyati']) * 100);
                            }
                        ?>
                            <div class="product-card">
                                <div class="product-badges">
                                    <?php if ($urun['yeni_urun']): ?>
                                        <span class="product-badge badge-new">Yeni</span>
                                    <?php endif; ?>
                                    <?php if ($indirim_var): ?>
                                        <span class="product-badge badge-sale">İndirim</span>
                                    <?php endif; ?>
                                    <?php if ($urun['vitrin_urun']): ?>
                                        <span class="product-badge badge-featured">Vitrin</span>
                                    <?php endif; ?>
                                </div>

                                <a href="urun_detay.php?slug=<?php echo urlencode($urun['urun_slug']); ?>">
                                    <div class="product-image-container">
                                        <?php if (!empty($urun['ana_resim']) && file_exists(UPLOAD_PATH . 'products/' . $urun['ana_resim'])): ?>
                                            <img src="<?php echo UPLOAD_URL . 'products/' . $urun['ana_resim']; ?>" 
                                                 alt="<?php echo htmlspecialchars($urun['urun_adi']); ?>" 
                                                 class="product-image">
                                        <?php else: ?>
                                            <div class="product-placeholder">🎧</div>
                                        <?php endif; ?>
                                    </div>
                                </a>

                                <div class="product-info">
                                    <div class="product-category"><?php echo htmlspecialchars($urun['kategori_adi']); ?></div>
                                    <a href="urun_detay.php?slug=<?php echo urlencode($urun['urun_slug']); ?>" style="text-decoration: none; color: inherit;">
                                        <h3 class="product-title"><?php echo htmlspecialchars($urun['urun_adi']); ?></h3>
                                    </a>
                                    
                                    <div class="product-rating">
                                        <span class="stars">★★★★★</span>
                                        <span class="rating-count">(<?php echo rand(50, 200); ?>)</span>
                                    </div>

                                    <div class="product-price-container">
                                        <?php echo formatPriceDual($urun['doviz_satis_fiyati'], $urun['fiyat_doviz']); ?>
                                    </div>

                                    <a href="urun_detay.php?slug=<?php echo urlencode($urun['urun_slug']); ?>" class="view-details-btn">
                                        Detayları Gör
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">🔍</div>
                        <h3>Ürün Bulunamadı</h3>
                        <p>
                            <?php if (!empty($arama)): ?>
                                "<?php echo htmlspecialchars($arama); ?>" araması için sonuç bulunamadı.
                            <?php else: ?>
                                Bu kategoride henüz ürün bulunmuyor.
                            <?php endif; ?>
                        </p>
                        <a href="urunler.php" class="btn-primary">← Tüm Ürünlere Dön</a>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <footer class="footer">
        <p><strong><?php echo SITE_NAME; ?></strong> - Ses ve Teknoloji Ürünlerinde Güvenilir Adres</p>
        <p>© <?php echo date('Y'); ?> Tüm Hakları Saklıdır.</p>
    </footer>

    <script>
        // Mobil Filtre Toggle
        function toggleFilters() {
            const sidebar = document.getElementById('productSidebar');
            sidebar.classList.toggle('active');
            
            const btn = document.querySelector('.mobile-filter-btn');
            if (sidebar.classList.contains('active')) {
                btn.textContent = '❌ Filtreleri Gizle';
                btn.style.backgroundColor = '#f8f9fa';
                btn.style.color = '#333';
            } else {
                btn.textContent = '🔍 Filtrele / Kategoriler';
                btn.style.backgroundColor = 'var(--white)';
                btn.style.color = 'var(--primary)';
            }
        }

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    </script>
</body>
</html>