# Magento 2 Open Source — Learning Guide 🎓

## Table of Contents

1. [Quick Setup](#quick-setup)
2. [Architecture Overview](#architecture-overview)
3. [Key Concepts](#key-concepts)
4. [Directory Structure](#directory-structure)
5. [Learning Path](#learning-path)
6. [Professional Magento 2 Skill Roadmap](#professional-magento-2-skill-roadmap)
7. [Useful CLI Commands](#useful-cli-commands)
8. [Building Your First Module](#building-your-first-module)
9. [Resources](#resources)

---

## Quick Setup

### Prerequisites

| Tool | Purpose |
|------|---------|
| **Docker Desktop** | Containerized environment |
| **Adobe Marketplace Account** | Free — needed for Composer auth keys |

### Step-by-step

```bash
# 1. Get Marketplace credentials (free account)
#    → https://commercemarketplace.adobe.com/
#    → My Profile → Access Keys → Create a New Access Key

# 2. Make the setup script executable
chmod +x setup.sh magento.sh

# 3. Run the automated setup
./setup.sh

# 4. Open your browser
#    Storefront → http://localhost:8080
#    Admin      → Run: ./magento.sh info:adminuri
                -> http://localhost:8080/admin_s961x6u (example)
#                (admin / <your-password>)
```

---

## Architecture Overview

Magento 2 uses a **modular, service-oriented architecture**:

```
┌──────────────────────────────────────────────────┐
│                  PRESENTATION                     │
│    Themes (PHTML, Layout XML, RequireJS, LESS)    │
├──────────────────────────────────────────────────┤
│                SERVICE CONTRACTS                  │
│        (API interfaces / Web APIs / REST)         │
├──────────────────────────────────────────────────┤
│                  DOMAIN LAYER                     │
│    Models, Resource Models, Repositories          │
├──────────────────────────────────────────────────┤
│                PERSISTENCE LAYER                  │
│         MariaDB / MySQL + OpenSearch              │
└──────────────────────────────────────────────────┘
```

**Key Patterns:**
- **Dependency Injection (DI)** — configured via `di.xml`
- **Plugins (Interceptors)** — modify behavior without rewriting classes
- **Events & Observers** — decouple logic
- **Service Contracts** — stable PHP interfaces for APIs
- **EAV (Entity-Attribute-Value)** — flexible product/customer attributes

---

## Key Concepts

| Concept | Description |
|---------|-------------|
| **Module** | Self-contained unit of code (e.g., `Magento_Catalog`, `Magento_Checkout`) |
| **Theme** | Controls the storefront look & feel (inherits from parent themes) |
| **Layout XML** | Defines page structure (containers, blocks, and their order) |
| **Block** | PHP class that prepares data for templates |
| **Template (.phtml)** | Renders HTML using data from Blocks |
| **Plugin** | Wraps (before/after/around) any public method |
| **Observer** | Reacts to dispatched events |
| **Widget** | Reusable, configurable content block |
| **API / Web API** | REST & GraphQL endpoints |
| **Cron** | Scheduled background tasks (reindexing, emails, etc.) |

---

## Directory Structure

```
magento2/
├── app/
│   ├── code/               # Custom & community modules (YOUR code goes here)
│   │   └── Vendor/
│   │       └── ModuleName/
│   ├── design/             # Themes
│   │   ├── frontend/       # Storefront themes
│   │   └── adminhtml/      # Admin themes
│   └── etc/                # Global config (env.php, config.php)
│
├── bin/magento              # CLI tool ⭐
├── dev/                     # Tests & development tools
├── generated/               # Auto-generated code (DI, Interceptors)
├── lib/                     # Core libraries & JS
├── pub/                     # Public web root (static, media)
├── setup/                   # Installation scripts
├── var/                     # Cache, logs, reports, sessions
│   ├── log/                 # debug.log, system.log, exception.log
│   └── cache/
└── vendor/                  # Composer dependencies (Magento core lives here)
```

---

## Learning Path

### 🟢 Phase 1 — Explore & Understand (Week 1-2)

1. **Navigate the Admin Panel** (run `./magento.sh info:adminuri`)
   - Create products (Simple, Configurable, Virtual)
   - Set up categories
   - Configure store settings (Stores → Configuration)
   - Manage customers and orders

2. **Understand the module system**
   ```bash
   ./magento.sh module:status         # List all modules
   ./magento.sh module:disable Magento_TwoFactorAuth  # Disable 2FA for dev
   ```

3. **Read the logs**
   - `var/log/system.log` — general logs
   - `var/log/debug.log` — debug info
   - `var/log/exception.log` — errors

### 🟡 Phase 2 — Build Your First Module (Week 3-4)

4. **Create a "Hello World" module** (see [below](#building-your-first-module))
5. **Add a custom route (Controller)**
6. **Create a Block + Template**
7. **Add Layout XML**
8. **Learn Dependency Injection** (`di.xml`)

### 🟠 Phase 3 — Intermediate Skills (Month 2)

9. **Plugins** — Create before/after/around plugins
10. **Events & Observers** — Listen to `checkout_cart_add_product_complete` etc.
11. **Database** — Create custom tables with `db_schema.xml`
12. **Admin Grid** — Build a UI Component listing
13. **REST API** — Create custom API endpoints
14. **GraphQL** — Add a custom GraphQL resolver

### 🔴 Phase 4 — Advanced Topics (Month 3+)

15. **Theming** — Create a child theme, customize layouts
16. **Frontend** — RequireJS, Knockout.js, UI Components
17. **Indexers** — Custom indexers for performance
18. **Message Queues** — Async processing with RabbitMQ
19. **Cron Jobs** — Scheduled tasks
20. **Performance** — Full Page Cache, Varnish, Redis tuning
21. **Testing** — Unit, Integration, API Functional, MFTF

---

## Professional Magento 2 Skill Roadmap

This roadmap maps your scope into **practical skills + deliverables**. Use it as a checklist.

### Phase 1 — Foundations (Weeks 1–2)
**Goal:** Understand platform basics and ship small changes safely.

- [ ] Install and run Magento locally (done)
- [ ] Navigate Admin: products, categories, customers, orders
- [ ] Learn file structure: `app/code`, `app/design`, `pub`, `var/log`
- [ ] Build a simple module (route + controller + layout + template)
- [ ] Edit a template and deploy static content when needed

**Deliverable:** “Hello World” page + basic CMS page + product created in Admin.

### Phase 2 — Customization (Weeks 3–5)
**Goal:** Customize modules, themes, and frontend.

- [ ] Create a custom module with config and admin settings
- [ ] Create a child theme and override a template
- [ ] Add JS via RequireJS and add CSS/LESS to the theme
- [ ] Mobile‑first layout adjustments (grid, responsive components)

**Deliverable:** Custom storefront section with JS interaction + responsive styling.

### Phase 3 — Commerce Features (Weeks 6–8)
**Goal:** Build core ecommerce features safely.

- [ ] Product attributes (EAV) and custom attribute sets
- [ ] Custom product listing page + filters
- [ ] Cart/checkout customization (layout XML + JS)
- [ ] Promotions (catalog/cart price rules)
- [ ] Email templates for order lifecycle

**Deliverable:** Custom catalog page + checkout UI adjustment + promo rule.

### Phase 4 — APIs & Integrations (Weeks 9–12)
**Goal:** Integrate third‑party systems (ERP/CRM), APIs, and web services.

- [ ] Create REST API endpoint (service contract + webapi.xml)
- [ ] Create GraphQL resolver
- [ ] Build a simple integration using a client module
- [ ] Webhooks / async messaging with RabbitMQ

**Deliverable:** External system sync (e.g., product import) via API.

### Phase 5 — Performance, Security, and Reliability (Ongoing)
**Goal:** Ship production‑grade stores.

- [ ] Cache layers (FPC, Redis) and cache debugging
- [ ] Indexers and cron jobs
- [ ] Performance profiling (slow logs, page cache hits)
- [ ] Security patches & upgrades (Composer + upgrade flow)
- [ ] Harden admin access, enforce HTTPS, review CSP

**Deliverable:** Performance checklist + secure deployment checklist.

### Phase 6 — Team Practices & Delivery (Ongoing)
**Goal:** Work like a professional team member.

- [ ] Git workflow + code review checklist
- [ ] Documentation updates for each change
- [ ] Debugging production issues (logs, reports, exception handling)
- [ ] Estimation and milestone planning

**Deliverable:** Release notes + technical documentation for each feature.

### Map to Your Scope (Quick Reference)

| Scope Item | What to Practice |
|-----------|-------------------|
| Design/develop/maintain Magento | Module + theme lifecycle, upgrades |
| Customize modules/themes/APIs | Modules, layout XML, service contracts |
| Mobile‑first responsive apps | Theme overrides, LESS, responsive layout |
| Performance/scalability/security | Cache, indexers, upgrades, CSP |
| ERP/CRM integrations | REST/GraphQL, queues, data imports |
| Collaboration | Code reviews, tickets, documentation |
| Debug/resolve issues | Logs, exception reports, dev tools |
| Development standards | Module structure, DI, testing basics |
| Stay current | Release notes, Magento community, best practices |
| Apply security patches | Composer upgrade process |
| Documentation archive | Change logs, ADRs, runbooks |
| Deliver on time/scope | Planning + milestone tracking |

## Useful CLI Commands

```bash
# --- Cache ---
./magento.sh cache:flush                   # Flush all caches
./magento.sh cache:clean                   # Clean expired caches
./magento.sh cache:status                  # Show cache status

# --- Setup ---
./magento.sh setup:upgrade                 # Run DB migrations after module changes
./magento.sh setup:di:compile              # Compile DI (dependency injection)
./magento.sh setup:static-content:deploy   # Deploy CSS/JS/images

# --- Modules ---
./magento.sh module:status                 # Show enabled/disabled modules
./magento.sh module:enable Vendor_Module   # Enable a module
./magento.sh module:disable Vendor_Module  # Disable a module

# --- Indexers ---
./magento.sh indexer:reindex               # Reindex all
./magento.sh indexer:status                # Check indexer status

# --- Developer ---
./magento.sh deploy:mode:show              # Show current mode
./magento.sh deploy:mode:set developer     # Set developer mode

# --- Maintenance ---
./magento.sh maintenance:enable            # Enable maintenance mode
./magento.sh maintenance:disable           # Disable maintenance mode

# --- Info ---
./magento.sh info:adminuri                 # Show admin URL
./magento.sh catalog:product:attributes:cleanup  # Clean unused attributes
```

---

## Building Your First Module

Create a module at `app/code/Learning/HelloWorld/`:

### 1. Registration (`registration.php`)
```php
<?php
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Learning_HelloWorld',
    __DIR__
);
```

### 2. Module declaration (`etc/module.xml`)
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="Learning_HelloWorld" setup_version="1.0.0">
        <sequence>
            <module name="Magento_Store"/>
        </sequence>
    </module>
</config>
```

### 3. Route definition (`etc/frontend/routes.xml`)
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:App/etc/routes.xsd">
    <router id="standard">
        <route id="helloworld" frontName="helloworld">
            <module name="Learning_HelloWorld"/>
        </route>
    </router>
</config>
```

### 4. Controller (`Controller/Index/Index.php`)
```php
<?php
namespace Learning\HelloWorld\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

class Index implements HttpGetActionInterface
{
    private PageFactory $pageFactory;

    public function __construct(PageFactory $pageFactory)
    {
        $this->pageFactory = $pageFactory;
    }

    public function execute()
    {
        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set('Hello World');
        return $page;
    }
}
```

### 5. Layout (`view/frontend/layout/helloworld_index_index.xml`)
```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <referenceContainer name="content">
            <block class="Learning\HelloWorld\Block\Hello"
                   name="helloworld"
                   template="Learning_HelloWorld::hello.phtml"/>
        </referenceContainer>
    </body>
</page>
```

### 6. Block (`Block/Hello.php`)
```php
<?php
namespace Learning\HelloWorld\Block;

use Magento\Framework\View\Element\Template;

class Hello extends Template
{
    public function getGreeting(): string
    {
        return 'Hello from Magento 2! 🎉';
    }
}
```

### 7. Template (`view/frontend/templates/hello.phtml`)
```phtml
<?php /** @var \Learning\HelloWorld\Block\Hello $block */ ?>
<div class="hello-world">
    <h1><?= $block->escapeHtml($block->getGreeting()) ?></h1>
    <p>This is your first custom Magento 2 module!</p>
</div>
```

### 8. Enable & test
```bash
./magento.sh module:enable Learning_HelloWorld
./magento.sh setup:upgrade
./magento.sh cache:flush
# Visit → http://localhost:8080/helloworld
```

---

## Resources

### Official Documentation
- 📖 [Developer Documentation](https://developer.adobe.com/commerce/docs/)
- 📖 [PHP Developer Guide](https://developer.adobe.com/commerce/php/development/)
- 📖 [Frontend Developer Guide](https://developer.adobe.com/commerce/frontend-core/guide/)
- 📖 [REST API Reference](https://developer.adobe.com/commerce/webapi/rest/)
- 📖 [GraphQL API](https://developer.adobe.com/commerce/webapi/graphql/)

### Community & Learning
- 🎓 [Magento U (Adobe Training)](https://learning.adobe.com/catalog.html?solution=Adobe%20Commerce)
- 💬 [Magento Community Slack](https://magentocommeng.slack.com/)
- 📺 [Magento YouTube Channel](https://www.youtube.com/c/MagentoCommunity)
- 🏆 [Stack Exchange](https://magento.stackexchange.com/)
- 🐙 [GitHub Repository](https://github.com/magento/magento2)

### Recommended Books / Courses
- "Magento 2 Development Essentials" — Packt
- "Mastering Magento 2" — Packt
- Mage2.tv (video tutorials)
- SwiftOtter Study Guide (for certification prep)

---

## Services in This Docker Setup

| Service | URL | Credentials |
|---------|-----|-------------|
| **Storefront** | http://localhost:8080 | — |
| **Admin Panel** | Run: `./magento.sh info:adminuri` | admin / `<your-password>` |
| **MailCatcher** | http://localhost:1080 | — |
| **RabbitMQ** | http://localhost:15672 | magento / magento |
| **OpenSearch** | http://localhost:9200 | — |
| **MariaDB** | localhost:3307 | magento / magento |
| **Redis** | localhost:6380 | — |

---

Happy learning! 🚀
