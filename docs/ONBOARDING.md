# نظام Onboarding - SEO Norge

## 📋 نظرة عامة

نظام Onboarding مصمم لتوجيه المستخدمين الجدد خلال إعداد حساباتهم وتعريفهم بميزات المنصة. الهدف هو تحويل المستخدمين الجدد إلى مستخدمين نشطين بأسرع وقت ممكن.

---

## 🎯 أهداف النظام

1. **تقليل معدل الانسحاب**: توجيه المستخدم خطوة بخطوة
2. **جمع البيانات**: معرفة احتياجات المستخدم لتخصيص التجربة
3. **التفعيل السريع**: إضافة أول موقع وكلمات مفتاحية
4. **التعليم**: تعريف المستخدم بالميزات الرئيسية

---

## 🔄 مراحل Onboarding

### المرحلة 1: الترحيب والملف الشخصي
```
- الاسم الكامل
- اسم الشركة (اختياري)
- الدور الوظيفي
```

### المرحلة 2: إضافة أول موقع
```
- رابط الموقع (domain)
- اسم الموقع (اختياري)
```

### المرحلة 3: إضافة الكلمات المفتاحية
```
- قائمة كلمات مفتاحية (واحدة لكل سطر)
- نصائح لاختيار الكلمات
```

### المرحلة 4: إضافة المنافسين
```
- قائمة مواقع المنافسين
- شرح فوائد تحليل المنافسين
```

### المرحلة 5: تحديد الأهداف
```
- أهداف متعددة الاختيار:
  - زيادة الزيارات
  - تحسين الترتيب
  - توليد العملاء المحتملين
  - زيادة المبيعات
  - الظهور المحلي
  - بناء العلامة التجارية
  - إنشاء محتوى أفضل
  - التفوق على المنافسين
- مستوى الخبرة في SEO
```

---

## 📁 ملفات النظام

### Frontend (Next.js)

```
frontend/
├── app/(dashboard)/onboarding/
│   └── page.tsx                    # صفحة Onboarding الرئيسية
├── components/onboarding/
│   ├── WelcomeModal.tsx            # نافذة الترحيب
│   ├── OnboardingChecklist.tsx     # قائمة المهام
│   └── EmptyState.tsx              # حالة فارغة مع توجيهات
```

### Backend (Laravel)

```
backend/
├── app/Http/Controllers/Api/
│   └── OnboardingController.php    # Controller للـ Onboarding
├── database/migrations/
│   └── 2024_01_01_000007_add_onboarding_fields_to_users.php
```

---

## 🔌 API Endpoints

### GET /api/onboarding/progress
```json
// Response
{
  "profile": true,
  "domain": true,
  "keywords": false,
  "competitor": false,
  "ai": false
}
```

### POST /api/onboarding/complete
```json
// Request
{
  "full_name": "Ola Nordmann",
  "company": "Min Bedrift AS",
  "role": "owner",
  "domain": "minbedrift.no",
  "domain_name": "Min Bedrift",
  "keywords": ["beste restaurant oslo", "romantisk middag"],
  "competitors": ["konkurrent1.no", "konkurrent2.no"],
  "goals": ["traffic", "rankings", "local"],
  "experience": "intermediate"
}

// Response
{
  "success": true,
  "message": "Onboarding fullført!",
  "domain": { ... }
}
```

### GET /api/onboarding/tips
```json
// Response
{
  "tips": [
    {
      "id": "add_keywords",
      "priority": 1,
      "icon": "🔍",
      "title": "Legg til søkeord",
      "description": "Legg til søkeord for å begynne å spore rangeringene dine.",
      "action": "/dashboard/domains",
      "action_label": "Legg til søkeord"
    }
  ],
  "show_checklist": true
}
```

### POST /api/user/goals
```json
// Request
{
  "goals": ["traffic", "rankings"],
  "experience": "beginner"
}
```

### POST /api/user/dismiss-checklist
```json
// Response
{
  "success": true
}
```

---

## 🗃️ حقول قاعدة البيانات (جدول users)

| الحقل | النوع | الوصف |
|:---|:---|:---|
| `full_name` | string | الاسم الكامل |
| `company` | string | اسم الشركة |
| `role` | string | الدور الوظيفي |
| `onboarding_completed` | boolean | هل اكتمل الـ Onboarding |
| `onboarding_completed_at` | timestamp | تاريخ الإكمال |
| `goals` | json | أهداف المستخدم |
| `seo_experience` | string | مستوى الخبرة |
| `checklist_dismissed` | boolean | هل تم إخفاء القائمة |
| `checklist_dismissed_at` | timestamp | تاريخ الإخفاء |
| `ai_analyses_count` | integer | عدد تحليلات AI |

