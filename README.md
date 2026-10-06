# Novax — Hostinger paketi

[ZIP paketini indir](https://github.com/ardasrky580-ai/novaxcati/raw/refs/heads/main/novax-hostinger.zip)

PHP destekli web hosting gerekir; yalnızca domain yeterli değildir.

1. Paketi bilgisayarında aç. `SITE-YUKLE.zip` dosyasını domaininin `public_html` klasörüne yükleyip çıkar.
2. SSL'yi etkinleştir. PHP 8.2+; GD ve mbstring açık olsun.
3. Hostinger Dosya Yöneticisi'nde `config.php` dosyasını aç. `setup_key` alanındaki boş tırnaklara kendine özel en az 24 karakterlik harf/rakamdan oluşan bir anahtar yaz ve kaydet. Değiştirdiğin dosyayı GitHub'a yükleme.
4. `https://domainin.com/setup.php` adresine gir. Aynı anahtarla yönetici hesabını oluştur.
5. `https://domainin.com/admin.php` üzerinden metinlerini, fotoğraflarını ve işletme bilgilerini düzenle.

Ayrıntılar [KURULUM.txt](KURULUM.txt) dosyasında. Kaynak dosyaları `site/` klasöründe. ZIP kişisel kurulum anahtarı, yönetici şifresi veya test veritabanı içermez.
