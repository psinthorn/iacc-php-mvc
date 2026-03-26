<?php
/**
 * API Documentation View
 * 
 * Interactive documentation for the iACC Sales Channel API.
 * Variables from AdminApiController::docs():
 *   (none — self-contained reference)
 */
?>
<style>
.api-doc { max-width: 960px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
.api-doc h2 { border-bottom: 2px solid #e9ecef; padding-bottom: 10px; margin-top: 30px; }
.api-doc h3 { color: #2c3e50; margin-top: 25px; }
.endpoint { background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border-left: 4px solid #3498db; }
.endpoint.post { border-left-color: #27ae60; }
.endpoint.put { border-left-color: #f39c12; }
.endpoint.delete { border-left-color: #e74c3c; }
.method-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-weight: bold; font-size: 0.85rem; color: white; margin-right: 8px; }
.method-get { background: #3498db; }
.method-post { background: #27ae60; }
.method-put { background: #f39c12; }
.method-delete { background: #e74c3c; }
.api-path { font-family: 'SFMono-Regular', Menlo, monospace; font-size: 0.95rem; color: #2c3e50; }
.code-block { background: #1e1e2e; color: #cdd6f4; padding: 15px; border-radius: 8px; font-family: 'SFMono-Regular', Menlo, monospace; font-size: 0.85rem; overflow-x: auto; margin: 10px 0; line-height: 1.5; }
.code-block .comment { color: #6c7086; }
.code-block .string { color: #a6e3a1; }
.code-block .key { color: #89b4fa; }
.param-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
.param-table th { background: #f8f9fa; text-align: left; padding: 8px 12px; border-bottom: 2px solid #dee2e6; font-size: 0.85rem; }
.param-table td { padding: 8px 12px; border-bottom: 1px solid #f0f0f0; font-size: 0.9rem; }
.param-table code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 0.85rem; }
.required { color: #e74c3c; font-size: 0.75rem; font-weight: bold; }
.optional { color: #95a5a6; font-size: 0.75rem; }
.error-code { display: inline-block; padding: 2px 8px; border-radius: 4px; font-family: monospace; font-size: 0.85rem; }
.toc { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.toc a { text-decoration: none; color: #3498db; display: block; padding: 4px 0; }
.toc a:hover { color: #2980b9; text-decoration: underline; }
.feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 15px 0; }
.feature-card { background: #f8f9fa; border-radius: 8px; padding: 15px; text-align: center; }
.feature-card i { font-size: 2rem; color: #8e44ad; margin-bottom: 8px; }
</style>

<div class="api-doc">

<div class="master-data-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <h1 style="margin:0;"><i class="fa fa-book"></i> iACC Sales Channel API</h1>
    <div>
        <a href="index.php?page=api_dashboard" class="btn btn-sm btn-outline-primary"><i class="fa fa-arrow-left"></i> Dashboard</a>
        <a href="index.php?page=api_keys" class="btn btn-sm btn-outline-primary"><i class="fa fa-key"></i> API Keys</a>
    </div>
</div>

<p style="color:#666; font-size:1.05rem; line-height:1.6;">
    The iACC Sales Channel API allows you to receive bookings from any channel — websites, LINE, Facebook, email — 
    and automatically sync them to your iACC account as Purchase Requisitions and Purchase Orders.
</p>

<!-- Features -->
<div class="feature-grid">
    <div class="feature-card">
        <i class="fa fa-bolt"></i>
        <h5>Auto-Processing</h5>
        <small>Bookings auto-create Customer, PR & PO</small>
    </div>
    <div class="feature-card">
        <i class="fa fa-bell"></i>
        <h5>Webhooks</h5>
        <small>Real-time notifications on events</small>
    </div>
    <div class="feature-card">
        <i class="fa fa-shield"></i>
        <h5>Rate Limiting</h5>
        <small>Plan-based request limits</small>
    </div>
    <div class="feature-card">
        <i class="fa fa-refresh"></i>
        <h5>Key Rotation</h5>
        <small>Zero-downtime credential rotation</small>
    </div>
</div>

<!-- TOC -->
<div class="toc">
    <h4 style="margin-top:0;">Table of Contents</h4>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0 20px;">
        <a href="#auth">1. Authentication</a>
        <a href="#errors">2. Error Handling</a>
        <a href="#rate-limits">3. Rate Limits</a>
        <a href="#idempotency">4. Idempotency</a>
        <a href="#create-booking">5. Create Booking</a>
        <a href="#list-bookings">6. List Bookings</a>
        <a href="#get-booking">7. Get Booking</a>
        <a href="#update-booking">8. Update Booking</a>
        <a href="#cancel-booking">9. Cancel Booking</a>
        <a href="#retry-booking">10. Retry Booking</a>
        <a href="#subscription">11. Subscription Info</a>
        <a href="#webhooks">12. Webhooks</a>
    </div>
</div>

<!-- Authentication -->
<h2 id="auth"><i class="fa fa-lock"></i> 1. Authentication</h2>
<p>All API requests require two headers:</p>
<div class="code-block">
<span class="comment"># Every request must include these headers:</span>
X-API-Key: iACC_your_api_key_here
X-API-Secret: your_api_secret_here
</div>
<p>Get your credentials from <a href="index.php?page=api_keys">API Keys</a> page. Keep your secret secure — treat it like a password.</p>

<!-- Errors -->
<h2 id="errors"><i class="fa fa-exclamation-triangle"></i> 2. Error Handling</h2>
<p>All errors return a consistent JSON structure:</p>
<div class="code-block">
{
    <span class="key">"success"</span>: false,
    <span class="key">"error"</span>: {
        <span class="key">"code"</span>: <span class="string">"ERROR_CODE"</span>,
        <span class="key">"message"</span>: <span class="string">"Human-readable description"</span>,
        <span class="key">"details"</span>: [<span class="string">"field-level errors..."</span>]
    }
}
</div>
<table class="param-table">
    <thead><tr><th>HTTP Status</th><th>Code</th><th>Description</th></tr></thead>
    <tbody>
        <tr><td>400</td><td><code>INVALID_JSON</code></td><td>Request body is not valid JSON</td></tr>
        <tr><td>401</td><td><code>AUTH_MISSING</code></td><td>Missing X-API-Key or X-API-Secret header</td></tr>
        <tr><td>401</td><td><code>AUTH_INVALID</code></td><td>Invalid API credentials</td></tr>
        <tr><td>403</td><td><code>SUBSCRIPTION_INACTIVE</code></td><td>API subscription disabled or inactive</td></tr>
        <tr><td>403</td><td><code>SUBSCRIPTION_EXPIRED</code></td><td>Trial or subscription has expired</td></tr>
        <tr><td>403</td><td><code>CHANNEL_NOT_ALLOWED</code></td><td>Channel not available on your plan</td></tr>
        <tr><td>404</td><td><code>NOT_FOUND</code></td><td>Resource not found</td></tr>
        <tr><td>409</td><td><code>INVALID_STATUS</code></td><td>Operation not allowed in current status</td></tr>
        <tr><td>422</td><td><code>VALIDATION_ERROR</code></td><td>Input validation failed (see details)</td></tr>
        <tr><td>429</td><td><code>RATE_LIMIT_EXCEEDED</code></td><td>Too many requests — check Retry-After header</td></tr>
        <tr><td>429</td><td><code>QUOTA_EXCEEDED</code></td><td>Monthly booking quota exceeded</td></tr>
    </tbody>
</table>

<!-- Rate Limits -->
<h2 id="rate-limits"><i class="fa fa-tachometer"></i> 3. Rate Limits</h2>
<p>Rate limits are per API key, per minute. Check response headers:</p>
<div class="code-block">
X-RateLimit-Limit: 30         <span class="comment"># Max requests per minute for your plan</span>
X-RateLimit-Remaining: 27     <span class="comment"># Requests remaining in current window</span>
Retry-After: 60                <span class="comment"># Seconds to wait (only on 429 response)</span>
</div>
<table class="param-table">
    <thead><tr><th>Plan</th><th>Requests/min</th><th>Bookings/mo</th><th>API Keys</th></tr></thead>
    <tbody>
        <tr><td>Trial</td><td>30</td><td>50</td><td>1</td></tr>
        <tr><td>Starter</td><td>60</td><td>500</td><td>3</td></tr>
        <tr><td>Professional</td><td>120</td><td>5,000</td><td>10</td></tr>
        <tr><td>Enterprise</td><td>300</td><td>Unlimited</td><td>Unlimited</td></tr>
    </tbody>
</table>

<!-- Idempotency -->
<h2 id="idempotency"><i class="fa fa-copy"></i> 4. Idempotency</h2>
<p>Prevent duplicate bookings by sending a unique <code>X-Idempotency-Key</code> header with POST requests. 
   If the same key is sent again within 24 hours, the original booking is returned instead of creating a duplicate.</p>
<div class="code-block">
curl -X POST http://localhost/api.php/v1/bookings \
  -H <span class="string">"X-API-Key: your_key"</span> \
  -H <span class="string">"X-API-Secret: your_secret"</span> \
  -H <span class="string">"X-Idempotency-Key: unique-request-id-123"</span> \
  -H <span class="string">"Content-Type: application/json"</span> \
  -d <span class="string">'{"guest_name": "John Doe", ...}'</span>
</div>

<!-- Create Booking -->
<h2 id="create-booking">5. Endpoints</h2>

<div class="endpoint post">
    <h3 style="margin-top:0;">
        <span class="method-badge method-post">POST</span>
        <span class="api-path">/api.php/v1/bookings</span>
    </h3>
    <p>Create a new booking. Auto-creates Customer → PR → PO → Products in iACC.</p>
    
    <h5>Request Body</h5>
    <table class="param-table">
        <thead><tr><th>Field</th><th>Type</th><th></th><th>Description</th></tr></thead>
        <tbody>
            <tr><td><code>guest_name</code></td><td>string</td><td><span class="required">REQUIRED</span></td><td>Guest's full name</td></tr>
            <tr><td><code>guest_email</code></td><td>string</td><td><span class="optional">optional</span></td><td>Guest's email address</td></tr>
            <tr><td><code>guest_phone</code></td><td>string</td><td><span class="optional">optional</span></td><td>Guest's phone number</td></tr>
            <tr><td><code>check_in</code></td><td>date</td><td><span class="optional">optional</span></td><td>Check-in date (YYYY-MM-DD)</td></tr>
            <tr><td><code>check_out</code></td><td>date</td><td><span class="optional">optional</span></td><td>Check-out date (YYYY-MM-DD)</td></tr>
            <tr><td><code>room_type</code></td><td>string</td><td><span class="optional">optional</span></td><td>Room type (e.g. "Deluxe", "Suite")</td></tr>
            <tr><td><code>guests</code></td><td>integer</td><td><span class="optional">optional</span></td><td>Number of guests (default: 1)</td></tr>
            <tr><td><code>total_amount</code></td><td>number</td><td><span class="optional">optional</span></td><td>Total price (default: 0)</td></tr>
            <tr><td><code>currency</code></td><td>string</td><td><span class="optional">optional</span></td><td>Currency code (default: "THB")</td></tr>
            <tr><td><code>channel</code></td><td>string</td><td><span class="optional">optional</span></td><td>Source channel: website, email, line, facebook, manual</td></tr>
            <tr><td><code>notes</code></td><td>string</td><td><span class="optional">optional</span></td><td>Additional notes</td></tr>
        </tbody>
    </table>

    <h5>Example</h5>
    <div class="code-block">
curl -X POST http://localhost/api.php/v1/bookings \
  -H <span class="string">"X-API-Key: iACC_abc123..."</span> \
  -H <span class="string">"X-API-Secret: def456..."</span> \
  -H <span class="string">"Content-Type: application/json"</span> \
  -d '{
    <span class="key">"guest_name"</span>: <span class="string">"John Doe"</span>,
    <span class="key">"guest_email"</span>: <span class="string">"john@example.com"</span>,
    <span class="key">"check_in"</span>: <span class="string">"2026-04-01"</span>,
    <span class="key">"check_out"</span>: <span class="string">"2026-04-03"</span>,
    <span class="key">"room_type"</span>: <span class="string">"Deluxe"</span>,
    <span class="key">"total_amount"</span>: 5000,
    <span class="key">"channel"</span>: <span class="string">"website"</span>
  }'
    </div>

    <h5>Response (201)</h5>
    <div class="code-block">
{
  <span class="key">"success"</span>: true,
  <span class="key">"message"</span>: <span class="string">"Booking created and processed successfully"</span>,
  <span class="key">"data"</span>: {
    <span class="key">"booking_id"</span>: 1,
    <span class="key">"customer_id"</span>: 176,
    <span class="key">"pr_id"</span>: 1110,
    <span class="key">"po_id"</span>: 2024,
    <span class="key">"status"</span>: <span class="string">"completed"</span>
  }
}
    </div>
</div>

<!-- List Bookings -->
<div class="endpoint" id="list-bookings">
    <h3 style="margin-top:0;">
        <span class="method-badge method-get">GET</span>
        <span class="api-path">/api.php/v1/bookings</span>
    </h3>
    <p>List bookings with filters and pagination.</p>
    
    <h5>Query Parameters</h5>
    <table class="param-table">
        <thead><tr><th>Param</th><th>Type</th><th>Description</th></tr></thead>
        <tbody>
            <tr><td><code>status</code></td><td>string</td><td>Filter: pending, processing, completed, failed, cancelled</td></tr>
            <tr><td><code>channel</code></td><td>string</td><td>Filter: website, email, line, facebook, manual</td></tr>
            <tr><td><code>date_from</code></td><td>date</td><td>Filter bookings created on or after (YYYY-MM-DD)</td></tr>
            <tr><td><code>date_to</code></td><td>date</td><td>Filter bookings created on or before (YYYY-MM-DD)</td></tr>
            <tr><td><code>search</code></td><td>string</td><td>Search in guest name, email, phone</td></tr>
            <tr><td><code>page</code></td><td>integer</td><td>Page number (default: 1)</td></tr>
            <tr><td><code>per_page</code></td><td>integer</td><td>Results per page (default: 15, max: 100)</td></tr>
        </tbody>
    </table>
</div>

<!-- Get Booking -->
<div class="endpoint" id="get-booking">
    <h3 style="margin-top:0;">
        <span class="method-badge method-get">GET</span>
        <span class="api-path">/api.php/v1/bookings/{id}</span>
    </h3>
    <p>Get a single booking by ID. Only returns bookings owned by your company.</p>
</div>

<!-- Update Booking -->
<div class="endpoint put" id="update-booking">
    <h3 style="margin-top:0;">
        <span class="method-badge method-put">PUT</span>
        <span class="api-path">/api.php/v1/bookings/{id}</span>
    </h3>
    <p>Update a booking. Only <code>pending</code> or <code>processing</code> bookings can be updated.</p>
    
    <h5>Updatable Fields</h5>
    <p><code>guest_name</code>, <code>guest_email</code>, <code>guest_phone</code>, <code>check_in</code>, <code>check_out</code>, <code>room_type</code>, <code>guests</code>, <code>total_amount</code>, <code>currency</code>, <code>notes</code></p>
</div>

<!-- Cancel -->
<div class="endpoint delete" id="cancel-booking">
    <h3 style="margin-top:0;">
        <span class="method-badge method-delete">DELETE</span>
        <span class="api-path">/api.php/v1/bookings/{id}</span>
    </h3>
    <p>Cancel a booking. Cannot cancel already-cancelled bookings.</p>
</div>

<!-- Retry -->
<div class="endpoint post" id="retry-booking">
    <h3 style="margin-top:0;">
        <span class="method-badge method-post">POST</span>
        <span class="api-path">/api.php/v1/bookings/{id}/retry</span>
    </h3>
    <p>Retry processing a <code>failed</code> booking. Resets status to pending and re-runs the full processing pipeline.</p>
</div>

<!-- Subscription -->
<div class="endpoint" id="subscription">
    <h3 style="margin-top:0;">
        <span class="method-badge method-get">GET</span>
        <span class="api-path">/api.php/v1/subscription</span>
    </h3>
    <p>Get your subscription info, quota usage, and booking statistics.</p>
    <h5>Response (200)</h5>
    <div class="code-block">
{
  <span class="key">"success"</span>: true,
  <span class="key">"data"</span>: {
    <span class="key">"plan"</span>: <span class="string">"trial"</span>,
    <span class="key">"status"</span>: <span class="string">"active"</span>,
    <span class="key">"bookings_limit"</span>: 50,
    <span class="key">"bookings_used"</span>: 3,
    <span class="key">"bookings_remaining"</span>: 47,
    <span class="key">"channels"</span>: [<span class="string">"website"</span>],
    <span class="key">"trial_end"</span>: <span class="string">"2026-04-10"</span>
  }
}
    </div>
</div>

<!-- Webhooks -->
<h2 id="webhooks"><i class="fa fa-bell"></i> 12. Webhooks</h2>
<p>Webhooks send real-time HTTP POST notifications to your server when booking events occur.</p>

<h4>Events</h4>
<table class="param-table">
    <thead><tr><th>Event</th><th>Triggered When</th></tr></thead>
    <tbody>
        <tr><td><code>booking.completed</code></td><td>Booking successfully processed (PR + PO created)</td></tr>
        <tr><td><code>booking.failed</code></td><td>Booking processing failed</td></tr>
        <tr><td><code>booking.cancelled</code></td><td>Booking cancelled via DELETE endpoint</td></tr>
        <tr><td><code>booking.updated</code></td><td>Booking fields updated via PUT endpoint</td></tr>
    </tbody>
</table>

<div class="endpoint post">
    <h3 style="margin-top:0;">
        <span class="method-badge method-post">POST</span>
        <span class="api-path">/api.php/v1/webhooks</span>
    </h3>
    <p>Register a webhook endpoint. Maximum 5 webhooks per company.</p>
    <div class="code-block">
curl -X POST http://localhost/api.php/v1/webhooks \
  -H <span class="string">"X-API-Key: your_key"</span> \
  -H <span class="string">"X-API-Secret: your_secret"</span> \
  -H <span class="string">"Content-Type: application/json"</span> \
  -d '{
    <span class="key">"url"</span>: <span class="string">"https://example.com/webhook"</span>,
    <span class="key">"events"</span>: [<span class="string">"booking.completed"</span>, <span class="string">"booking.cancelled"</span>]
  }'
    </div>
    <p><strong>Note:</strong> The response includes a <code>secret</code> — save it! Use it to verify webhook signatures (HMAC-SHA256).</p>
</div>

<div class="endpoint">
    <h3 style="margin-top:0;">
        <span class="method-badge method-get">GET</span>
        <span class="api-path">/api.php/v1/webhooks</span>
    </h3>
    <p>List all registered webhooks.</p>
</div>

<div class="endpoint delete">
    <h3 style="margin-top:0;">
        <span class="method-badge method-delete">DELETE</span>
        <span class="api-path">/api.php/v1/webhooks/{id}</span>
    </h3>
    <p>Delete a webhook endpoint.</p>
</div>

<h4>Webhook Payload</h4>
<p>Each delivery is a POST request with JSON body and signature headers:</p>
<div class="code-block">
<span class="comment"># Headers</span>
Content-Type: application/json
X-Webhook-Event: booking.completed
X-Webhook-Signature: sha256=abc123...
X-Webhook-Id: 1
User-Agent: iACC-Webhook/1.0

<span class="comment"># Body</span>
{
  <span class="key">"event"</span>: <span class="string">"booking.completed"</span>,
  <span class="key">"timestamp"</span>: <span class="string">"2026-03-27T10:30:00+07:00"</span>,
  <span class="key">"data"</span>: {
    <span class="key">"booking_id"</span>: 1,
    <span class="key">"customer_id"</span>: 176,
    <span class="key">"pr_id"</span>: 1110,
    <span class="key">"po_id"</span>: 2024,
    <span class="key">"status"</span>: <span class="string">"completed"</span>
  }
}
</div>

<h4>Signature Verification</h4>
<p>Verify the <code>X-Webhook-Signature</code> header using HMAC-SHA256 with your webhook secret:</p>
<div class="code-block">
<span class="comment">// PHP</span>
$payload = file_get_contents('php://input');
$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
$valid = hash_equals($expected, $_SERVER['HTTP_X_WEBHOOK_SIGNATURE']);

<span class="comment"># Python</span>
import hmac, hashlib
expected = 'sha256=' + hmac.new(secret.encode(), payload, hashlib.sha256).hexdigest()
valid = hmac.compare_digest(expected, request.headers['X-Webhook-Signature'])

<span class="comment">// Node.js</span>
const crypto = require('crypto');
const expected = 'sha256=' + crypto.createHmac('sha256', secret).update(payload).digest('hex');
const valid = crypto.timingSafeEqual(Buffer.from(expected), Buffer.from(signature));
</div>

<p style="margin-top:30px; color:#999; text-align:center; font-size:0.85rem;">
    <i class="fa fa-code"></i> iACC Sales Channel API v1 — 
    <a href="index.php?page=api_dashboard">Dashboard</a> · 
    <a href="index.php?page=api_keys">API Keys</a> · 
    <a href="index.php?page=api_webhooks">Webhooks</a>
</p>

</div>