---

## 🎨 المكونات

### 1. صفحة Onboarding (`/onboarding`)

**الميزات:**
- شريط تقدم مرئي (5 خطوات)
- تنقل بين الخطوات (التالي/السابق)
- إمكانية تخطي أي خطوة
- حفظ تلقائي عند الإكمال
- توجيه إلى Dashboard بعد الانتهاء

### 2. نافذة الترحيب (`WelcomeModal`)

**متى تظهر:**
- عند أول زيارة للـ Dashboard بعد إكمال Onboarding
- تُفعّل بـ `?welcome=true` في URL

**الميزات:**
- عرض نصائح متحركة (carousel)
- زر للبدء السريع
- إمكانية الإغلاق

### 3. قائمة المهام (`OnboardingChecklist`)

**المهام:**
1. ✅ إكمال الملف الشخصي
2. ✅ إضافة موقع
3. ✅ إضافة 5 كلمات مفتاحية
4. ✅ إضافة منافس
5. ✅ تجربة أدوات AI

**الميزات:**
- شريط تقدم دائري
- قابلة للطي/التوسيع
- إمكانية الإخفاء بعد الإكمال
- روابط مباشرة لكل مهمة

### 4. حالة فارغة (`EmptyState`)

**الأنواع:**
- `domains`: لا توجد مواقع
- `keywords`: لا توجد كلمات مفتاحية
- `competitors`: لا يوجد منافسون
- `rankings`: لا توجد بيانات ترتيب
- `reports`: لا توجد تقارير

**الميزات:**
- أيقونة ورسالة مخصصة
- زر إجراء
- نصائح للبدء

---

## 🔧 كيفية الاستخدام

### 1. إضافة Checklist إلى Dashboard

```tsx
import OnboardingChecklist from '@/components/onboarding/OnboardingChecklist';

export default function DashboardPage() {
  return (
    <div>
      <OnboardingChecklist />
      {/* باقي محتوى Dashboard */}
    </div>
  );
}
```

### 2. إضافة Welcome Modal

```tsx
import WelcomeModal from '@/components/onboarding/WelcomeModal';

export default function DashboardLayout({ children }) {
  return (
    <div>
      <WelcomeModal userName="Ola" />
      {children}
    </div>
  );
}
```

### 3. استخدام Empty State

```tsx
import EmptyState from '@/components/onboarding/EmptyState';

export default function DomainsPage() {
  const { domains } = useDomains();
  
  if (domains.length === 0) {
    return <EmptyState type="domains" onAction={() => setShowModal(true)} />;
  }
  
  return <DomainsList domains={domains} />;
}
```

---

## 📊 تحليلات Onboarding

### المقاييس المهمة

| المقياس | الوصف | الهدف |
|:---|:---|:---|
| **معدل الإكمال** | % المستخدمين الذين أكملوا Onboarding | > 70% |
| **وقت الإكمال** | متوسط الوقت لإكمال Onboarding | < 5 دقائق |
| **معدل التخطي** | % الخطوات المتخطاة | < 30% |
| **معدل التفعيل** | % المستخدمين الذين أضافوا موقعاً | > 80% |

### أحداث للتتبع

```javascript
// مثال على تتبع الأحداث
analytics.track('onboarding_started');
analytics.track('onboarding_step_completed', { step: 2 });
analytics.track('onboarding_step_skipped', { step: 3 });
analytics.track('onboarding_completed', { duration: 180 });
```

---

## 🌍 الترجمة

جميع النصوص بالنرويجية (Bokmål). لإضافة لغات أخرى:

```typescript
// lib/i18n/onboarding.ts
export const onboardingTranslations = {
  no: {
    welcome: 'Velkommen til SEO Norge!',
    step1_title: 'La oss bli kjent med deg',
    // ...
  },
  en: {
    welcome: 'Welcome to SEO Norge!',
    step1_title: "Let's get to know you",
    // ...
  }
};
```

---

## ✅ قائمة التحقق للنشر

- [ ] تشغيل migrations
- [ ] اختبار كل خطوة من Onboarding
- [ ] اختبار API endpoints
- [ ] اختبار على الموبايل
- [ ] إعداد تتبع الأحداث
- [ ] مراجعة النصوص النرويجية

---

**تم إنشاء هذا التوثيق لمشروع SEO Norge** 🇳🇴
