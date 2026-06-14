# Quickstart — تشغيل المشروع (المرحلة ٠)

## ١. GitHub
اتبع `PUSH-TO-GITHUB.md` لرفع الكود.

## ٢. قاعدة البيانات (Supabase — مجاني)
- أنشئ مشروعاً على https://supabase.com → خذ: Project URL، anon key، service_role key، JWT secret، وبيانات اتصال Postgres.

## ٣. الـ backend (Laravel) على Hostinger «Custom PHP/HTML»
```bash
cd backend
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate          # مهم: يفعّل تشفير بيانات النشر
# عدّل .env: DB_* (Supabase Postgres)، SUPABASE_*، OPENAI_API_KEY، QUEUE_CONNECTION=sync
php artisan migrate
```
- document root = `backend/public`
- أضف مهمة Cron واحدة:
```
* * * * * cd /home/USERNAME/APP_PATH && php artisan schedule:run >> /dev/null 2>&1
```

## ٤. الواجهة (Next.js)
- على Vercel (أسهل) أو Hostinger «Node.js Web App».
- اضبط: `NEXT_PUBLIC_API_URL=https://api.dittdomene.no/api`، `NEXT_PUBLIC_SUPABASE_URL`، `NEXT_PUBLIC_SUPABASE_ANON_KEY`.

## ٥. اختبار سريع
- سجّل دخول → أضف نطاقاً → `POST /api/domains/{id}/content-plans` بوصف عملك → ستُنشأ مسودّات → `POST /articles/{id}/generate` → راجع → فعّل الأوتوبايلوت من `PUT /autopilot`.

## ٦. لاحقاً (المرحلة ٥)
- باقات بالكرون + Vipps + احتساب MVA ٢٥٪ (خبرتك كمحاسب).
