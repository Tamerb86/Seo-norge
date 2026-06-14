# رفع المشروع إلى GitHub — خطوة بخطوة

المستودع مُهيّأ مسبقاً (git + .gitignore + أول commit، بدون أسرار). تحتاج فقط إنشاء مستودع على GitHub وربطه ودفع الكود.

## الطريقة ١ — عبر سطر الأوامر (موصى بها)

### أ) أنشئ مستودعاً فارغاً على GitHub
اذهب إلى https://github.com/new → اسم مثل `seo-norge` → **Private** → بدون README/‏gitignore (موجودان عندنا) → Create.

### ب) من مجلد المشروع على جهازك نفّذ:
```bash
# إن لم يكن git مُهيّأ بعد (مهيأ مسبقاً في النسخة المرفقة):
git init
git add -A
git commit -m "SEO Norge autopilot SaaS — phases 1-4"

# اربط بمستودعك وادفع (استبدل USERNAME):
git branch -M main
git remote add origin https://github.com/USERNAME/seo-norge.git
git push -u origin main
```
سيطلب اسم المستخدم و**Personal Access Token** ككلمة مرور (أنشئه من GitHub → Settings → Developer settings → Tokens، صلاحية `repo`).

## الطريقة ٢ — عبر GitHub CLI (إن كان مثبّتاً)
```bash
gh auth login
gh repo create seo-norge --private --source=. --remote=origin --push
```

## بعد الرفع — النشر من Hostinger
- **Node.js Web App** (الواجهة): اختر «Deploy from GitHub» → اختر المستودع → مجلد `frontend` → أمر البناء `npm run build`، التشغيل `npm start`.
- **Custom PHP/HTML** (الـ backend): ارفع `backend` (أو انسخه من GitHub)، اجعل document root = `backend/public`.

> ملاحظة: `.env` لن يُرفع (محمي بـ .gitignore). أنشئه يدوياً على الخادم من `.env.example`.
