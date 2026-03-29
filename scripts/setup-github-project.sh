#!/bin/bash
# =============================================================================
# iACC GitHub Project Setup Script
# =============================================================================
# Creates GitHub Project Board, Milestones, Labels, and Issues
# based on the product roadmap.
#
# Usage:
#   export GITHUB_TOKEN="ghp_your_personal_access_token"
#   bash scripts/setup-github-project.sh
#
# Required token permissions: repo, project
# Generate at: https://github.com/settings/tokens
# =============================================================================

set -e

OWNER="psinthorn"
REPO="iacc-php-mvc"
API="https://api.github.com"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

# Check token
if [ -z "$GITHUB_TOKEN" ]; then
    echo -e "${RED}❌ GITHUB_TOKEN not set${NC}"
    echo "   export GITHUB_TOKEN=\"ghp_your_personal_access_token\""
    echo "   Generate at: https://github.com/settings/tokens (select 'repo' + 'project' scopes)"
    exit 1
fi

# Helper: API call
gh_api() {
    local method=$1
    local endpoint=$2
    local data=$3
    
    if [ -n "$data" ]; then
        curl -s -X "$method" \
            -H "Authorization: token $GITHUB_TOKEN" \
            -H "Accept: application/vnd.github.v3+json" \
            -H "Content-Type: application/json" \
            "$API$endpoint" \
            -d "$data"
    else
        curl -s -X "$method" \
            -H "Authorization: token $GITHUB_TOKEN" \
            -H "Accept: application/vnd.github.v3+json" \
            "$API$endpoint"
    fi
}

echo -e "${BLUE}🚀 iACC GitHub Project Setup${NC}"
echo "================================================"

# Verify authentication
echo -n "Verifying GitHub token... "
AUTH_CHECK=$(gh_api GET "/user" | python3 -c "import sys,json; print(json.load(sys.stdin).get('login',''))" 2>/dev/null)
if [ -z "$AUTH_CHECK" ]; then
    echo -e "${RED}FAILED${NC}"
    echo "Invalid token. Generate a new one at https://github.com/settings/tokens"
    exit 1
fi
echo -e "${GREEN}✅ Authenticated as $AUTH_CHECK${NC}"

# =============================================================================
# Step 1: Create Labels
# =============================================================================
echo -e "\n${YELLOW}📌 Step 1: Creating labels...${NC}"

create_label() {
    local name=$1
    local color=$2
    local desc=$3
    
    result=$(gh_api POST "/repos/$OWNER/$REPO/labels" \
        "{\"name\":\"$name\",\"color\":\"$color\",\"description\":\"$desc\"}" 2>/dev/null)
    
    if echo "$result" | grep -q '"id"'; then
        echo -e "  ${GREEN}✅${NC} $name"
    elif echo "$result" | grep -q 'already_exists'; then
        echo -e "  ${YELLOW}⏭️${NC}  $name (exists)"
    else
        echo -e "  ${RED}❌${NC} $name"
    fi
}

create_label "completed"      "0e8a16" "Completed milestone or feature"
create_label "in-progress"    "fbca04" "Currently being worked on"
create_label "planned"        "0075ca" "Planned for future development"
create_label "feature"        "a2eeef" "New feature or enhancement"
create_label "bug"            "d73a4a" "Something is not working"
create_label "security"       "b60205" "Security related"
create_label "api"            "006b75" "API related"
create_label "ui/ux"          "e99695" "UI/UX improvements"
create_label "infrastructure" "d4c5f9" "CI/CD, deployment, Docker"
create_label "documentation"  "0075ca" "Documentation updates"
create_label "mobile"         "c5def5" "Mobile app related"
create_label "integration"    "fbca04" "Third-party integrations"
create_label "accounting"     "bfd4f2" "Core accounting features"
create_label "template"       "f9d0c4" "Website template system"
create_label "tax"            "b60205" "Tax and compliance features"
create_label "expense"        "c2e0c6" "Expense tracking"
create_label "journal"        "d4c5f9" "Journal and voucher"
create_label "payment"        "0e8a16" "Payment gateway"
create_label "report"         "1d76db" "Reports and analytics"
create_label "v2.0"           "ededed" "Version 2.0"
create_label "v4.5"           "ededed" "Version 4.5"
create_label "v5.0"           "ededed" "Version 5.0"
create_label "v5.1"           "ededed" "Version 5.1"
create_label "v5.2"           "ededed" "Version 5.2"
create_label "v5.3"           "ededed" "Version 5.3"
create_label "v5.4"           "ededed" "Version 5.4"
create_label "v5.5"           "ededed" "Version 5.5"
create_label "v5.6"           "ededed" "Version 5.6"
create_label "v5.7"           "ededed" "Version 5.7"
create_label "v6.0"           "ededed" "Version 6.0"

