# SEO Norge - Full Documentation

## Overview

SEO Norge is a comprehensive SEO SaaS platform designed specifically for the Norwegian market. It provides rank tracking, keyword research, competitor analysis, and AI-powered content optimization tools.

## Table of Contents

1. [Architecture](#architecture)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [API Reference](#api-reference)
5. [Database Schema](#database-schema)
6. [Deployment](#deployment)
7. [Customization](#customization)

---

## Architecture

### Tech Stack

| Component | Technology |
|-----------|------------|
| Frontend | Next.js 14 (App Router) + TypeScript + Tailwind CSS |
| Backend | Laravel 11 + PHP 8.2 |
| Database | PostgreSQL 15 |
| Authentication | Supabase Auth |
| AI | OpenAI API (GPT-4) |
| Payments | Stripe |
| Containerization | Docker + Docker Compose |

### Project Structure

```
seo-norge/
├── backend/                 # Laravel API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/
│   │   │   └── Middleware/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Jobs/
│   ├── database/migrations/
│   ├── routes/api.php
│   └── config/
├── frontend/               # Next.js App
│   ├── app/
│   │   ├── (auth)/
│   │   ├── (dashboard)/
│   │   └── api/
│   ├── components/
│   ├── lib/
│   └── hooks/
├── docs/
├── scripts/
├── docker-compose.yml
└── README.md
```

---

## Installation

### Prerequisites

- Docker & Docker Compose
- Node.js 18+ (for local development)
- PHP 8.2+ (for local development)
- Composer (for local development)

### Quick Start with Docker

```bash
# Clone the repository
git clone https://github.com/your-repo/seo-norge.git
cd seo-norge

# Copy environment file
cp .env.example .env

# Edit .env with your credentials
nano .env

# Start all services
docker-compose up -d

# Run database migrations
docker-compose exec backend php artisan migrate

# Access the application
# Frontend: http://localhost:3000
# Backend API: http://localhost:8000
```

### Local Development

#### Backend (Laravel)

```bash
cd backend

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Start development server
php artisan serve
```

#### Frontend (Next.js)

```bash
cd frontend

# Install dependencies
pnpm install

# Start development server
pnpm dev
```

---

## Configuration

### Environment Variables

#### Backend (.env)

```env
# Application
APP_NAME="SEO Norge"
APP_ENV=production
APP_KEY=base64:your-key-here
APP_DEBUG=false
APP_URL=https://api.seo-norge.no

# Frontend URL (for CORS)
FRONTEND_URL=https://seo-norge.no

# Database
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=seo_norge
DB_USERNAME=postgres
DB_PASSWORD=your-password

# Supabase Auth
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_KEY=your-anon-key
SUPABASE_JWT_SECRET=your-jwt-secret

# OpenAI
OPENAI_API_KEY=sk-your-api-key
OPENAI_MODEL=gpt-4

# Scraping Provider (choose one)
SCRAPING_PROVIDER=scrapingrobot
SCRAPING_API_KEY=your-api-key

# Stripe
STRIPE_KEY=pk_live_xxx
STRIPE_SECRET=sk_live_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx
STRIPE_PRICE_STARTER=price_xxx
STRIPE_PRICE_PROFESSIONAL=price_xxx
STRIPE_PRICE_AGENCY=price_xxx

# Queue
QUEUE_CONNECTION=redis
REDIS_HOST=localhost
REDIS_PORT=6379
```

#### Frontend (.env.local)

```env
NEXT_PUBLIC_SUPABASE_URL=https://your-project.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=your-anon-key
NEXT_PUBLIC_API_URL=https://api.seo-norge.no
```

---

## API Reference

### Authentication

All API endpoints (except `/health`) require authentication via Bearer token.

```
Authorization: Bearer <supabase-jwt-token>
```

### Endpoints

#### User

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/user/profile` | Get user profile |
| PUT | `/api/user/profile` | Update user profile |
| GET | `/api/user/usage` | Get usage statistics |
| GET | `/api/user/dashboard` | Get dashboard data |

#### Domains

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/domains` | List all domains |
| POST | `/api/domains` | Add new domain |
| GET | `/api/domains/{id}` | Get domain details |
| PUT | `/api/domains/{id}` | Update domain |
| DELETE | `/api/domains/{id}` | Delete domain |
| GET | `/api/domains/{id}/rankings/latest` | Get latest rankings |

#### Keywords

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/domains/{id}/keywords` | List keywords |
| POST | `/api/domains/{id}/keywords` | Add keyword |
| POST | `/api/domains/{id}/keywords/bulk` | Add multiple keywords |
| DELETE | `/api/domains/{id}/keywords/{kwId}` | Delete keyword |
| GET | `/api/domains/{id}/keywords/{kwId}/rankings` | Get ranking history |
| POST | `/api/domains/{id}/keywords/{kwId}/refresh` | Refresh ranking |

#### AI Tools

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/ai/analyze-content` | Analyze content for SEO |
| POST | `/api/ai/suggest-keywords` | Get keyword suggestions |
| POST | `/api/ai/generate-content` | Generate SEO content |

#### Billing

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/billing/plans` | Get available plans |
| POST | `/api/billing/subscribe` | Create checkout session |
| POST | `/api/billing/portal` | Get billing portal URL |
| GET | `/api/billing/invoices` | Get invoice history |

---

## Database Schema

### Users

```sql
CREATE TABLE users (
    id UUID PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    full_name VARCHAR(255),
    company VARCHAR(255),
    plan ENUM('free', 'starter', 'professional', 'agency') DEFAULT 'free',
    stripe_customer_id VARCHAR(255),
    stripe_subscription_id VARCHAR(255),
    email_verified_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Domains

```sql
CREATE TABLE domains (
    id UUID PRIMARY KEY,
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    domain VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    favicon_url VARCHAR(255),
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(user_id, domain)
);
```

### Keywords

```sql
CREATE TABLE keywords (
    id UUID PRIMARY KEY,
    domain_id UUID REFERENCES domains(id) ON DELETE CASCADE,
    keyword VARCHAR(255) NOT NULL,
    search_volume INTEGER,
    difficulty INTEGER,
    cpc DECIMAL(8,2),
    search_intent ENUM('informational', 'navigational', 'transactional', 'commercial'),
    language ENUM('nb', 'nn', 'en') DEFAULT 'nb',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(domain_id, keyword)
);
```

### Rankings

```sql
CREATE TABLE rankings (
    id UUID PRIMARY KEY,
    keyword_id UUID REFERENCES keywords(id) ON DELETE CASCADE,
    position INTEGER,
    url VARCHAR(255),
    title VARCHAR(255),
    description TEXT,
    featured_snippet BOOLEAN DEFAULT FALSE,
    checked_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## Deployment

### Railway (Recommended)

1. Create a new project on Railway
2. Add PostgreSQL database
3. Add Redis for queues
4. Deploy backend from GitHub
5. Deploy frontend from GitHub
6. Set environment variables
7. Configure custom domain

### Docker Production

```bash
# Build images
docker-compose -f docker-compose.prod.yml build

# Start services
docker-compose -f docker-compose.prod.yml up -d

# Run migrations
docker-compose exec backend php artisan migrate --force
```

### Manual VPS Deployment

1. Install Nginx, PHP 8.2, Node.js 18, PostgreSQL
2. Clone repository
3. Configure Nginx reverse proxy
4. Set up SSL with Let's Encrypt
5. Configure systemd services
6. Set up cron jobs for rank checking

---

## Customization

### Adding New Languages

1. Add language to `keywords.language` enum in migration
2. Update frontend language selector
3. Add translations to `resources/lang/`

### Adding New AI Features

1. Create new method in `AiService.php`
2. Add controller endpoint
3. Create frontend component
4. Update API documentation

### Changing Scraping Provider

1. Update `SCRAPING_PROVIDER` in `.env`
2. Add provider implementation in `RankTrackerService.php`
3. Configure API key

### White-labeling

1. Update branding in `frontend/app/layout.tsx`
2. Replace logo files in `public/`
3. Update colors in `tailwind.config.ts`
4. Update email templates

---

## Support

For technical support or questions about this codebase, please refer to:

- GitHub Issues
- Documentation Wiki
- API Reference

---

## License

This software is proprietary and confidential. Unauthorized copying, distribution, or use is strictly prohibited.

© 2024 SEO Norge. All rights reserved.
