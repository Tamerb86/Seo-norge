# SEO Norge - Deployment Guide

## Quick Deployment Options

### Option 1: Railway (Recommended for Beginners)

Railway provides the easiest deployment experience with automatic builds and managed databases.

#### Step 1: Create Railway Account
1. Go to [railway.app](https://railway.app)
2. Sign up with GitHub

#### Step 2: Deploy Backend
```bash
# In Railway dashboard:
# 1. Click "New Project"
# 2. Select "Deploy from GitHub repo"
# 3. Choose your repository
# 4. Set root directory to "backend"
```

Add these environment variables in Railway:
```
APP_NAME=SEO Norge
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-backend.railway.app
FRONTEND_URL=https://your-frontend.railway.app
DB_CONNECTION=pgsql
SUPABASE_URL=your-supabase-url
SUPABASE_KEY=your-supabase-anon-key
SUPABASE_JWT_SECRET=your-jwt-secret
OPENAI_API_KEY=sk-your-key
STRIPE_KEY=pk_live_xxx
STRIPE_SECRET=sk_live_xxx
```

#### Step 3: Add PostgreSQL Database
1. In your Railway project, click "New"
2. Select "Database" → "PostgreSQL"
3. Railway will automatically set DB_* variables

#### Step 4: Deploy Frontend
1. Create another service in the same project
2. Set root directory to "frontend"
3. Add environment variables:
```
NEXT_PUBLIC_SUPABASE_URL=your-supabase-url
NEXT_PUBLIC_SUPABASE_ANON_KEY=your-supabase-anon-key
NEXT_PUBLIC_API_URL=https://your-backend.railway.app
```

---

### Option 2: Docker (VPS/Self-hosted)

#### Prerequisites
- Ubuntu 22.04 server
- Docker & Docker Compose installed
- Domain name pointing to your server

#### Step 1: Clone Repository
```bash
git clone https://github.com/your-repo/seo-norge.git
cd seo-norge
```

#### Step 2: Configure Environment
```bash
cp .env.example .env
nano .env
```

Edit all required variables:
```env
# Database
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=seo_norge
DB_USERNAME=postgres
DB_PASSWORD=your-secure-password

# Supabase
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_KEY=your-anon-key
SUPABASE_JWT_SECRET=your-jwt-secret

# OpenAI
OPENAI_API_KEY=sk-your-api-key

# Stripe
STRIPE_KEY=pk_live_xxx
STRIPE_SECRET=sk_live_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx
```

#### Step 3: Start Services
```bash
docker-compose up -d
```

#### Step 4: Run Migrations
```bash
docker-compose exec backend php artisan migrate --force
```

#### Step 5: Configure Nginx (Optional - for custom domain)
```nginx
server {
    listen 80;
    server_name seo-norge.no www.seo-norge.no;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name seo-norge.no www.seo-norge.no;

    ssl_certificate /etc/letsencrypt/live/seo-norge.no/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/seo-norge.no/privkey.pem;

    # Frontend
    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }

    # Backend API
    location /api {
        proxy_pass http://localhost:8000;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

#### Step 6: SSL Certificate
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d seo-norge.no -d www.seo-norge.no
```

---

### Option 3: Vercel + Supabase + Railway

Best for scalability and performance.

#### Frontend on Vercel
1. Import project to Vercel
2. Set root directory to `frontend`
3. Add environment variables
4. Deploy

#### Backend on Railway
1. Deploy backend to Railway
2. Add PostgreSQL database
3. Configure environment variables

#### Database on Supabase
1. Create Supabase project
2. Use Supabase PostgreSQL as your database
3. Enable Row Level Security (RLS)

---

## Post-Deployment Checklist

### 1. Database Setup
```bash
# Run migrations
php artisan migrate --force

# Create admin user (optional)
php artisan tinker
>>> User::create(['email' => 'admin@example.com', ...])
```

### 2. Stripe Webhook
1. Go to Stripe Dashboard → Webhooks
2. Add endpoint: `https://your-api.com/api/webhooks/stripe`
3. Select events: `checkout.session.completed`, `customer.subscription.*`
4. Copy webhook secret to `STRIPE_WEBHOOK_SECRET`

### 3. Supabase Auth Setup
1. Go to Supabase Dashboard → Authentication
2. Enable Email provider
3. (Optional) Enable Google OAuth
4. Set Site URL to your frontend domain
5. Add redirect URLs

### 4. Cron Jobs
Add to your server's crontab:
```bash
# Check rankings daily at 3 AM
0 3 * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

Or use Laravel Scheduler in `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    $schedule->job(new CheckAllRankings)->dailyAt('03:00');
}
```

### 5. Queue Worker
For production, use Supervisor:
```ini
[program:seo-norge-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/backend/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=2
```

---

## Domain Setup

### Norwegian Domain (.no)
1. Register at [Domeneshop.no](https://domeneshop.no) (~280 NOK/year)
2. Point DNS to your server:
   - A record: `@` → Your server IP
   - A record: `www` → Your server IP
   - A record: `api` → Your server IP (if separate)

### SSL Certificate
Railway and Vercel provide automatic SSL.
For VPS, use Let's Encrypt (free).

---

## Scaling Considerations

### Database
- Start with Railway/Supabase PostgreSQL
- Upgrade to dedicated instance when needed
- Add read replicas for high traffic

### Caching
- Enable Redis for session/cache
- Use Cloudflare for CDN

### Queue Processing
- Use Redis queue driver
- Scale workers based on load

---

## Monitoring

### Recommended Tools
- **Uptime**: UptimeRobot (free)
- **Errors**: Sentry
- **Analytics**: Plausible or Umami (privacy-focused)
- **Logs**: Papertrail or Logtail

---

## Backup Strategy

### Database
```bash
# Daily backup script
pg_dump -h localhost -U postgres seo_norge > backup_$(date +%Y%m%d).sql
```

### Files
- User uploads (if any)
- Environment files
- SSL certificates

---

## Security Checklist

- [ ] HTTPS enabled
- [ ] Environment variables secured
- [ ] Database credentials rotated
- [ ] Stripe webhook secret configured
- [ ] CORS properly configured
- [ ] Rate limiting enabled
- [ ] SQL injection protection (Laravel handles this)
- [ ] XSS protection (React handles this)
- [ ] CSRF protection enabled

---

## Support

For deployment issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check Docker logs: `docker-compose logs -f`
3. Check browser console for frontend errors

---

© 2024 SEO Norge. All rights reserved.
