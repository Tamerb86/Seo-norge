# المرحلة ٢ — نموذج بيانات التقويم والكلستر (منفّذة)

أصبح بإمكان النظام حفظ خطط المحتوى، الكلسترات، والمقالات (مسودّة/مجدولة/منشورة) — تمهيداً للنشر الآلي في المرحلة ٣–٤.

## جداول جديدة (migrations)
- `content_plans` — خطة محتوى لكل نطاق (وصف العمل، اللغة، الحالة).
- `clusters` — بيلار داخل الخطة (عنوان + كلمة محورية + ترتيب).
- `articles` — المقال: كلمة مستهدفة، نية، أولوية، H1، title_tag، meta، slug، secondary_keywords، body_markdown، faq، internal_links، image_alts، schema_types، warnings، word_count، **الحالة** (draft/scheduled/published/failed)، scheduled_for، published_at، published_url.

## Models جديدة
- `ContentPlan` (hasMany clusters)، `Cluster` (hasMany articles, belongsTo plan/domain)، `Article` (belongsTo domain/cluster، ثوابت حالة، casts JSON).
- `Domain` أُضيف له: `contentPlans()`, `clusters()`, `articles()`.

## Controllers + Routes جديدة (تحت `/api/domains/{domain}`)
**خطط المحتوى:**
- `GET  /content-plans` — قائمة الخطط مع الكلسترات وعدّ المقالات.
- `POST /content-plans` — يولّد خطة Topic Clusters عبر الـ AI **ويحفظها** كمقالات مسودّة دفعة واحدة (transaction).
- `GET  /content-plans/{id}` · `DELETE /content-plans/{id}`.

**المقالات:**
- `GET  /articles` (فلترة بالحالة) · `GET /articles/{id}`.
- `POST /articles/{id}/generate` — يولّد جسم المقال + بلوك SEO + تحذيرات الجودة ويحفظه.
- `PUT  /articles/{id}` — تحرير (slug يُنظّف تلقائياً، الجودة تُعاد فحصها).
- `POST /articles/{id}/schedule` — جدولة للنشر (يتطلّب توليد المقال أولاً).
- `DELETE /articles/{id}`.

## كل النقاط محميّة بفحص الملكية (النطاق يخصّ المستخدم) — نفس نمط الكود القائم.

## قبل التشغيل
- `php artisan migrate` (٣ جداول جديدة).
- تدفّق الاختبار: أنشئ نطاقاً → `POST /content-plans` بوصف العمل → سترى مقالات مسودّة → `POST /articles/{id}/generate` → `POST /articles/{id}/schedule`.

## المتبقي
- المرحلة ٣: `PublishService` (Shopify Admin API + WordPress REST) لنشر المقالات المجدولة.
- المرحلة ٤: Jobs مجدولة (Scheduler/Cron) تنشر `scheduled` تلقائياً وتولّد المحتوى اليومي.
