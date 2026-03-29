<?php
/**
 * iACC — Public API Documentation
 * Public-facing page showing Sales Channel API endpoints, authentication, and examples.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['landing_lang']) ? $_SESSION['landing_lang'] : 'en');
if (!in_array($lang, ['en', 'th'])) $lang = 'en';
$_SESSION['landing_lang'] = $lang;

$langFile = __DIR__ . '/inc/lang/' . $lang . '.php';
$t = file_exists($langFile) ? require $langFile : require __DIR__ . '/inc/lang/en.php';
function __($key) { global $t; return $t[$key] ?? $key; }
$htmlLang = $lang === 'th' ? 'th' : 'en';
?>
<!DOCTYPE html>
<html lang="<?= $htmlLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation — iACC Sales Channel API</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        :root { --primary: #8e44ad; --primary-dark: #6c3483; --dark: #1e293b; --gray: #64748b; --bg: #f8fafc; --code-bg: #0f172a; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; color: var(--dark); line-height: 1.6; background: var(--bg); }
        .top-bar { background: var(--dark); color: white; padding: 16px 20px; }
        .top-bar .inner { max-width: 1100px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .top-bar a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; }
        .top-bar a:hover { color: white; }
        .top-bar h1 { font-size: 20px; font-weight: 700; }
        .top-bar h1 span { color: var(--primary); }
        .container { max-width: 1100px; margin: 0 auto; padding: 40px 20px 80px; display: grid; grid-template-columns: 240px 1fr; gap: 40px; }
        /* Sidebar */
        .sidebar { position: sticky; top: 20px; align-self: start; }
        .sidebar h3 { font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--gray); margin-bottom: 12px; letter-spacing: 0.5px; }
        .sidebar a { display: block; padding: 8px 14px; font-size: 14px; color: var(--dark); text-decoration: none; border-radius: 8px; margin-bottom: 2px; transition: all 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(142,68,173,0.1); color: var(--primary); }
        .sidebar .method-tag { display: inline-block; font-size: 11px; font-weight: 700; padding: 2px 6px; border-radius: 4px; margin-right: 6px; font-family: 'JetBrains Mono', monospace; }
        .tag-get { background: #dbeafe; color: #1d4ed8; }
        .tag-post { background: #dcfce7; color: #15803d; }
        .tag-put { background: #fef3c7; color: #92400e; }
        .tag-del { background: #fee2e2; color: #991b1b; }
        /* Content */
        .content section { margin-bottom: 48px; padding-bottom: 48px; border-bottom: 1px solid #e2e8f0; }
        .content h2 { font-size: 28px; font-weight: 700; margin-bottom: 12px; }
        .content h3 { font-size: 20px; font-weight: 600; margin-bottom: 10px; margin-top: 32px; }
        .content p { color: var(--gray); margin-bottom: 16px; font-size: 15px; }
        .endpoint-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; margin-top: 32px; }
        .endpoint-header .method { font-size: 13px; font-weight: 700; padding: 4px 10px; border-radius: 6px; font-family: 'JetBrains Mono', monospace; }
        .endpoint-header .url { font-size: 15px; font-family: 'JetBrains Mono', monospace; color: var(--dark); font-weight: 500; }
        pre { background: var(--code-bg); color: #e2e8f0; padding: 20px 24px; border-radius: 12px; overflow-x: auto; font-family: 'JetBrains Mono', monospace; font-size: 13px; line-height: 1.7; margin-bottom: 16px; }
        pre .comment { color: #64748b; }
        pre .key { color: #7dd3fc; }
        pre .string { color: #86efac; }
        pre .number { color: #fbbf24; }
        code { background: #e2e8f0; padding: 2px 8px; border-radius: 4px; font-family: 'JetBrains Mono', monospace; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th { text-align: left; padding: 10px 14px; background: #f1f5f9; font-size: 13px; font-weight: 600; color: var(--dark); }
        table td { padding: 10px 14px; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: var(--gray); }
        table td code { background: #f1f5f9; font-size: 12px; }
        .note { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px; font-size: 14px; color: #1e40af; }
        .note i { margin-right: 8px; }
        .warn { background: #fefce8; border: 1px solid #fde68a; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px; font-size: 14px; color: #92400e; }
        @media (max-width: 768px) {
            .container { grid-template-columns: 1fr; }
            .sidebar { position: static; }
        }
    </style>
</head>
<body>
<div class="top-bar">
    <div class="inner">
        <h1><span>iACC</span> API Documentation</h1>
        <div>
            <a href="landing.php?lang=<?= $lang ?>"><i class="fa fa-arrow-left"></i> Back to Home</a>
        </div>
    </div>
</div>

<div class="container">
    <!-- Sidebar Navigation -->
    <nav class="sidebar">
        <h3>Getting Started</h3>
        <a href="#overview">Overview</a>
        <a href="#auth">Authentication</a>
        <a href="#errors">Error Handling</a>

        <h3 style="margin-top:24px;">Endpoints</h3>
        <a href="#subscription"><span class="method-tag tag-get">GET</span> Subscription</a>
        <a href="#products"><span class="method-tag tag-get">GET</span> Products</a>
        <a href="#categories"><span class="method-tag tag-get">GET</span> Categories</a>
        <a href="#create-order"><span class="method-tag tag-post">POST</span> Create Order</a>
        <a href="#get-order"><span class="method-tag tag-get">GET</span> Get Order</a>
        <a href="#list-orders"><span class="method-tag tag-get">GET</span> List Orders</a>
        <a href="#update-order"><span class="method-tag tag-put">PUT</span> Update Order</a>

        <h3 style="margin-top:24px;">Resources</h3>
        <a href="template-howto.php?lang=<?= $lang ?>">Setup Guide</a>
        <a href="template-demo.php?lang=<?= $lang ?>">Live Demo</a>
    </nav>

    <!-- Main Content -->
    <div class="content">
        <!-- Overview -->
        <section id="overview">
            <h2>Sales Channel API</h2>
            <p>The iACC Sales Channel API allows you to integrate your website, app, or platform with iACC's accounting system. Receive orders from any channel — website, LINE, Facebook, email — and they automatically create Purchase Requests, Purchase Orders, and Products in your iACC account.</p>

            <h3>Base URL</h3>
<pre><span class="string">https://your-iacc-domain.com</span>/api.php/v1</pre>

            <h3>Quick Start</h3>
            <p>1. Login to your iACC account → <strong>Settings → Sales Channel API</strong></p>
            <p>2. Activate a plan (Free Trial available)</p>
            <p>3. Generate an API Key and Secret</p>
            <p>4. Use the key and secret in HTTP headers for all requests</p>
        </section>

        <!-- Authentication -->
        <section id="auth">
            <h2>Authentication</h2>
            <p>All API requests require two custom HTTP headers:</p>

            <table>
                <tr><th>Header</th><th>Description</th></tr>
                <tr><td><code>X-API-Key</code></td><td>Your API key (starts with <code>iACC_</code>)</td></tr>
                <tr><td><code>X-API-Secret</code></td><td>Your API secret key</td></tr>
            </table>

            <h3>Example Request</h3>
<pre>curl -X GET "https://iacc.f2.co.th/api.php/v1/subscription" \
  -H "<span class="key">X-API-Key</span>: <span class="string">iACC_your_api_key_here</span>" \
  -H "<span class="key">X-API-Secret</span>: <span class="string">your_api_secret_here</span>"</pre>

            <div class="warn">
                <i class="fa fa-exclamation-triangle"></i>
                <strong>Security:</strong> Never expose your API Secret in client-side JavaScript or public repositories. Always make API calls from your server-side code (PHP, Node.js, Python, etc.).
            </div>
        </section>

        <!-- Error Handling -->
        <section id="errors">
            <h2>Error Handling</h2>
            <p>All responses use standard JSON format:</p>
<pre>{
  <span class="key">"success"</span>: <span class="number">false</span>,
  <span class="key">"error"</span>: {
    <span class="key">"message"</span>: <span class="string">"Invalid API credentials"</span>,
    <span class="key">"code"</span>: <span class="string">"AUTH_FAILED"</span>
  }
}</pre>
            <table>
                <tr><th>HTTP Code</th><th>Meaning</th></tr>
                <tr><td><code>200</code></td><td>Success</td></tr>
                <tr><td><code>201</code></td><td>Created (new order)</td></tr>
                <tr><td><code>400</code></td><td>Bad request (missing fields)</td></tr>
                <tr><td><code>401</code></td><td>Unauthorized (invalid credentials)</td></tr>
                <tr><td><code>403</code></td><td>Forbidden (plan limit exceeded)</td></tr>
                <tr><td><code>404</code></td><td>Not found</td></tr>
                <tr><td><code>500</code></td><td>Server error</td></tr>
            </table>
        </section>

        <!-- GET Subscription -->
        <section id="subscription">
            <div class="endpoint-header">
                <span class="method tag-get">GET</span>
                <span class="url">/api.php/v1/subscription</span>
            </div>
            <p>Get your current API subscription details, plan info, and usage.</p>
<pre><span class="comment">// Response</span>
{
  <span class="key">"success"</span>: <span class="number">true</span>,
  <span class="key">"data"</span>: {
    <span class="key">"plan"</span>: <span class="string">"professional"</span>,
    <span class="key">"status"</span>: <span class="string">"active"</span>,
    <span class="key">"company_name"</span>: <span class="string">"My Tour Company"</span>,
    <span class="key">"orders_used"</span>: <span class="number">42</span>,
    <span class="key">"orders_limit"</span>: <span class="number">5000</span>,
    <span class="key">"orders_remaining"</span>: <span class="number">4958</span>
  }
}</pre>
        </section>

        <!-- GET Products -->
        <section id="products">
            <div class="endpoint-header">
                <span class="method tag-get">GET</span>
                <span class="url">/api.php/v1/products</span>
            </div>
            <p>Fetch all products (models) in your company. Includes type, category, and brand info.</p>

            <table>
                <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                <tr><td><code>category_id</code></td><td>integer (optional)</td><td>Filter by category ID</td></tr>
            </table>

<pre><span class="comment">// Response</span>
{
  <span class="key">"success"</span>: <span class="number">true</span>,
  <span class="key">"data"</span>: {
    <span class="key">"products"</span>: [
      {
        <span class="key">"id"</span>: <span class="number">453</span>,
        <span class="key">"name"</span>: <span class="string">"Angthong Marine Park Full Day"</span>,
        <span class="key">"price"</span>: <span class="string">"1900.00"</span>,
        <span class="key">"description"</span>: <span class="string">""</span>,
        <span class="key">"type_id"</span>: <span class="number">502</span>,
        <span class="key">"type_name"</span>: <span class="string">"Full Day Trip"</span>,
        <span class="key">"category_id"</span>: <span class="number">183</span>,
        <span class="key">"category_name"</span>: <span class="string">"Speedboat Tour"</span>,
        <span class="key">"brand_id"</span>: <span class="number">128</span>,
        <span class="key">"brand_name"</span>: <span class="string">"My Samui Island Tour"</span>
      }
    ],
    <span class="key">"total"</span>: <span class="number">19</span>
  }
}</pre>
        </section>

        <!-- GET Categories -->
        <section id="categories">
            <div class="endpoint-header">
                <span class="method tag-get">GET</span>
                <span class="url">/api.php/v1/categories</span>
            </div>
            <p>Fetch all categories with nested types and product counts.</p>

<pre><span class="comment">// Response</span>
{
  <span class="key">"success"</span>: <span class="number">true</span>,
  <span class="key">"data"</span>: {
    <span class="key">"categories"</span>: [
      {
        <span class="key">"id"</span>: <span class="number">183</span>,
        <span class="key">"name"</span>: <span class="string">"Speedboat Tour"</span>,
        <span class="key">"description"</span>: <span class="string">"Fast speedboat excursions"</span>,
        <span class="key">"types"</span>: [
          {
            <span class="key">"id"</span>: <span class="number">502</span>,
            <span class="key">"name"</span>: <span class="string">"Full Day Trip"</span>,
            <span class="key">"product_count"</span>: <span class="number">4</span>
          }
        ]
      }
    ],
    <span class="key">"total"</span>: <span class="number">6</span>
  }
}</pre>
        </section>

        <!-- POST Create Order -->
        <section id="create-order">
            <div class="endpoint-header">
                <span class="method tag-post">POST</span>
                <span class="url">/api.php/v1/orders</span>
            </div>
            <p>Create a new booking order. This automatically creates a Customer, Purchase Request, Purchase Order, and Product in iACC.</p>

            <h3>Request Body</h3>
            <table>
                <tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr>
                <tr><td><code>guest_name</code></td><td>string</td><td>Yes</td><td>Customer full name</td></tr>
                <tr><td><code>guest_email</code></td><td>string</td><td>No</td><td>Customer email</td></tr>
                <tr><td><code>guest_phone</code></td><td>string</td><td>No</td><td>Customer phone number</td></tr>
                <tr><td><code>check_in</code></td><td>date</td><td>Yes</td><td>Service date (YYYY-MM-DD)</td></tr>
                <tr><td><code>check_out</code></td><td>date</td><td>No</td><td>End date (YYYY-MM-DD)</td></tr>
                <tr><td><code>room_type</code></td><td>string</td><td>No</td><td>Product/service description</td></tr>
                <tr><td><code>guests</code></td><td>integer</td><td>No</td><td>Number of guests (default: 1)</td></tr>
                <tr><td><code>total_amount</code></td><td>decimal</td><td>Yes</td><td>Total order amount</td></tr>
                <tr><td><code>currency</code></td><td>string</td><td>No</td><td>THB, USD, EUR (default: THB)</td></tr>
                <tr><td><code>notes</code></td><td>string</td><td>No</td><td>Special requests</td></tr>
                <tr><td><code>channel</code></td><td>string</td><td>No</td><td>website, email, line, facebook, manual</td></tr>
            </table>

            <h3>Example</h3>
<pre>curl -X POST "https://iacc.f2.co.th/api.php/v1/orders" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: iACC_your_key" \
  -H "X-API-Secret: your_secret" \
  -d '{
    <span class="key">"guest_name"</span>: <span class="string">"John Smith"</span>,
    <span class="key">"guest_email"</span>: <span class="string">"john@email.com"</span>,
    <span class="key">"check_in"</span>: <span class="string">"2026-04-15"</span>,
    <span class="key">"room_type"</span>: <span class="string">"Angthong Marine Park Full Day"</span>,
    <span class="key">"guests"</span>: <span class="number">3</span>,
    <span class="key">"total_amount"</span>: <span class="number">5700</span>,
    <span class="key">"currency"</span>: <span class="string">"THB"</span>,
    <span class="key">"channel"</span>: <span class="string">"website"</span>
  }'</pre>

<pre><span class="comment">// Response (201 Created)</span>
{
  <span class="key">"success"</span>: <span class="number">true</span>,
  <span class="key">"data"</span>: {
    <span class="key">"order_id"</span>: <span class="number">63</span>,
    <span class="key">"status"</span>: <span class="string">"confirmed"</span>,
    <span class="key">"po_id"</span>: <span class="number">2147</span>,
    <span class="key">"po_ref"</span>: <span class="string">"API-20260329-1223"</span>,
    <span class="key">"customer_id"</span>: <span class="number">520</span>,
    <span class="key">"total_amount"</span>: <span class="string">"5700.00"</span>,
    <span class="key">"created_at"</span>: <span class="string">"2026-03-29 14:32:00"</span>
  }
}</pre>

            <div class="note">
                <i class="fa fa-info-circle"></i>
                <strong>Auto-Pipeline:</strong> Each order automatically creates a Customer record, Purchase Request (PR), Purchase Order (PO), and Product line item in iACC. No manual data entry needed!
            </div>
        </section>

        <!-- GET Order -->
        <section id="get-order">
            <div class="endpoint-header">
                <span class="method tag-get">GET</span>
                <span class="url">/api.php/v1/orders/{id}</span>
            </div>
            <p>Get details of a specific order by ID.</p>
<pre><span class="comment">// Response</span>
{
  <span class="key">"success"</span>: <span class="number">true</span>,
  <span class="key">"data"</span>: {
    <span class="key">"order"</span>: {
      <span class="key">"id"</span>: <span class="number">63</span>,
      <span class="key">"guest_name"</span>: <span class="string">"John Smith"</span>,
      <span class="key">"status"</span>: <span class="string">"confirmed"</span>,
      <span class="key">"total_amount"</span>: <span class="string">"5700.00"</span>,
      <span class="key">"po_id"</span>: <span class="number">2147</span>,
      <span class="key">"created_at"</span>: <span class="string">"2026-03-29 14:32:00"</span>
    }
  }
}</pre>
        </section>

        <!-- List Orders -->
        <section id="list-orders">
            <div class="endpoint-header">
                <span class="method tag-get">GET</span>
                <span class="url">/api.php/v1/orders</span>
            </div>
            <p>List all orders with pagination.</p>
            <table>
                <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                <tr><td><code>page</code></td><td>integer</td><td>Page number (default: 1)</td></tr>
                <tr><td><code>per_page</code></td><td>integer</td><td>Items per page (default: 15)</td></tr>
            </table>
        </section>

        <!-- Update Order -->
        <section id="update-order">
            <div class="endpoint-header">
                <span class="method tag-put">PUT</span>
                <span class="url">/api.php/v1/orders/{id}</span>
            </div>
            <p>Update an existing order status or details.</p>
            <table>
                <tr><th>Field</th><th>Type</th><th>Description</th></tr>
                <tr><td><code>status</code></td><td>string</td><td>confirmed, cancelled, completed</td></tr>
                <tr><td><code>notes</code></td><td>string</td><td>Additional notes</td></tr>
            </table>
        </section>
    </div>
</div>

</body>
</html>