# =============================================================================
# Step 2: Create Milestones
# =============================================================================
echo -e "\n${YELLOW}🎯 Step 2: Creating milestones...${NC}"

create_milestone() {
    local title=$1
    local desc=$2
    local state=$3
    local due=$4
    
    # Create as open first (API requires open on create)
    local data="{\"title\":\"$title\",\"description\":\"$desc\""
    if [ -n "$due" ]; then
        data="$data,\"due_on\":\"$due\""
    fi
    data="$data}"
    
    local result=$(gh_api POST "/repos/$OWNER/$REPO/milestones" "$data" 2>/dev/null)
    local num=""
    
    if echo "$result" | grep -q '"number"'; then
        num=$(echo "$result" | python3 -c "import sys,json; print(json.load(sys.stdin)['number'])" 2>/dev/null)
        echo -e "  ${GREEN}✅${NC} $title (#$num)" >&2
    elif echo "$result" | grep -q 'already_exists'; then
        num=$(gh_api GET "/repos/$OWNER/$REPO/milestones?state=all&per_page=100" 2>/dev/null | \
            python3 -c "import sys,json; ms=[m for m in json.load(sys.stdin) if m['title']=='$title']; print(ms[0]['number'] if ms else '')" 2>/dev/null)
        echo -e "  ${YELLOW}⏭️${NC}  $title (exists, #$num)" >&2
    fi
    
    # Close milestone if state=closed
    if [ "$state" = "closed" ] && [ -n "$num" ]; then
        gh_api PATCH "/repos/$OWNER/$REPO/milestones/$num" '{"state":"closed"}' > /dev/null 2>&1
    fi
    
    # Return ONLY the number on stdout
    echo "$num"
}

M_V20=$(create_milestone "v2.0 - Version 2.0 Launch" "UI/UX redesign, new login, multi-language, user management" "closed" "2025-12-31T23:59:59Z")
M_V45=$(create_milestone "v4.5 - Production Ready" "Security, AI Chatbot, Multi-Tenant SaaS, RBAC" "closed" "2026-03-31T23:59:59Z")
M_DEPLOY=$(create_milestone "Production Deployment" "cPanel deploy, CI/CD, health checks" "closed" "2026-03-31T23:59:59Z")
M_V50=$(create_milestone "v5.0 - MVC Architecture" "Complete MVC migration: 34 Controllers, 28 Models, 98 Views, 139 routes" "closed" "2026-03-31T23:59:59Z")
M_V51=$(create_milestone "v5.1 - Sales Channel API" "REST API, webhooks, order management, rate limiting" "closed" "2026-03-31T23:59:59Z")
M_V52=$(create_milestone "v5.2 - MVC View Modernization" "18 views upgraded, color-coded design system" "closed" "2026-03-31T23:59:59Z")
M_V53=$(create_milestone "v5.3 - Payment Gateway & Multi-Currency" "PromptPay, 10 currencies, Thai tax reports, slip review" "closed" "2026-06-30T23:59:59Z")
M_V54=$(create_milestone "v5.4 - Expense Tracking Module" "Daily expenses, categories, VAT/WHT, approval workflow" "closed" "2026-09-30T23:59:59Z")
M_V55=$(create_milestone "v5.5 - Dashboard Charts & Reports Hub" "Chart.js dashboard, reports center, AR aging" "closed" "2026-09-30T23:59:59Z")
M_V56=$(create_milestone "v5.6 - Journal Module & Voucher Classification" "Journal voucher, voucher_type, double-entry, chart of accounts" "closed" "2026-09-30T23:59:59Z")
M_V57=$(create_milestone "v5.7 - Website Templates & Admin Panel" "Tour company template, setup wizard, admin panel, API docs" "closed" "2026-09-30T23:59:59Z")
M_V60=$(create_milestone "v6.0 - Mobile App & Advanced Integrations" "React Native, bank connections, e-commerce, public API" "open" "2026-12-31T23:59:59Z")

