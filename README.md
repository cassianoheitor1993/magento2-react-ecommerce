# 🐾 Petshop — Magento 2 + PWA Studio E-Commerce

A full-stack pet e-commerce platform built with **Magento 2 Open Source 2.4.7** (backend) and **PWA Studio** (React headless storefront), orchestrated with Docker Compose.

![Magento 2.4.7](https://img.shields.io/badge/Magento-2.4.7-orange?logo=magento)
![PWA Studio](https://img.shields.io/badge/PWA%20Studio-React-blue?logo=react)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker)
![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php)

---

## 🏗️ Architecture

```
┌──────────────────────────────────────────────────┐
│                  PWA Studio (React)              │
│            pwa-studio/react-storefront/          │
│    Hero · Widgets · Categories · Carousel · CTA  │
└────────────────────┬─────────────────────────────┘
                     │ GraphQL
┌────────────────────▼─────────────────────────────┐
│              Magento 2 Open Source                │
│           magento2/app/code/Petshop/             │
│  Homepage · Blog · Marketing · SampleData        │
├──────────────────────────────────────────────────┤
│  Nginx │ PHP-FPM 8.3 │ MariaDB │ OpenSearch     │
│  Redis │ RabbitMQ    │ MailCatcher               │
└──────────────────────────────────────────────────┘
```

## 📦 Custom Magento Modules

| Module | Description |
|--------|-------------|
| **Petshop_Homepage** | AI-powered homepage builder with drag-and-drop widget system (hero, CTA, trust badges, categories carousel, testimonials, newsletter). Admin UI for layout config + OpenAI content generation. |
| **Petshop_Blog** | CMS blog engine with categories, posts, and SEO metadata |
| **Petshop_Marketing** | Newsletter, email campaigns, and promotional tools |
| **Petshop_SampleData** | Seed data for demo/dev environments |

## 🚀 Quick Start

### Prerequisites

- [Docker Desktop](https://docs.docker.com/desktop/) (Docker + Docker Compose)
- [Node.js](https://nodejs.org/) 18+ and npm
- [Adobe Commerce Marketplace](https://commercemarketplace.adobe.com/) account (free — for Composer auth keys)

### 1. Clone & configure

```bash
git clone https://github.com/YOUR_USERNAME/magento2-petshop.git
cd magento2-petshop

# Create auth.json from template (add your Marketplace keys)
cp auth.json.sample auth.json
# Edit auth.json with your public/private keys
```

### 2. Start the backend

```bash
chmod +x setup.sh magento.sh
./setup.sh
```

This will:
- Start all Docker containers (Nginx, PHP-FPM, MariaDB, OpenSearch, Redis, RabbitMQ, MailCatcher)
- Install Magento 2.4.7 via Composer
- Run the Magento installer
- Configure developer mode

### 3. Install custom modules

```bash
./magento.sh module:enable Petshop_Homepage Petshop_Blog Petshop_Marketing Petshop_SampleData
./magento.sh setup:upgrade
./magento.sh setup:di:compile
./magento.sh cache:flush
```

### 4. Start the frontend

```bash
cd pwa-studio/react-storefront
cp .env.example .env
npm install
npm run watch
```

### 5. Access the app

| Service | URL |
|---------|-----|
| 🛒 PWA Storefront | `https://localhost:PORT` (shown in terminal) |
| 🔧 Magento Admin | `http://localhost:8080/admin` |
| 📧 MailCatcher | `http://localhost:1080` |
| 🐇 RabbitMQ | `http://localhost:15672` (magento/magento) |

**Admin credentials:** `admin` / `Admin123!`

---

## 🗂️ Project Structure

```
magento2-petshop/
├── docker-compose.yml          # Dev services (7 containers)
├── setup.sh                    # One-command Magento install
├── magento.sh                  # CLI helper (e.g. ./magento.sh cache:flush)
├── auth.json.sample            # Composer auth template
├── nginx/                      # Nginx config
├── php-fpm/                    # PHP-FPM config
├── magento2/                   # Magento 2 application
│   ├── composer.json           # PHP dependencies
│   ├── composer.lock
│   ├── app/
│   │   ├── code/Petshop/       # ⭐ Custom modules
│   │   │   ├── Homepage/       #   AI homepage builder
│   │   │   ├── Blog/           #   Blog engine
│   │   │   ├── Marketing/      #   Marketing tools
│   │   │   └── SampleData/     #   Demo seed data
│   │   ├── design/             # Theme overrides
│   │   └── etc/config.php      # Module registry
│   └── pub/                    # Web root
├── pwa-studio/react-storefront/ # ⭐ React PWA frontend
│   ├── package.json
│   ├── src/
│   │   ├── components/         # UI components
│   │   │   ├── HomeRevamp/     #   Hero slider + widget renderer
│   │   │   ├── HomeWidgets/    #   CTA, Trust Badges, Carousel, etc.
│   │   │   ├── Header/         #   Site header
│   │   │   └── AnnouncementBar/
│   │   ├── overrides/CMS/      # CMS page override (homepage logic)
│   │   └── queries/            # GraphQL queries
│   └── .env.example            # PWA env template
└── .vscode/launch.json         # VS Code debug configs
```

---

## 🛠️ Development

### Common commands

```bash
# Magento CLI (via Docker)
./magento.sh cache:flush
./magento.sh setup:upgrade
./magento.sh setup:di:compile
./magento.sh module:status
./magento.sh indexer:reindex

# Frontend
cd pwa-studio/react-storefront
npm run watch          # Dev server with HMR
npm run build          # Production build

# Docker
docker compose up -d   # Start all services
docker compose down    # Stop all services
docker compose logs -f phpfpm   # Follow PHP logs
```

### VS Code

The project includes launch configurations in `.vscode/launch.json`:
- **Backend: Docker Up + Cache Flush** — starts containers and flushes cache
- **Frontend: npm run watch** — starts the PWA dev server
- **Full Stack** — runs both simultaneously

---

## 🌐 Deployment

### Production build (frontend)

```bash
cd pwa-studio/react-storefront
npm run build
```

The built assets go to `dist/` and can be served by any Node.js hosting (Vercel, Railway, DigitalOcean App Platform, etc.) using the UPWARD middleware.

### Backend deployment

Magento 2 can be deployed to any PHP 8.3 hosting with:
- MariaDB 10.6+ or MySQL 8.0+
- OpenSearch 2.x or Elasticsearch 8.x
- Redis 7.x
- RabbitMQ 3.x (optional)
- Nginx or Apache

For production, update `magento2/app/etc/env.php` with production database credentials and run:

```bash
bin/magento deploy:mode:set production
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
```

---

## 📝 License

This is a portfolio/learning project. Magento Open Source is licensed under [OSL-3.0](https://opensource.org/licenses/OSL-3.0).
