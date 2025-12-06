#!/bin/bash
# Deploy script for Fly.io with PostgreSQL database setup

# Color codes
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}=== Demolition Traders - Fly.io Deployment ===${NC}\n"

# Check if flyctl is installed
if ! command -v flyctl &> /dev/null; then
    echo -e "${RED}Error: flyctl CLI is not installed${NC}"
    echo "Install from: https://fly.io/docs/getting-started/installing-flyctl/"
    exit 1
fi

echo -e "${GREEN}✓ flyctl is installed${NC}\n"

# Get app name
APP_NAME="demolitiontraders"
DB_APP_NAME="demolitiontraders-db"

echo "App name: $APP_NAME"
echo "Database app name: $DB_APP_NAME"
echo ""

# Check if DATABASE_URL secret exists
echo -e "${YELLOW}Checking for DATABASE_URL secret...${NC}"
if flyctl secrets list --app $APP_NAME | grep -q DATABASE_URL; then
    echo -e "${GREEN}✓ DATABASE_URL already set${NC}"
else
    echo -e "${YELLOW}DATABASE_URL not found. You need to set it manually:${NC}"
    echo ""
    echo "1. Get your PostgreSQL connection string:"
    echo "   flyctl postgres connect -a $DB_APP_NAME"
    echo ""
    echo "2. Or from the Fly dashboard:"
    echo "   https://fly.io/dashboard/organizations/personal/apps/$DB_APP_NAME"
    echo ""
    echo "3. Set the secret:"
    echo "   flyctl secrets set DATABASE_URL='postgresql://user:password@$DB_APP_NAME.flycast/dbname' --app $APP_NAME"
    echo ""
fi

# Push latest changes
echo -e "${YELLOW}Pushing latest changes to GitHub...${NC}"
git push origin main
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Pushed to GitHub${NC}"
else
    echo -e "${RED}✗ Failed to push to GitHub${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Deploying to Fly.io...${NC}"
flyctl deploy --app $APP_NAME
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Deployment successful${NC}"
else
    echo -e "${RED}✗ Deployment failed${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}=== Deployment Complete ===${NC}"
echo ""
echo "Next steps:"
echo "1. Verify the app is running: flyctl status --app $APP_NAME"
echo "2. Check logs: flyctl logs --app $APP_NAME"
echo "3. Visit: https://$APP_NAME.fly.dev"
echo ""
echo "If you see database connection errors:"
echo "1. Import the database schema:"
echo "   flyctl ssh console -a $APP_NAME"
echo "   psql \$DATABASE_URL < demolitiontraders_pg_fly.sql"
echo ""
