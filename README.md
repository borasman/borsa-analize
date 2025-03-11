# Borsa Analize

Borsa analiz ve portföy yönetim uygulaması. Bu uygulama ile hisse senetlerinizi takip edebilir, portföy oluşturabilir ve analizler yapabilirsiniz.

## Özellikler

- Kullanıcı kaydı ve girişi
- Portföy yönetimi
- Hisse senedi takibi
- Alım-satım işlemleri
- Portföy analizi
- Bildirim sistemi

## Teknolojiler

- PHP 8.2
- Symfony 6.1
- MySQL/SQLite
- Bootstrap 5
- JavaScript/jQuery
- RabbitMQ
- Redis
- JWT Authentication
- Google reCAPTCHA

## Kurulum

1. Projeyi klonlayın:
```bash
git clone https://github.com/borasman/borsa-analize.git
cd borsa-analize
```

2. Composer bağımlılıklarını yükleyin:
```bash
composer install
```

3. Node.js bağımlılıklarını yükleyin:
```bash
npm install
```

4. Veritabanını oluşturun:
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. Varlıkları derleyin:
```bash
npm run build
```

6. Sunucuyu başlatın:
```bash
symfony server:start
# veya
php -S localhost:8000 -t public/
```

## Konfigürasyon

1. `.env` dosyasını `.env.local` olarak kopyalayın ve gerekli ayarları yapın:
- Veritabanı bağlantısı
- RabbitMQ bağlantısı
- Redis bağlantısı
- JWT anahtarları
- reCAPTCHA anahtarları

## Lisans

Bu proje MIT lisansı altında lisanslanmıştır. Detaylar için [LICENSE](LICENSE) dosyasına bakın.