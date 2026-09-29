# NK Teknoloji — Ürün Vitrini Örneği

Bu depo, [NK Teknoloji](https://www.nkteknoloji.com.tr) e-ticaret sitesinin ürün gösterim bileşenlerinin sadeleştirilmiş, örnek/gösterim amaçlı bir sürümüdür. PHP + MySQL ile çalışan, kur bazlı (USD/TL) fiyatlandırma destekli basit bir ürün vitrini ve ürün listeleme sayfası oluşturmak isteyenler için başlangıç noktası olarak paylaşılmaktadır.

Gerçek NK Teknoloji sitesindeki özel bilgiler (veritabanı şifreleri, WhatsApp numarası vb.) bu depodan çıkarılmış, yerine `.env` tabanlı bir yapılandırma sistemi eklenmiştir.

## İçerik

- **`anasayfa-urun-bolumleri.php`** — Ana sayfada kullanılan iki ürün gösterim bölümünün bağımsız/çalışır örneği:
  - "Yeni Gelenler" kaydırmalı slider'ı ([Swiper.js](https://swiperjs.com/) ile)
  - "Öne Çıkanlar" (vitrin ürünleri) grid görünümü
- **`urunler.php`** — Kategori filtreleme, arama ve sıralama (fiyata göre, en yeni, en popüler) özellikli tam ürün listeleme/katalog sayfası.
- **`config.php`** — Veritabanı bağlantısı, ayarlar ve yardımcı fonksiyonlar (`.env` üzerinden yapılandırılır).

## Öne Çıkan Özellikler

- Çift para birimi gösterimi (USD fiyat + güncel kur ile TL karşılığı) — `formatPriceDual()`
- Kategoriye göre filtreleme ve ürün adı/marka/açıklamada arama
- Fiyat artan/azalan, en yeni, en çok görüntülenen sıralama seçenekleri
- Vitrin ürünü, yeni ürün ve indirim rozetleri
- Swiper.js tabanlı, mobil uyumlu responsive slider
- Ürün görseli yoksa otomatik placeholder (emoji ikon) gösterimi

## Klasör Yapısı

```
nk-teknoloji-urun-vitrini/
├── config.php                     # Veritabanı bağlantısı ve yardımcı fonksiyonlar
├── anasayfa-urun-bolumleri.php    # Ana sayfa ürün vitrini bölümleri (örnek/çalışır sayfa)
├── urunler.php                    # Tam ürün listeleme/katalog sayfası
├── .env.example                   # Örnek ortam değişkenleri dosyası
├── .gitignore
├── README.md
└── LICENSE
```

## Gereksinimler

- PHP 7.4+ (PDO MySQL eklentisi ile)
- MySQL / MariaDB
- Composer'a ihtiyaç yoktur (`.env` dosyası basit bir PHP fonksiyonu ile okunur)

## Kurulum

1. `.env.example` dosyasını `.env` olarak kopyalayın ve kendi bilgilerinizle doldurun:

   ```
   cp .env.example .env
   ```

2. `.env` içindeki veritabanı bilgilerini kendi sunucunuza göre güncelleyin (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).

3. Veritabanınızda aşağıdaki tabloları oluşturun (mevcut projeden uyarlanmış minimum şema):

   ```sql
   CREATE TABLE kategoriler (
       kategori_id INT AUTO_INCREMENT PRIMARY KEY,
       kategori_adi VARCHAR(100) NOT NULL,
       kategori_slug VARCHAR(120) NOT NULL UNIQUE,
       sira_no INT DEFAULT 0,
       aktif TINYINT(1) DEFAULT 1
   );

   CREATE TABLE urunler (
       urun_id INT AUTO_INCREMENT PRIMARY KEY,
       urun_adi VARCHAR(200) NOT NULL,
       urun_slug VARCHAR(220) NOT NULL UNIQUE,
       urun_aciklama TEXT,
       marka VARCHAR(100),
       kategori_id INT,
       satis_fiyati DECIMAL(10,2) DEFAULT 0,
       doviz_satis_fiyati DECIMAL(10,2) DEFAULT 0,
       fiyat_doviz VARCHAR(5) DEFAULT 'USD',
       indirimli_fiyat DECIMAL(10,2) NULL,
       ana_resim VARCHAR(255),
       aktif TINYINT(1) DEFAULT 1,
       yeni_urun TINYINT(1) DEFAULT 0,
       vitrin_urun TINYINT(1) DEFAULT 0,
       goruntulenme_sayisi INT DEFAULT 0,
       olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
       FOREIGN KEY (kategori_id) REFERENCES kategoriler(kategori_id)
   );
   ```

4. `uploads/products/` klasörünü oluşturun ve ürün görsellerini buraya yükleyin (dosya adlarını `urunler.ana_resim` sütununda saklayın). Görsel yoksa sayfalar otomatik olarak bir emoji placeholder gösterir.

5. `anasayfa-urun-bolumleri.php` dosyasını tarayıcıda açarak ürün vitrini bölümlerini, `urunler.php` dosyasını açarak tam katalog sayfasını önizleyebilirsiniz.

## Kendi Ana Sayfanıza Ekleme

`anasayfa-urun-bolumleri.php` bağımsız çalışacak şekilde hazırlanmıştır, ancak asıl amacı kendi `index.php` dosyanıza entegre edilmesidir:

1. Dosyanın en üstündeki PHP sorgu bloğunu (`$vitrin_urunler` ve `$yeni_urunler` sorguları) kendi `index.php`'nizin veri çekme bölümüne ekleyin.
2. `<style>` içindeki ilgili CSS sınıflarını kendi sayfanızın stil dosyasına/bloğuna taşıyın (isim çakışması olmaması için sınıf adlarını kontrol edin).
3. İki `<section>` bloğunu (Yeni Ürünler Slider ve Vitrin Ürünleri) kendi sayfanızdaki uygun konuma yapıştırın.
4. Sayfanızda Swiper.js zaten yükleniyorsa, tekrar CDN eklemenize gerek yoktur.

## Güvenlik Notları

- Gerçek veritabanı bilgileri ve WhatsApp numarası bu depoya dahil edilmemiştir; `.env` dosyanızı **asla** git'e eklemeyin (`.gitignore` içinde zaten hariç tutulmuştur).
- Tüm kullanıcı girdileri (`kategori`, `q`, `sort` GET parametreleri) `clean()` fonksiyonu ile temizlenir ve sorgular parametreli (prepared statement) olarak çalıştırılır; SQL injection riskine karşı önlem alınmıştır.
- Üretim ortamında `.env` dosyasında `APP_DEBUG=false` olarak ayarlı olduğundan emin olun; aksi halde hata mesajları ziyaretçilere görünebilir.

## Lisans

MIT — ayrıntılar için `LICENSE` dosyasına bakın.
