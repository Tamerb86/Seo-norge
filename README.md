# SEO Norge 🇳🇴

**Norges første AI-drevne SEO-verktøy** - The first AI-powered SEO tool for the Norwegian market.

![License](https://img.shields.io/badge/license-Proprietary-red)
![Version](https://img.shields.io/badge/version-1.0.0-blue)
![Status](https://img.shields.io/badge/status-In%20Development-yellow)

---

## 📋 Overview

SEO Norge is a comprehensive SEO platform designed specifically for the Norwegian market. It combines powerful rank tracking, keyword research, competitor analysis, and AI-powered content optimization in one easy-to-use platform.

### Key Features

- 🔍 **Rank Tracking** - Track your keywords on Google.no daily
- 📊 **Keyword Research** - Find profitable keywords with Norwegian search volume
- 🏆 **Competitor Analysis** - Analyze your competitors' SEO strategies
- 🤖 **AI Content Analysis** - Get AI-powered recommendations in Norwegian
- 📍 **Local SEO** - Optimize for local Norwegian searches
- 📈 **Reports & Alerts** - Automated reports and ranking alerts

---

## 🛠️ Tech Stack

| Component | Technology |
|-----------|------------|
| **Frontend** | Next.js 14, React 18, TypeScript, Tailwind CSS |
| **Backend** | Laravel 11, PHP 8.2 |
| **Database** | PostgreSQL 15 |
| **Cache** | Redis 7 |
| **Auth** | Supabase Auth |
| **AI** | OpenAI GPT-4 |
| **Payments** | Stripe |
| **Deployment** | Docker, Railway |

---

## 🚀 Quick Start

### Prerequisites

- Docker & Docker Compose
- Node.js 18+ (for local development)
- PHP 8.2+ (for local development)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/seo-norge.git
   cd seo-norge
   ```

2. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

3. **Configure environment variables**
   - Edit `.env` and add your API keys (Supabase, OpenAI, Stripe, etc.)

4. **Start with Docker**
   ```bash
   docker-compose up -d
   ```

5. **Run migrations**
   ```bash
   docker-compose exec backend php artisan migrate --seed
   ```

6. **Access the application**
   - Frontend: http://localhost:3000
   - Backend API: http://localhost:8000/api

---

## 📁 Project Structure

```
seo-norge/
├── backend/                 # Laravel API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   └── Middleware/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Jobs/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   └── tests/
├── frontend/                # Next.js App
│   ├── app/
│   │   ├── (auth)/
│   │   ├── (dashboard)/
│   │   └── api/
│   ├── components/
│   ├── lib/
│   ├── hooks/
│   └── types/
├── docs/                    # Documentation
├── scripts/                 # Utility scripts
├── docker-compose.yml
└── README.md
```

---

## 🔐 Environment Variables

See `.env.example` for all required environment variables.

### Required Services

1. **Supabase** - Authentication
   - Create project at https://supabase.com
   - Get URL, anon key, and service role key

2. **OpenAI** - AI Features
   - Get API key at https://platform.openai.com

3. **Stripe** - Payments
   - Get keys at https://dashboard.stripe.com

4. **Scraping API** - Rank Tracking
   - Options: ScrapingRobot, Bright Data, SerpAPI

---

## 📊 API Documentation

### Authentication

All API endpoints require authentication via Bearer token from Supabase.

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/domains
```

### Main Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/domains` | List all domains |
| POST | `/api/domains` | Add a new domain |
| GET | `/api/keywords` | List all keywords |
| POST | `/api/keywords` | Add keywords |
| GET | `/api/rankings` | Get ranking history |
| POST | `/api/ai/analyze` | AI content analysis |

---

## 🧪 Testing

### Backend Tests
```bash
docker-compose exec backend php artisan test
```

### Frontend Tests
```bash
docker-compose exec frontend npm test
```

---

## 🚢 Deployment

### Railway (Recommended)

1. Connect your GitHub repository to Railway
2. Add environment variables
3. Deploy automatically on push

### Manual Deployment

1. Build production images:
   ```bash
   docker-compose -f docker-compose.prod.yml build
   ```

2. Push to your container registry

3. Deploy to your server

---

## 💰 Pricing Plans

| Plan | Price | Features |
|------|-------|----------|
| **Free** | $0 | 1 domain, 10 keywords, 10 AI analyses |
| **Starter** | $29/mo | 3 domains, 100 keywords, 100 AI analyses |
| **Professional** | $79/mo | 10 domains, 500 keywords, 500 AI analyses |
| **Agency** | $199/mo | Unlimited |

---

## 📄 License

This is proprietary software. All rights reserved.

---

## 🤝 Support

For support, email support@seo-norge.no or open an issue.

---

## 🗺️ Roadmap

- [x] Project setup
- [ ] Authentication system
- [ ] Rank tracking
- [ ] Keyword research
- [ ] Competitor analysis
- [ ] AI content analysis
- [ ] Local SEO features
- [ ] Stripe integration
- [ ] Multi-language support (Bokmål, Nynorsk)

---

**Built with ❤️ for the Norwegian market**
