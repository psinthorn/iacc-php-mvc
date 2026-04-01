# LINE OA Sales Channel - Implementation Guide

## Overview

The LINE OA Sales Channel module enables your iACC application to receive and process orders, bookings, and messages through LINE Official Account. Customers and agents can interact via LINE chat, and administrators manage everything through a web admin panel.

### Features
- **Customer Ordering** — Customers send "order item1 x2, item2 x1" via LINE
- **Booking** — Customers send "book 2026-04-15 10:00" to make reservations
- **Order Status** — Customers send "status" or "status LINE-20260401-001" to check orders
- **Payment Slip Upload** — Customers send payment slip images, auto-matched to pending orders
- **Auto-Reply Rules** — Configure keyword-based automatic responses (exact, contains, regex)
- **Agent Messaging** — Send text messages and voucher images to LINE users from admin panel
- **Webhook Event Log** — Full audit trail of all LINE Platform events
- **Bilingual UI** — All admin views support English/Thai (System 2 i18n)

---

## Prerequisites

1. **LINE Developers Account** — https://developers.line.biz/
2. **LINE Official Account** — Created via LINE Official Account Manager
3. **iACC application** — Running with Docker (PHP 8.2, MySQL 5.7)
4. **Public URL with HTTPS** — LINE requires HTTPS for webhook URLs (use ngrok for development)

---

## Step 1: Database Migration

Run the migration to create the 6 required tables:

```bash
# Copy migration file into MySQL container
docker cp database/migrations/007_line_oa_sales_channel.sql iacc_mysql:/tmp/

# Execute the migration
docker exec iacc_mysql mysql -uroot -proot iacc -e "source /tmp/007_line_oa_sales_channel.sql"
```

### Tables Created
| Table | Purpose |
|-------|---------|
| `line_oa_config` | Channel credentials & settings per company |
| `line_users` | LINE users who interact with the bot |
| `line_messages` | Message log (inbound & outbound) |
| `line_orders` | Orders and bookings created via LINE |
| `line_auto_replies` | Keyword-based auto-reply rules |
| `line_webhook_events` | Raw webhook event audit log |

### Verify Migration
```bash
docker exec iacc_mysql mysql -uroot -proot iacc -e "SHOW TABLES LIKE 'line%';"
```

Expected output: 6 tables listed.

---

## Step 2: LINE Developers Console Setup

### 2.1 Create a Provider
1. Go to https://developers.line.biz/console/
2. Click **Create** under Providers
3. Enter your company/organization name

### 2.2 Create a Messaging API Channel
1. Under your provider, click **Create a new channel**
2. Select **Messaging API**
3. Fill in the required fields:
   - **Channel name**: Your business name
   - **Channel description**: Brief description
   - **Category**: Select appropriate category
   - **Subcategory**: Select appropriate subcategory
4. Agree to terms and click **Create**

### 2.3 Get Channel Credentials
From the channel settings page, note these values:

| Setting | Location | Example |
|---------|----------|---------|
| **Channel ID** | Basic settings | `1234567890` |
| **Channel Secret** | Basic settings → Channel secret | `abc123def456...` |
| **Channel Access Token** | Messaging API → Issue | `long-token-string...` |

### 2.4 Configure Webhook URL
1. Go to **Messaging API** tab
2. Set **Webhook URL** to:
   ```
   https://yourdomain.com/line-webhook.php?company_id=YOUR_COMPANY_ID
   ```
3. Enable **Use webhook**: ON
4. Disable **Auto-reply messages**: OFF (we handle this in code)
5. Disable **Greeting messages**: OFF (we handle this in code)
6. Click **Verify** to test the connection

> **Development**: Use ngrok to expose your local server:  
> ```bash
> ngrok http 80
> ```
> Then set webhook URL to: `https://xxxx.ngrok.io/line-webhook.php?company_id=1`

---

## Step 3: Configure in Admin Panel

1. Log in to iACC with a Developer role account
2. Navigate to **LINE OA → Settings** in the sidebar
3. Enter the credentials from Step 2:
   - **Channel ID**
   - **Channel Secret**
   - **Channel Access Token**
4. Toggle **Active** → ON
5. Optionally configure:
   - **Auto-Reply** → ON/OFF
   - **Greeting Message** — Sent when users add your LINE account
6. Click **Save**

---

## Step 4: Test the Integration

### 4.1 Add the LINE Official Account
1. In LINE Developers Console → Messaging API tab, scan the QR code with LINE app
2. Add the official account as a friend

### 4.2 Test User Registration
- Send any message → Check **LINE OA → Users** in admin panel
- A new user should appear with your LINE display name and profile picture

### 4.3 Test Ordering
Send via LINE chat:
```
order Laptop x1, Mouse x2
```
Expected: Confirmation flex message with order reference (LINE-YYYYMMDD-NNN)

### 4.4 Test Booking
Send via LINE chat:
```
book 2026-04-15 10:00
```
Expected: Booking confirmation flex message

### 4.5 Test Status Check
Send via LINE chat:
```
status
```
Expected: Reply with your latest order status

### 4.6 Test Payment Slip
1. Create an order first
2. Send an image via LINE chat
3. Check admin panel → the order should show payment_status = "slip_uploaded"

### 4.7 Test Auto-Reply
1. Go to **LINE OA → Auto Replies** in admin panel
2. Add a rule:
   - Keyword: `hello`
   - Match Type: `contains`
   - Reply: `Welcome! How can I help you today?`
3. Send "hello" via LINE chat
4. Expected: Auto-reply message received

---

## Architecture

