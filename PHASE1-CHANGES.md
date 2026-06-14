# المرحلة ١ — ترقية «دماغ» المحتوى (منفّذة)

تم تطبيق منطق مهارة `norsk-seo-autopilot` داخل الكود الفعلي. هذه قائمة التغييرات.

## ملفات عُدّلت
- `backend/app/Services/AiService.php` — أُعيدت كتابته بالكامل (الترقية الأساسية).
- `backend/app/Http/Controllers/Api/AiController.php` — أُضيفت دالتان: `clusterPlan`, `validateNorwegian`.
- `backend/routes/api.php` — مساران جديدان تحت `/api/ai`.
- `backend/config/services.php` — النموذج الافتراضي صار `gpt-4o-mini` (تحكّم بالتكلفة).
- `.env.example` — `OPENAI_MODEL=gpt-4o-mini` مع ملاحظة.

## ما الجديد في AiService
1. **برومبتات نرويجية أصيلة** مع كتلة KVALITETSKRAV: منع særskriving، ضمان æ/ø/å، صيغة «du»، أسعار بالكرون، مواسم نرويجية، منع الإحصاءات المفبركة.
2. **توليد مقال منظَّم (GEO)**: بنية «الجواب أولاً»، عناوين أسئلة، جدول/قائمة، FAQ، ومخرجات JSON تتضمّن `title_tag`, `meta_description`, `slug`, `secondary_keywords`, `internal_link_suggestions`, `image_alt_suggestions`, `schema`, `cluster` — أي بلوك SEO تقني جاهز للنشر.
3. **خطة Topic Clusters** عبر `generateClusterPlan()` — يولّد بيلارات + مقالات داعمة (كلمة/نية/H1/روابط داخلية) مرتّبة حسب الأولوية. (يخدم أيضاً طلب «خريطة المحتوى» السابق.)
4. **حارس الجودة النرويجية** `validateNorwegian()` — يكشف فيلر الافتتاح، غياب æ/ø/å، «De» الأركايك، وأنماط særskriving الشائعة؛ يُعيد تحذيرات.
5. **دعم نوع `pillar`** (مقال أطول وأشمل) إضافة إلى `blog_post`/`article`.
6. **slug آمن نرويجياً** (æ→ae, ø→o, å→a).
7. **تسجيل آمن** للاستهلاك (tokens) مع عدم كسر التوليد إن فشل السجل.

## نقاط API الجديدة
- `POST /api/ai/cluster-plan` — body: `business_description`, (اختياري) `language`, `pillars`, `articles_per_pillar`.
- `POST /api/ai/validate-norwegian` — body: `text`, (اختياري) `language` → `{passed, warnings[]}`.
- `POST /api/ai/generate-content` — كما هو، لكن `content_type=blog_post|article|pillar` يُرجع الآن مقالاً منظَّماً + بلوك SEO + تحذيرات الجودة.

## قبل التشغيل
- ضع `OPENAI_API_KEY` في `.env` (التوليد يستدعي OpenAI فعلياً).
- شغّل `composer install` ثم `php artisan migrate` (لا migrations جديدة في هذه المرحلة — قادمة في المرحلة ٢).
- اختبر: `POST /api/ai/generate-content` بـ `{ "keyword": "ballongbue til bursdag", "content_type": "article" }`.

## المتبقي (المراحل التالية)
- المرحلة ٢: جداول `articles`/`clusters`/`content_plans` + حفظ الخطة.
- المرحلة ٣: `PublishService` (Shopify + WordPress).
- المرحلة ٤: Jobs مجدولة للنشر اليومي.
