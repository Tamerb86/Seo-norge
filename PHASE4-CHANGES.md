# المرحلة ٤ — الأوتوبايلوت (Jobs + Scheduler/Cron) (منفّذة)

اكتمل «الطيّار الآلي»: النظام يولّد المحتوى اليومي وينشره تلقائياً عبر **مهمة Cron واحدة** — دون VPS ولا worker دائم.

## ما أُضيف
**Jobs:**
- `GenerateArticleJob` — يولّد جسم مقال واحد + بلوك SEO + تحذيرات الجودة، ويحفظه؛ وإن كان النطاق على «نشر تلقائي» يسلّمه مباشرة لـ`PublishArticleJob`.
- `PublishArticleJob` — ينشر مقالاً واحداً عبر `PublishService` ويحدّث الحالة/الرابط (أو `failed` مع السبب).

**Commands (artisan):**
- `seo:generate-daily` — لكل نطاق «autopilot مفعّل»: يأخذ أعلى المسودّات أولوية (ضمن الحدّ اليومي) ويبدأ توليدها.
- `seo:publish-scheduled` — ينشر المقالات `scheduled` التي حان موعدها.

**الجدولة (`routes/console.php` — أسلوب Laravel 11):**
- `seo:generate-daily` يومياً 05:00 بتوقيت أوسلو.
- `seo:publish-scheduled` كل ١٥ دقيقة.
- `queue:work --stop-when-empty` كل دقيقة (يُفرّغ الطابور إن كان `QUEUE_CONNECTION=database`).
- فحص الترتيب اليومي 03:00 (ميزة قائمة، مُحافَظ عليها).

**migration** `...000012_add_autopilot_to_domains`: `autopilot_enabled`, `autopilot_auto_publish`, `autopilot_daily_limit`, `autopilot_tone`, `autopilot_last_run_at`.

**API جديدة (تحت `/api/domains/{domain}`):**
- `GET /autopilot` · `PUT /autopilot` — تشغيل/إيقاف المحرّك، الحدّ اليومي، النشر التلقائي، النبرة.
- حارس: لا يمكن تفعيل «النشر التلقائي» قبل ضبط وجهة نشر.

## الإعداد على Hostinger (المفتاح كله)
أضف **مهمة Cron واحدة فقط** في لوحة Hostinger:

```
* * * * * cd /home/USERNAME/domains/APP_PATH && php artisan schedule:run >> /dev/null 2>&1
```

هذه السطر الواحد يشغّل كل الجدولة أعلاه. لا حاجة لخادم دائم.

## أبسط إعداد تشغيلي (موصى به للبداية)
- `QUEUE_CONNECTION=sync` → الـ Jobs تُنفَّذ فوراً داخل الـ Command (يعمل ممتاز على الاستضافة المشتركة).
- عند النمو: حوّل إلى `QUEUE_CONNECTION=database` ودع سطر `queue:work` المجدول يفرّغ الطابور.

## تدفّق «الأوتوبايلوت» الكامل الآن
وصف العمل → خطة كلستر (محفوظة) → `seo:generate-daily` يكتب المسودّات يومياً → (مراجعة اختيارية) → جدولة → `seo:publish-scheduled` ينشر على Shopify/WordPress. مع «النشر التلقائي» يصبح كل ذلك بلا تدخّل.

## قبل التشغيل
- `php artisan migrate`
- اضبط الـ Cron أعلاه، فعّل autopilot عبر `PUT /autopilot`.
- (الـ Console وCommands تُسجَّل تلقائياً في Laravel 11.)