### File Structure
```
iAcc-PHP-MVC/
├── app/
│   ├── Controllers/LineOAController.php    # Admin panel controller
│   ├── Models/LineOA.php                   # Database operations
│   ├── Services/LineService.php            # LINE Messaging API client
│   └── Views/line-oa/
│       ├── dashboard.php                    # Stats & overview
│       ├── settings.php                     # Channel configuration
│       ├── orders.php                       # Order list
│       ├── order-detail.php                 # Single order view
│       ├── messages.php                     # Message log
│       ├── users.php                        # LINE users
│       ├── auto-replies.php                 # Auto-reply rules
│       ├── send-message.php                 # Push message form
│       └── webhook-log.php                  # Raw event log
├── line-webhook.php                        # Webhook endpoint (public)
├── database/migrations/
│   └── 007_line_oa_sales_channel.sql       # Migration
└── docs/
    └── LINE-OA-IMPLEMENTATION-GUIDE.md     # This guide
```

### Request Flow
```
LINE Platform  →  line-webhook.php  →  LineService (validate)
                                    →  LineOA Model (log & process)
                                    →  LineService (reply)

Admin Browser  →  index.php?page=line_*  →  LineOAController
                                          →  LineOA Model
                                          →  Views/line-oa/*.php
```

### Routes (app/Config/routes.php)
| Route | Controller Method | Purpose |
|-------|-------------------|---------|
| `line_dashboard` | `dashboard()` | Stats overview |
| `line_settings` | `settings()` | Channel configuration |
| `line_store` | `store()` | Handle all POST actions |
| `line_orders` | `orders()` | Order list with filters |
| `line_order_detail` | `orderDetail()` | Single order + conversation |
| `line_messages` | `messages()` | Full message log |
| `line_users` | `users()` | LINE user directory |
| `line_auto_replies` | `autoReplies()` | Auto-reply rule management |
| `line_webhook_log` | `webhookLog()` | Raw webhook events |
| `line_send_message` | `sendMessagePage()` | Push message form |

---

## Customization Guide

### Adding New Message Commands

Edit `line-webhook.php`, inside the `handleMessage()` function's text handler:

```php
// Add after existing command checks
if (stripos($text, 'menu') === 0) {
    // Custom menu command
    $lineService->replyText($replyToken, $channelToken, "Our menu:\n1. Product A\n2. Product B");
    return;
}
```

### Adding Flex Message Templates

Edit `app/Services/LineService.php`, add a new method:

```php
public function buildCustomFlex($data) {
    return [
        'type' => 'flex',
        'altText' => 'Custom notification',
        'contents' => [
            'type' => 'bubble',
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    ['type' => 'text', 'text' => $data['title'], 'weight' => 'bold', 'size' => 'lg'],
                    ['type' => 'text', 'text' => $data['body'], 'wrap' => true],
                ]
            ]
        ]
    ];
}
```

### Integrating with Existing PO/PR Workflow

To create a real Purchase Order from a LINE order, modify `LineOAController::store()` in the `update_order_status` action:

```php
case 'confirmed':
    // Create PR from LINE order
    $order = $model->getOrder($orderId, $this->companyFilter);
    $items = json_decode($order['items_json'], true);
    // Use existing PR creation logic...
    break;
```

---

## Reusing as a Module in Future Projects

### Required Files to Copy
1. `app/Services/LineService.php` — No project-specific dependencies
2. `app/Models/LineOA.php` — Uses `BaseModel` + prepared statements
3. `app/Controllers/LineOAController.php` — Uses `BaseController`
4. `app/Views/line-oa/` — All 9 view files (self-contained bilingual)
5. `line-webhook.php` — Standalone webhook, update autoloader path
6. `database/migrations/007_line_oa_sales_channel.sql` — Schema

### Integration Steps for New Project
1. Copy all files above to the new project
2. Run the database migration
3. Add the route entries to your routes config
4. Add sidebar menu items
5. Add i18n XML keys (or the views work without them using fallback strings)
6. Update namespace/autoloader paths if different

### Configuration Per Company
The module is **multi-tenant by design**. Each company has its own:
- LINE channel credentials (line_oa_config)
- LINE users (line_users.company_id)
- Orders (line_orders.company_id)
- Auto-reply rules (line_auto_replies.company_id)

All queries filter by `$_SESSION['com_id']` via BaseController's `$companyFilter`.

---

## Troubleshooting

### Webhook Not Receiving Events
1. Check webhook URL is HTTPS  
2. Verify channel secret matches in Settings page  
3. Check Nginx logs: `docker logs iacc_nginx --tail 50`  
4. Check PHP logs: `docker logs iacc_php --tail 50`  
5. Test webhook manually:
```bash
curl -X POST https://yourdomain.com/line-webhook.php?company_id=1 \
  -H "Content-Type: application/json" \
  -H "X-Line-Signature: test" \
  -d '{"events":[]}'
```
(Should return 200 for empty events array)

### Messages Not Sending
1. Verify Channel Access Token is correct  
2. Check token hasn't expired (reissue in LINE Console)  
3. Verify the LINE user hasn't blocked your account  
4. Check `line_messages` table for outbound entries

### Orders Not Creating
1. Check the message format: `order item1 x1, item2 x2`  
2. Check `line_webhook_events` table for raw event data  
3. Verify `company_id` parameter in webhook URL matches your company

### Database Errors
```bash
# Check table structure
docker exec iacc_mysql mysql -uroot -proot iacc -e "DESCRIBE line_orders;"

# Check recent records
docker exec iacc_mysql mysql -uroot -proot iacc -e "SELECT * FROM line_orders ORDER BY id DESC LIMIT 5;"
```
