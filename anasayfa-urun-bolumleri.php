<?php
/**
 * Ana Sayfa - Ürün Vitrini Bölümleri (NK Teknoloji)
 * ---------------------------------------------------
 * Bu dosya, NK Teknoloji ana sayfasında (index.php) yer alan iki ürün
 * gösterim bölümünü örnek/bağımsız bir sayfa olarak sunar:
 *
 *   1) "Yeni Gelenler" kaydırmalı slider'ı (Swiper.js ile)
 *   2) "Öne Çıkanlar" (vitrin ürünleri) grid görünümü
 *
 * Kendi ana sayfanıza eklemek isterseniz, aşağıdaki PHP sorgu bloğunu ve
 * <section> içeriklerini kopyalayıp kendi index.php dosyanıza
 * yapıştırmanız yeterlidir. Bu dosyayı olduğu gibi de çalıştırıp
 * tarayıcıda önizleyebilirsiniz.
 *
 * Gereksinimler: config.php içindeki $db (PDO wrapper) ve
 * formatPriceDual() fonksiyonu, ayrıca "urunler" ve "kategoriler"
 * tabloları (bkz. README.md içindeki tablo şeması).
 */

require_once __DIR__ . '/config.php';

// Vitrin ürünleri (öne çıkanlar) - "vitrin_urun = 1" olarak işaretlenmiş ürünler
$vitrin_urunler = $db->fetchAll("
    SELECT u.*, k.kategori_adi, k.kategori_slug
    FROM urunler u
    LEFT JOIN kategoriler k ON u.kategori_id = k.kategori_id
    WHERE u.aktif = 1 AND u.vitrin_urun = 1
    ORDER BY u.olusturma_tarihi DESC
    LIMIT 8
");

// Yeni ürünler (üst slider için) - "yeni_urun = 1" olarak işaretlenmiş ürünler
$yeni_urunler = $db->fetchAll("
    SELECT u.*, k.kategori_adi, k.kategori_slug
    FROM urunler u
    LEFT JOIN kategoriler k ON u.kategori_id = k.kategori_id
    WHERE u.aktif = 1 AND u.yeni_urun = 1
    ORDER BY u.olusturma_tarihi DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Vitrini Örneği - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #141E30;
            --secondary: #35577D;
            --white: #FFFFFF;
            --text-dark: #1A1A1A;
            --text-light: #6B7280;
            --border: #E5E7EB;
            --success: #10B981;
            --danger: #EF4444;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-dark);
            background: #F8F9FB;
        }

        .container { max-width: 1400px; margin: 0 auto; padding: 0 40px; }
        @media (max-width: 768px) { .container { padding: 0 15px; } }

        .section-header { text-align: center; margin: 40px 0 30px; }
        .section-title { font-size: 28px; font-weight: 800; color: var(--text-dark); }

        /* ============ YENİ ÜRÜNLER SLIDER (ÜST KISIM) ============ */
        .new-products-top {
            background: var(--white);
            padding: 30px 0;
            border-bottom: 1px solid var(--border);
        }
        .new-products-top .section-header { text-align: center; margin-bottom: 20px; }
        .new-products-top .section-title { font-size: 22px; font-weight: 800; color: var(--text-dark); margin-bottom: 5px; }
        .new-products-top .section-subtitle { font-size: 14px; color: var(--text-light); }

        .topNewProductsSwiper { width: 100%; padding: 20px 0 50px 0 !important; }

        .slider-product-card {
            background: var(--white); border-radius: 12px; border: 1px solid var(--border);
            overflow: hidden; position: relative; transition: 0.3s;
            display: flex; flex-direction: column; height: 100%;
        }
        .slider-product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .slider-product-image-container { position: relative; padding-top: 100%; background: #f5f5f5; overflow: hidden; }
        .slider-product-image { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; padding: 15px; }
        .slider-product-placeholder {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center; font-size: 48px; color: #ddd;
        }
        .slider-product-badges { position: absolute; top: 10px; left: 10px; z-index: 2; display: flex; flex-direction: column; gap: 5px; }
        .slider-product-badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: white; }
        .slider-badge-new { background: var(--success); }
        .slider-product-info { padding: 15px; display: flex; flex-direction: column; flex: 1; }
        .slider-product-category { font-size: 11px; text-transform: uppercase; color: var(--text-light); margin-bottom: 5px; }
        .slider-product-title {
            font-size: 14px; font-weight: 600; color: var(--text-dark); margin-bottom: 10px; line-height: 1.4;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 40px;
        }
        .slider-product-price-container { margin-top: auto; }
        .slider-view-btn {
            margin-top: 10px; display: block; width: 100%; padding: 10px;
            background: var(--primary); color: white; text-align: center; border-radius: 8px;
            font-size: 13px; text-decoration: none; font-weight: 600; transition: 0.3s;
        }
        .slider-view-btn:hover { background: var(--secondary); }

        .topNewProductsSwiper .swiper-button-next,
        .topNewProductsSwiper .swiper-button-prev {
            background-color: white; width: 40px !important; height: 40px !important;
            border-radius: 50%; box-shadow: 0 4px 10px rgba(0,0,0,0.15); color: var(--primary) !important;
        }
        .topNewProductsSwiper .swiper-button-next:after,
        .topNewProductsSwiper .swiper-button-prev:after { font-size: 16px !important; font-weight: bold; }
        .topNewProductsSwiper .swiper-pagination-bullet { background: var(--primary); }
        .topNewProductsSwiper .swiper-pagination-bullet-active { background: var(--secondary); }

        .swiper { width: 100%; padding-bottom: 40px !important; }
        .swiper-slide { height: auto !important; }

        /* ============ VİTRİN ÜRÜNLERİ (GRID) ============ */
        .products-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; }
        @media (max-width: 1024px) { .products-grid { grid-template-columns: repeat(3, 1fr); gap: 15px; } }
        @media (max-width: 768px) { .products-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } }

        .product-card {
            background: var(--white); border-radius: 12px; border: 1px solid var(--border);
            overflow: hidden; position: relative; transition: 0.3s; display: flex; flex-direction: column; height: 100%;
        }
        .product-image-container { position: relative; padding-top: 100%; background: #f0f0f0; overflow: hidden; }
        .product-image { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; padding: 10px; }
        .product-placeholder {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center; font-size: 40px; color: #ccc;
        }
        .product-info { padding: 15px; display: flex; flex-direction: column; flex: 1; }
        .product-category { font-size: 10px; text-transform: uppercase; color: var(--text-light); margin-bottom: 4px; }
        .product-title {
            font-size: 14px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px; line-height: 1.4;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 40px;
        }
        .product-price-container { margin-top: auto; }
        .price-usd { font-size: 16px; font-weight: 800; color: var(--success); }
        .price-try { font-size: 12px; color: #888; font-weight: 500; }
        .view-details-btn {
            margin-top: 10px; display: block; width: 100%; padding: 8px;
            background: var(--primary); color: white; text-align: center; border-radius: 6px;
            font-size: 12px; text-decoration: none; font-weight: 600;
        }
        .product-badges { position: absolute; top: 10px; left: 10px; z-index: 2; display: flex; flex-direction: column; gap: 5px; }
        .product-badge { padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; color: white; }
        .badge-sale { background: var(--danger); }
    </style>
</head>
<body>

    <!-- ============ 1) YENİ ÜRÜNLER SLIDER (ÜST KISIM) ============ -->
    <?php if (count($yeni_urunler) > 0): ?>
    <section class="new-products-top">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">🔥 Yeni Gelenler</h2>
                <p class="section-subtitle">En son eklenen ürünleri kaydırarak keşfedin</p>
            </div>

            <div class="swiper topNewProductsSwiper">
                <div class="swiper-wrapper">
                    <?php foreach ($yeni_urunler as $urun): ?>
                        <div class="swiper-slide">
                            <div class="slider-product-card">
                                <div class="slider-product-badges">
                                    <span class="slider-product-badge slider-badge-new">YENİ</span>
                                </div>
                                <a href="urun_detay.php?slug=<?php echo urlencode($urun['urun_slug']); ?>" class="slider-product-image-container">
                                    <?php if (!empty($urun['ana_resim']) && file_exists(UPLOAD_PATH . 'products/' . $urun['ana_resim'])): ?>
                                        <img src="<?php echo UPLOAD_URL . 'products/' . $urun['ana_resim']; ?>" class="slider-product-image" alt="<?php echo htmlspecialchars($urun['urun_adi']); ?>">
                                    <?php else: ?>
                                        <div class="slider-product-placeholder">📦</div>
                                    <?php endif; ?>
                                </a>
                                <div class="slider-product-info">
                                    <div class="slider-product-category"><?php echo htmlspecialchars($urun['kategori_adi']); ?></div>
                                    <h3 class="slider-product-title"><?php echo htmlspecialchars($urun['urun_adi']); ?></h3>
                                    <div class="slider-product-price-container">
                                        <?php echo formatPriceDual($urun['doviz_satis_fiyati'], $urun['fiyat_doviz']); ?>
                                    </div>
                                    <a href="urun_detay.php?slug=<?php echo urlencode($urun['urun_slug']); ?>" class="slider-view-btn">İncele</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============ 2) VİTRİN ÜRÜNLERİ (ÖNE ÇIKANLAR) ============ -->
    <?php if (count($vitrin_urunler) > 0): ?>
    <section style="padding: 40px 0;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">✨ Öne Çıkanlar</h2>
            </div>
            <div class="products-grid">
                <?php foreach ($vitrin_urunler as $urun): ?>
                    <div class="product-card">
                        <div class="product-badges">
                            <?php if ($urun['indirimli_fiyat']) echo '<span class="product-badge badge-sale">İndirim</span>'; ?>
                        </div>
                        <a href="urun_detay.php?slug=<?php echo urlencode($urun['urun_slug']); ?>" class="product-image-container">
                            <?php if (!empty($urun['ana_resim']) && file_exists(UPLOAD_PATH . 'products/' . $urun['ana_resim'])): ?>
                                <img src="<?php echo UPLOAD_URL . 'products/' . $urun['ana_resim']; ?>" class="product-image" alt="Ürün">
                            <?php else: ?>
                                <div class="product-placeholder">🎧</div>
                            <?php endif; ?>
                        </a>
                        <div class="product-info">
                            <div class="product-category"><?php echo htmlspecialchars($urun['kategori_adi']); ?></div>
                            <a href="urun_detay.php?slug=<?php echo urlencode($urun['urun_slug']); ?>" style="color: inherit; text-decoration: none;">
                                <h3 class="product-title"><?php echo htmlspecialchars($urun['urun_adi']); ?></h3>
                            </a>
                            <div class="product-price-container">
                                <?php echo formatPriceDual($urun['doviz_satis_fiyati'], $urun['fiyat_doviz']); ?>
                            </div>
                            <a href="urun_detay.php?slug=<?php echo urlencode($urun['urun_slug']); ?>" class="view-details-btn">İncele</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        var topNewSwiper = new Swiper(".topNewProductsSwiper", {
            slidesPerView: 1,
            spaceBetween: 15,
            loop: true,
            autoplay: {
                delay: 3000,
                disableOnInteraction: false,
            },
            navigation: {
                nextEl: ".topNewProductsSwiper .swiper-button-next",
                prevEl: ".topNewProductsSwiper .swiper-button-prev",
            },
            pagination: {
                el: ".topNewProductsSwiper .swiper-pagination",
                clickable: true,
            },
            breakpoints: {
                640: { slidesPerView: 2, spaceBetween: 15 },
                768: { slidesPerView: 3, spaceBetween: 20 },
                1024: { slidesPerView: 4, spaceBetween: 25 },
            },
        });
    </script>
</body>
</html>