# =============================================================================
# Step 3: Create Issues (organized by milestone)
# =============================================================================
echo -e "\n${YELLOW}📝 Step 3: Creating issues...${NC}"

create_issue() {
    local title=$1
    local body=$2
    local labels=$3
    local milestone=$4
    local state=$5
    
    # Build JSON via python3 to handle escaping properly
    local data=$(python3 -c "
import json
d = {'title': '''$title''', 'body': '''$body''', 'labels': [$labels]}
m = '''$milestone'''.strip()
if m and m.isdigit():
    d['milestone'] = int(m)
print(json.dumps(d))
" 2>/dev/null)
    
    if [ -z "$data" ]; then
        echo -e "  ${RED}❌${NC} $title (JSON build failed)"
        return
    fi
    
    local result=$(curl -s -X POST \
        -H "Authorization: token $GITHUB_TOKEN" \
        -H "Accept: application/vnd.github.v3+json" \
        -H "Content-Type: application/json" \
        "$API/repos/$OWNER/$REPO/issues" \
        -d "$data" 2>/dev/null)
    
    if echo "$result" | grep -q '"number"'; then
        local num=$(echo "$result" | python3 -c "import sys,json; print(json.load(sys.stdin)['number'])" 2>/dev/null)
        echo -e "  ${GREEN}✅${NC} #$num $title"
        
        # Close if completed
        if [ "$state" = "closed" ]; then
            gh_api PATCH "/repos/$OWNER/$REPO/issues/$num" '{"state":"closed","state_reason":"completed"}' > /dev/null 2>&1
            echo -e "      ${BLUE}🔒 Closed${NC}"
        fi
    else
        local msg=$(echo "$result" | python3 -c "import sys,json; r=json.load(sys.stdin); print(r.get('message','unknown'))" 2>/dev/null)
        echo -e "  ${RED}❌${NC} $title ($msg)"
    fi
}

echo -e "\n  ${BLUE}── v2.0 - Version 2.0 Launch ──${NC}"
create_issue \
    "New login system with modern UI" \
    "## v2.0 Feature\nImplement new authentication system with modern design.\n\n### Acceptance Criteria\n- [x] Modern login page\n- [x] Session management\n- [x] Remember me functionality" \
    '"completed","v2.0","ui/ux"' \
    "$M_V20" "closed"

create_issue \
    "Modern dashboard design" \
    "## v2.0 Feature\nComplete dashboard redesign with modern UI components.\n\n### Acceptance Criteria\n- [x] New layout\n- [x] Responsive design\n- [x] Summary cards" \
    '"completed","v2.0","ui/ux"' \
    "$M_V20" "closed"

create_issue \
    "Multi-language support (EN/TH)" \
    "## v2.0 Feature\nAdd English and Thai language support.\n\n### Acceptance Criteria\n- [x] Language files (en.php, th.php)\n- [x] Language switcher\n- [x] All pages translated" \
    '"completed","v2.0","feature"' \
    "$M_V20" "closed"

create_issue \
    "User management system" \
    "## v2.0 Feature\nRole-based user management.\n\n### Acceptance Criteria\n- [x] User CRUD\n- [x] Role assignment\n- [x] Permission levels" \
    '"completed","v2.0","feature"' \
    "$M_V20" "closed"

echo -e "\n  ${BLUE}── v4.5 - Production Ready ──${NC}"
create_issue \
    "Bcrypt password hashing" \
    "## v4.5 Security\nMigrate all passwords to bcrypt hashing.\n\n### Acceptance Criteria\n- [x] Bcrypt implementation\n- [x] Migration script\n- [x] Backward compatibility" \
    '"completed","v4.5","security"' \
    "$M_V45" "closed"

create_issue \
    "CSRF protection" \
    "## v4.5 Security\nAdd CSRF token validation to all forms.\n\n### Acceptance Criteria\n- [x] Token generation\n- [x] Token validation middleware\n- [x] All forms protected" \
    '"completed","v4.5","security"' \
    "$M_V45" "closed"

create_issue \
    "Rate limiting" \
    "## v4.5 Security\nImplement rate limiting for API and login.\n\n### Acceptance Criteria\n- [x] Rate limiter class\n- [x] Login attempt limiting\n- [x] API rate limiting" \
    '"completed","v4.5","security"' \
    "$M_V45" "closed"

create_issue \
    "SQL injection prevention" \
    "## v4.5 Security\nPrepared statements and input sanitization.\n\n### Acceptance Criteria\n- [x] All queries parameterized\n- [x] Input validation\n- [x] XSS prevention" \
    '"completed","v4.5","security"' \
    "$M_V45" "closed"

create_issue \
    "AI Chatbot with 29 tools" \
    "## v4.5 Feature\nOllama-powered AI chatbot with database tools.\n\n### Acceptance Criteria\n- [x] Ollama integration\n- [x] 29 database tools\n- [x] Chat UI\n- [x] Context management" \
    '"completed","v4.5","feature"' \
    "$M_V45" "closed"

create_issue \
    "Multi-Tenant SaaS architecture" \
    "## v4.5 Feature\nCompany-based multi-tenant data isolation.\n\n### Acceptance Criteria\n- [x] com_id filtering\n- [x] Session-based company switching\n- [x] Data isolation" \
    '"completed","v4.5","feature"' \
    "$M_V45" "closed"

create_issue \
    "RBAC system (Super Admin, Admin, User)" \
    "## v4.5 Feature\nRole-based access control.\n\n### Acceptance Criteria\n- [x] 3 permission levels\n- [x] Menu visibility\n- [x] Action restrictions" \
    '"completed","v4.5","security"' \
    "$M_V45" "closed"

echo -e "\n  ${BLUE}── Production Deployment ──${NC}"
create_issue \
    "cPanel production deployment" \
    "## Infrastructure\nDeploy to iacc.f2.co.th via cPanel/FTP.\n\n### Acceptance Criteria\n- [x] FTP deployment\n- [x] Database migration\n- [x] SSL certificate" \
    '"completed","infrastructure"' \
    "$M_DEPLOY" "closed"

create_issue \
    "CI/CD with GitHub Actions" \
    "## Infrastructure\nAutomated build, test, and deploy pipeline.\n\n### Acceptance Criteria\n- [x] PHP syntax checking\n- [x] Staging deploy on push to develop\n- [x] Production deploy on push to main\n- [x] FTP deploy with retry logic" \
    '"completed","infrastructure"' \
    "$M_DEPLOY" "closed"

echo -e "\n  ${BLUE}── v5.0 - MVC Architecture ──${NC}"
create_issue \
    "Complete MVC migration (34 Controllers, 28 Models, 98 Views)" \
    "## v5.0 Architecture\nMigrate from monolithic PHP to MVC pattern.\n\n### Acceptance Criteria\n- [x] 34 Controllers\n- [x] 28 Models\n- [x] 98 Views\n- [x] 139 routes\n- [x] PSR-4 autoloading\n- [x] 126 MVC tests\n- [x] Zero legacy routes" \
    '"completed","v5.0","feature"' \
    "$M_V50" "closed"

echo -e "\n  ${BLUE}── v5.1 - Sales Channel API ──${NC}"
create_issue \
    "REST API with CRUD operations" \
    "## v5.1 API\nFull REST API for sales channel integration.\n\n### Acceptance Criteria\n- [x] REST endpoints (orders, products, categories)\n- [x] API key authentication\n- [x] Rate limiting (60/min)\n- [x] Webhooks (HMAC-SHA256)\n- [x] 20 API tests" \
    '"completed","v5.1","api"' \
    "$M_V51" "closed"

create_issue \
    "Order management UI" \
    "## v5.1 Feature\nUI for managing API orders.\n\n### Acceptance Criteria\n- [x] Order list view\n- [x] Order detail view\n- [x] CSV/JSON export\n- [x] Status management" \
    '"completed","v5.1","ui/ux"' \
    "$M_V51" "closed"

echo -e "\n  ${BLUE}── v5.2 - View Modernization ──${NC}"
create_issue \
    "Modernize 18 MVC views with color-coded design system" \
    "## v5.2 UI/UX\nUpgrade all MVC views to modern design.\n\n### Acceptance Criteria\n- [x] PO module (4 views)\n- [x] Delivery module (4 views)\n- [x] Receipt module (3 views)\n- [x] Voucher module (3 views)\n- [x] PR, Payment, Billing (4 views)\n- [x] Color-coded module gradients\n- [x] Inter font + responsive\n- [x] 42/42 E2E tests passing" \
    '"completed","v5.2","ui/ux"' \
    "$M_V52" "closed"

echo -e "\n  ${BLUE}── v5.3 - Payment Gateway ──${NC}"
create_issue \
    "PromptPay QR payment integration" \
    "## v5.3 Payment\nQR PromptPay with slip upload and admin review.\n\n### Acceptance Criteria\n- [x] QR code generation\n- [x] Slip upload\n- [x] Admin review (approve/reject)" \
    '"completed","v5.3","payment"' \
    "$M_V53" "closed"

create_issue \
    "Multi-currency support (10 currencies, BOT rates)" \
    "## v5.3 Feature\nSupport 10 currencies with Bank of Thailand exchange rates.\n\n### Acceptance Criteria\n- [x] 10 currency codes\n- [x] BOT API integration\n- [x] Auto-conversion" \
    '"completed","v5.3","accounting"' \
    "$M_V53" "closed"

create_issue \
    "Thai tax reports (PP30, PND3/53, WHT)" \
    "## v5.3 Tax\nThai tax compliance reports.\n\n### Acceptance Criteria\n- [x] PP30 monthly VAT return\n- [x] PND3/53 withholding tax\n- [x] Annual tax dashboard\n- [x] CSV/JSON export" \
    '"completed","v5.3","tax"' \
    "$M_V53" "closed"

echo -e "\n  ${BLUE}── v5.4 - Expense Tracking ──${NC}"
create_issue \
    "Expense CRUD with categories and approval workflow" \
    "## v5.4 Expense\nFull expense tracking module.\n\n### Acceptance Criteria\n- [x] Expense CRUD\n- [x] 10 seeded categories (bilingual)\n- [x] Link to PO/PR/Invoice\n- [x] Approval workflow (draft → approved → paid)\n- [x] VAT/WHT live calculator\n- [x] Receipt/document upload\n- [x] Monthly summary + category chart\n- [x] Project cost report\n- [x] 13 routes, 6 views, 2 models" \
    '"completed","v5.4","expense"' \
    "$M_V54" "closed"

echo -e "\n  ${BLUE}── v5.5 - Dashboard & Reports ──${NC}"
create_issue \
    "Chart.js dashboard visualizations" \
    "## v5.5 Dashboard\nInteractive charts on the main dashboard.\n\n### Acceptance Criteria\n- [x] Revenue vs Expenses chart (12 months)\n- [x] Payment status doughnut\n- [x] Order status doughnut\n- [x] Chart.js 4.4.7 via CDN" \
    '"completed","v5.5","report"' \
    "$M_V55" "closed"

create_issue \
    "Reports Center hub and AR Aging report" \
    "## v5.5 Reports\nCentralized reports hub page.\n\n### Acceptance Criteria\n- [x] Reports Center hub page\n- [x] AR Aging report (5 buckets)\n- [x] Reports sidebar submenu\n- [x] 2 new routes (175 total)" \
    '"completed","v5.5","report"' \
    "$M_V55" "closed"

echo -e "\n  ${BLUE}── v5.6 - Journal Module ──${NC}"
create_issue \
    "Journal Voucher module with double-entry bookkeeping" \
    "## v5.6 Journal\nJournal voucher and voucher classification.\n\n### Acceptance Criteria\n- [x] Journal Voucher CRUD (create/view/list)\n- [x] voucher_type column (payment/receipt/journal)\n- [x] Debit/credit double-entry recording\n- [x] Chart of Accounts integration" \
    '"completed","v5.6","journal"' \
    "$M_V56" "closed"

echo -e "\n  ${BLUE}── v5.7 - Website Templates ──${NC}"
create_issue \
    "Tour Company Demo template with iACC API integration" \
    "## v5.7 Template\nSelf-hosted website template with booking.\n\n### Acceptance Criteria\n- [x] Tour Company Demo template\n- [x] 3-step Setup Wizard\n- [x] Product sync via API\n- [x] Booking form → iACC orders\n- [x] SQLite cache" \
    '"completed","v5.7","template","api"' \
    "$M_V57" "closed"

create_issue \
    "Template Admin Panel with authentication" \
    "## v5.7 Template\nAdmin panel for managing template.\n\n### Acceptance Criteria\n- [x] Admin Panel (4 tabs: Products, API, Sync, Bookings)\n- [x] Login system (bcrypt)\n- [x] Product toggle + sync\n- [x] Admin bar + login/logout flow" \
    '"completed","v5.7","template","ui/ux"' \
    "$M_V57" "closed"

create_issue \
    "API documentation and developer pages" \
    "## v5.7 Documentation\nPublic-facing developer documentation.\n\n### Acceptance Criteria\n- [x] API Docs page (api-docs.php)\n- [x] Template Setup Demo (template-demo.php)\n- [x] Hosting Guide (template-howto.php)\n- [x] Developers footer column" \
    '"completed","v5.7","documentation","api"' \
    "$M_V57" "closed"

echo -e "\n  ${BLUE}── v6.0 - Planned (Q4 2026) ──${NC}"
create_issue \
    "React Native mobile app (iOS/Android)" \
    "## v6.0 Mobile\nNative mobile app for iACC.\n\n### Features\n- [ ] iOS app\n- [ ] Android app\n- [ ] Dashboard view\n- [ ] Invoice management\n- [ ] Push notifications\n- [ ] Offline support" \
    '"planned","v6.0","mobile"' \
    "$M_V60" ""

create_issue \
    "Push notifications system" \
    "## v6.0 Mobile\nReal-time push notifications.\n\n### Features\n- [ ] Payment received alerts\n- [ ] Invoice due reminders\n- [ ] New order notifications\n- [ ] FCM / APNs integration" \
    '"planned","v6.0","mobile"' \
    "$M_V60" ""

create_issue \
    "Bank connections and auto-reconciliation" \
    "## v6.0 Integration\nDirect bank feed integration.\n\n### Features\n- [ ] Thai bank API connections\n- [ ] Auto-match payments to invoices\n- [ ] Transaction import\n- [ ] Reconciliation dashboard" \
    '"planned","v6.0","integration","payment"' \
    "$M_V60" ""

create_issue \
    "E-commerce platform integrations" \
    "## v6.0 Integration\nDirect integrations with e-commerce platforms.\n\n### Features\n- [ ] Shopify connector\n- [ ] WooCommerce plugin\n- [ ] Lazada/Shopee integration\n- [ ] Auto-sync orders and inventory" \
    '"planned","v6.0","integration","api"' \
    "$M_V60" ""

create_issue \
    "Public Developer API with documentation portal" \
    "## v6.0 API\nFull public API with developer portal.\n\n### Features\n- [ ] OpenAPI/Swagger documentation\n- [ ] API sandbox environment\n- [ ] SDK (PHP, JavaScript, Python)\n- [ ] Webhook management UI\n- [ ] Rate limit dashboard" \
    '"planned","v6.0","api","documentation"' \
    "$M_V60" ""

create_issue \
    "Receipt scanning with OCR" \
    "## v6.0 Feature\nAI-powered receipt scanning.\n\n### Features\n- [ ] Camera/upload receipt image\n- [ ] OCR text extraction\n- [ ] Auto-fill expense form\n- [ ] Thai receipt support\n- [ ] Multi-receipt batch scan" \
    '"planned","v6.0","feature","expense"' \
    "$M_V60" ""

# =============================================================================
# Step 4: Create GitHub Project (Projects V2 via GraphQL)
# =============================================================================
echo -e "\n${YELLOW}📋 Step 4: Creating GitHub Project board...${NC}"

# Get user ID for project creation
USER_ID=$(curl -s -H "Authorization: bearer $GITHUB_TOKEN" \
    -H "Content-Type: application/json" \
    -X POST https://api.github.com/graphql \
    -d '{"query":"query { viewer { id login } }"}' | \
    python3 -c "import sys,json; print(json.load(sys.stdin)['data']['viewer']['id'])" 2>/dev/null)

if [ -n "$USER_ID" ]; then
    # Create Project V2
    PROJECT_RESULT=$(curl -s -H "Authorization: bearer $GITHUB_TOKEN" \
        -H "Content-Type: application/json" \
        -X POST https://api.github.com/graphql \
        -d "{\"query\":\"mutation { createProjectV2(input: { ownerId: \\\"$USER_ID\\\", title: \\\"iACC Product Roadmap\\\", repositoryId: null }) { projectV2 { id number url } } }\"}" 2>/dev/null)
    
    PROJECT_URL=$(echo "$PROJECT_RESULT" | python3 -c "import sys,json; r=json.load(sys.stdin); print(r.get('data',{}).get('createProjectV2',{}).get('projectV2',{}).get('url',''))" 2>/dev/null)
    
    if [ -n "$PROJECT_URL" ]; then
        echo -e "  ${GREEN}✅ Project created: $PROJECT_URL${NC}"
    else
        echo -e "  ${YELLOW}⚠️  Could not create Project V2 (may need 'project' scope on token)${NC}"
        echo "     You can create it manually at: https://github.com/users/$OWNER/projects/new"
    fi
else
    echo -e "  ${YELLOW}⚠️  Could not get user ID for project creation${NC}"
    echo "     Create project manually at: https://github.com/users/$OWNER/projects/new"
fi

# =============================================================================
# Summary
# =============================================================================
echo -e "\n${GREEN}================================================${NC}"
echo -e "${GREEN}✅ GitHub Project Setup Complete!${NC}"
echo -e "${GREEN}================================================${NC}"
echo ""
echo -e "📊 View your project:"
echo -e "   Issues:     https://github.com/$OWNER/$REPO/issues"
echo -e "   Milestones: https://github.com/$OWNER/$REPO/milestones"
echo -e "   Labels:     https://github.com/$OWNER/$REPO/labels"
echo -e "   Projects:   https://github.com/users/$OWNER/projects"
echo ""
echo -e "${BLUE}💡 Going forward, create new tasks with:${NC}"
echo "   Use the same GitHub API or the GitHub web UI to create issues."
echo "   Assign them to the appropriate milestone and label."
echo ""
echo -e "${YELLOW}📝 Quick template for new feature issues:${NC}"
echo '   Title: "Feature name"'
echo '   Labels: feature, v5.8 (or next version)'
echo '   Milestone: v5.8 - Feature Name'
echo '   Body: "## Description\n...\n### Acceptance Criteria\n- [ ] Task 1"'
