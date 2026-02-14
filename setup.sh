#!/bin/bash
##############################################################################
# Magento 2 Open Source - Setup Script
#
# Prerequisites:
#   1. Docker & Docker Compose installed
#   2. Adobe Commerce Marketplace account (free) for authentication keys
#      → https://commercemarketplace.adobe.com/customer/account/login/
#      → Go to: My Profile → Access Keys → Create a New Access Key
#      → "Public Key" = Composer username
#      → "Private Key" = Composer password
#
# Usage:
#   chmod +x setup.sh && ./setup.sh
##############################################################################

set -e

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
MAGENTO_VERSION="2.4.7"

echo "============================================="
echo "  Magento 2 Open Source - Setup"
echo "  Version: ${MAGENTO_VERSION}"
echo "============================================="
echo ""

# -----------------------------------------------
# Step 1: Check Docker is running
# -----------------------------------------------
echo "🐳 Step 1: Checking Docker..."
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    echo "   → https://docs.docker.com/engine/install/"
    exit 1
fi
echo "✅ Docker is available."
echo ""

# -----------------------------------------------
# Step 2: Prompt for Marketplace credentials
# -----------------------------------------------
echo "🔑 Step 2: Adobe Commerce Marketplace Credentials"
echo "   Get yours at: https://commercemarketplace.adobe.com/"
echo "   (My Profile → Access Keys)"
echo ""

if [ ! -f "$PROJECT_DIR/auth.json" ]; then
    read -p "   Enter Public Key (username): " COMPOSER_USER
    read -sp "   Enter Private Key (password): " COMPOSER_PASS
    echo ""

    cat > "$PROJECT_DIR/auth.json" <<EOF
{
    "http-basic": {
        "repo.magento.com": {
            "username": "${COMPOSER_USER}",
            "password": "${COMPOSER_PASS}"
        }
    }
}
EOF
    echo "✅ auth.json created."
else
    echo "✅ auth.json already exists, skipping."
fi
echo ""

# -----------------------------------------------
# Step 3: Start Docker containers
# -----------------------------------------------
echo "🚀 Step 3: Starting Docker containers..."
cd "$PROJECT_DIR"
docker compose up -d
echo "✅ Containers are running."
echo ""

# -----------------------------------------------
# Step 4: Wait for services
# -----------------------------------------------
echo "⏳ Step 4: Waiting for services to be ready..."
sleep 10

echo "   Waiting for MariaDB..."
until docker compose exec db mysqladmin ping -h localhost --silent 2>/dev/null; do
    sleep 2
done
echo "   ✅ MariaDB is ready."

echo "   Waiting for OpenSearch..."
until curl -s http://localhost:9200 > /dev/null 2>&1; do
    sleep 2
done
echo "   ✅ OpenSearch is ready."
echo ""

# -----------------------------------------------
# Step 5: Install Magento via Composer
# -----------------------------------------------
echo "📦 Step 5: Installing Magento ${MAGENTO_VERSION} via Composer..."

# Copy auth.json into the container BEFORE running Composer
echo "   Copying auth.json into container..."
docker compose cp "$PROJECT_DIR/auth.json" phpfpm:/var/www/html/auth.json
docker compose exec -T phpfpm bash -c "
    mkdir -p /var/www/.composer && \
    cp /var/www/html/auth.json /var/www/.composer/auth.json
"

docker compose exec -T phpfpm bash -c "
    COMPOSER_AUTH=\$(cat /var/www/.composer/auth.json) \
    composer create-project \
        --repository-url=https://repo.magento.com/ \
        magento/project-community-edition=${MAGENTO_VERSION} \
        /tmp/magento2
"

# Move files into the web root
docker compose exec -T phpfpm bash -c "
    shopt -s dotglob && \
    cp -a /tmp/magento2/* /var/www/html/ && \
    rm -rf /tmp/magento2
"

echo "✅ Magento source code installed."
echo ""

# -----------------------------------------------
# Step 6: Run Magento installer
# -----------------------------------------------
echo "⚙️  Step 6: Running Magento setup:install..."
docker compose exec -T phpfpm bin/magento setup:install \
    --base-url=http://localhost:8080 \
    --db-host=db \
    --db-name=magento \
    --db-user=magento \
    --db-password=magento \
    --admin-firstname=Admin \
    --admin-lastname=User \
    --admin-email=admin@example.com \
    --admin-user=admin \
    --admin-password=Admin123! \
    --language=en_US \
    --currency=USD \
    --timezone=America/Chicago \
    --use-rewrites=1 \
    --search-engine=opensearch \
    --opensearch-host=opensearch \
    --opensearch-port=9200 \
    --opensearch-index-prefix=magento2 \
    --opensearch-timeout=15 \
    --session-save=redis \
    --session-save-redis-host=redis \
    --session-save-redis-port=6379 \
    --session-save-redis-db=2 \
    --cache-backend=redis \
    --cache-backend-redis-server=redis \
    --cache-backend-redis-port=6379 \
    --cache-backend-redis-db=0 \
    --amqp-host=rabbitmq \
    --amqp-port=5672 \
    --amqp-user=magento \
    --amqp-password=magento

echo "✅ Magento installed successfully!"
echo ""

# -----------------------------------------------
# Step 7: Set developer mode & deploy
# -----------------------------------------------
echo "🔧 Step 7: Configuring for development..."
docker compose exec -T phpfpm bin/magento deploy:mode:set developer
docker compose exec -T phpfpm bin/magento setup:di:compile
docker compose exec -T phpfpm bin/magento setup:static-content:deploy -f
docker compose exec -T phpfpm bin/magento indexer:reindex
docker compose exec -T phpfpm bin/magento cache:flush

echo ""
echo "============================================="
echo "  🎉 Magento 2 is ready!"
echo "============================================="
echo ""
echo "  🛒 Storefront:  http://localhost:8080"
echo "  🔧 Admin Panel: http://localhost:8080/admin"
echo "     Username:    admin"
echo "     Password:    Admin123!"
echo ""
echo "  📧 MailCatcher:  http://localhost:1080"
echo "  🐇 RabbitMQ:     http://localhost:15672"
echo "     (magento / magento)"
echo ""
echo "  📖 Next steps: Read the LEARNING_GUIDE.md"
echo "============================================="
