# المرحلة ٣ — وحدة النشر (Shopify + WordPress) (منفّذة)

أصبح بإمكان النظام نشر المقالات المولّدة فعلياً على متجر/موقع العميل.

## ما أُضيف
- **`backend/app/Services/PublishService.php`** (جديد):
  - `publish(Article)` → ينشر حسب منصّة النطاق.
  - **Shopify**: ينشئ مقال مدوّنة عبر Admin API (عنوان، body HTML، handle/slug، tags، حقول SEO عبر metafields، نشر فوري)، ويحلّ blog_id تلقائياً إن لم يُحدَّد.
  - **WordPress**: ينشر عبر REST `/wp-json/wp/v2/posts` (Application Password) ويُرجع رابط المقال.
  - `testConnection(Domain)` للتحقّق من الاعتماديات.
  - تحويل **Markdown → HTML** نظيف + إلحاق كتلة الأسئلة الشائعة (FAQ).
- **migration** `...000011_add_publishing_to_domains`: عمودان على `domains`:
  - `publish_platform` (shopify|wordpress)، `publish_config` (JSON **مشفّر** عبر cast `encrypted:array`).
- **`Domain`**: حقول النشر + `isPublishingConfigured()`.
- **`ArticleController::publish()`**: نشر فوري؛ عند النجاح يحدّث الحالة إلى `published` + `published_at` + `published_url`، وعند الفشل `failed` + سبب في `warnings`.
- **`PublishSettingsController`** (جديد): حفظ/عرض/اختبار إعدادات النشر (الاعتماديات تُحجب في الاستجابة، لا تُعاد للعميل أبداً).

## نقاط API جديدة (تحت `/api/domains/{domain}`)
- `GET  /publish-settings` · `PUT /publish-settings` · `POST /publish-settings/test`
- `POST /articles/{id}/publish` — نشر فوري.

## الأمان
- الاعتماديات تُخزَّن **مشفّرة** (Laravel `encrypted:array`, يتطلّب `APP_KEY`).
- لا تُرجَع الأسرار للعميل (محجوبة بـ ••••••).

## قبل التشغيل
- `php artisan migrate`.
- تأكّد من توفّر `league/commonmark` (مضمّن افتراضياً في Laravel 11؛ إن لزم: `composer require league/commonmark`).
- **Shopify**: أنشئ Custom App في المتجر، امنحه صلاحيات المدوّنة، وضع `shop_domain` + `access_token` عبر `PUT /publish-settings`.
- **WordPress**: أنشئ Application Password وضع `base_url` + `username` + `app_password`.

## المتبقي
- المرحلة ٤: Job + Scheduler (Cron) ينشر المقالات `scheduled` تلقائياً يومياً — يربط كل ما سبق في «أوتوبايلوت» كامل.
