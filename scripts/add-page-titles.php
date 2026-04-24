<?php
/**
 * Script: Add $pageTitle to all module views
 * Run: php scripts/add-page-titles.php
 */

// Module name mapping (folder → readable name)
$moduleNames = [
    'tour-booking'    => 'Tour Bookings',
    'tour-agent'      => 'Tour Agents',
    'tour-location'   => 'Tour Locations',
    'tour-report'     => 'Tour Reports',
    'payment-method'  => 'Payment Methods',
    'payment-gateway' => 'Payment Gateway',
    'payment'         => 'Payments',
    'quick-create'    => 'Quick Create',
    'line-oa'         => 'LINE OA',
    'module-manager'  => 'Module Manager',
    'slip-review'     => 'Slip Review',
    'billing'         => 'Billing',
    'receipt'         => 'Receipts',
    'invoice'         => 'Invoices',
    'voucher'         => 'Vouchers',
    'delivery'        => 'Delivery',
    'expense'         => 'Expenses',
    'journal'         => 'Journal',
    'report'          => 'Reports',
    'tax'             => 'Tax',
    'po'              => 'Purchase Orders',
    'pr'              => 'Purchase Requests',
    'audit'           => 'Audit Log',
    'dashboard'       => 'Dashboard',
    'user'            => 'Users',
    'company'         => 'Companies',
    'brand'           => 'Brands',
    'category'        => 'Categories',
    'type'            => 'Types',
    'model'           => 'Models',
    'currency'        => 'Currency',
    'account'         => 'Account',
    'api'             => 'API',
    'ai'              => 'AI Assistant',
    'devtools'        => 'Dev Tools',
    'help'            => 'Help',
    'export'          => 'Export',
];

// File name mapping (filename without .php → suffix)
$fileTitles = [
    'list'           => '',           // "Tour Bookings"
    'index'          => '',           // "Dashboard"
    'dashboard'      => 'Dashboard',
    'make'           => 'New',        // "New Tour Booking"
    'form'           => 'New',
    'create'         => 'New',
    'view'           => 'Details',
    'edit'           => 'Edit',
    'print'          => 'Print',
    'payments'       => 'Payments',
    'contracts'      => 'Contracts',
    'contract-make'  => 'New Contract',
    'settings'       => 'Settings',
    'profile'        => 'Profile',
    'rates'          => 'Exchange Rates',
    'summary'        => 'Summary',
    'categories'     => 'Categories',
    'report'         => 'Report',
    'hub'            => 'Reports',
    'accounts'       => 'Chart of Accounts',
    'trial-balance'  => 'Trial Balance',
    'ar-aging'       => 'AR Aging',
    'pp30'           => 'PP30 Report',
    'wht'            => 'WHT Report',
    'config'         => 'Configuration',
    'promptpay'      => 'PromptPay',
    'messages'       => 'Messages',
    'auto-replies'   => 'Auto Replies',
    'webhook-log'    => 'Webhook Log',
    'send-message'   => 'Send Message',
    'users'          => 'Users',
    'orders'         => 'Orders',
    'order-detail'   => 'Order Details',
    'subscriptions'  => 'Subscriptions',
    'keys'           => 'API Keys',
    'webhooks'       => 'Webhooks',
    'webhook-deliveries' => 'Webhook Deliveries',
    'docs'           => 'Documentation',
    'usage-logs'     => 'Usage Logs',
    'upgrade'        => 'Upgrade Plan',
    'invoices'       => 'Invoices',
    'tax-list'       => 'Tax Invoices',
    'quotations'     => 'Quotations',
    'project-report' => 'Project Report',
    'action-log'     => 'Action Log',
    'chat-history'   => 'Chat History',
    'documentation'  => 'Documentation',
    'schema-browser' => 'Schema Browser',
    'schema-refresh' => 'Schema Refresh',
    'denied'         => 'Access Denied',
    'credits'        => 'Credits',
    'checkin-print'  => 'Check-in Sheet',
    'pickup-print'   => 'Pickup Sheet',
    'dev-summary'    => 'Dev Summary',
    'user-manual'    => 'User Manual',
    'master-data-guide' => 'Master Data Guide',
    'monitoring'     => 'Monitoring',
    'roadmap'        => 'Roadmap',
    'invoice'        => 'New Invoice',
    'quotation'      => 'New Quotation',
    'tax-invoice'    => 'New Tax Invoice',
    'delivery'       => 'Delivery',
];

// Files to skip (print/pdf pages that have their own <title>, or partials)
$skip = ['print', '_nav', '_products'];

$viewsDir = __DIR__ . '/../app/Views';
$updated  = 0;
$skipped  = 0;
$already  = 0;

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;

    $path     = $file->getPathname();
    $folder   = basename(dirname($path));
    $filename = $file->getBasename('.php');

    // Skip partials, layouts, auth, pdf, ajax, standalone payment pages
    if (in_array($folder, ['partials', 'layouts', 'auth', 'pdf', 'ajax', 'invoice-payment', 'booking-pay'])) {
        $skipped++;
        continue;
    }

    // Skip nav partials and print pages that have standalone <title>
    if (in_array($filename, $skip)) {
        $skipped++;
        continue;
    }

    $content = file_get_contents($path);

    // Skip if $pageTitle already set
    if (strpos($content, '$pageTitle') !== false) {
        $already++;
        continue;
    }

    // Build title
    $module = $moduleNames[$folder] ?? ucwords(str_replace('-', ' ', $folder));
    $suffix = $fileTitles[$filename] ?? ucwords(str_replace('-', ' ', $filename));

    $title = $suffix === '' ? $module : "$module — $suffix";

    // Inject after opening <?php tag or at top
    if (strpos($content, '<?php') === 0) {
        $content = "<?php\n\$pageTitle = " . var_export($title, true) . ";\n" . substr($content, 5);
    } elseif (strpos($content, "<?php\n") === 0) {
        $content = "<?php\n\$pageTitle = " . var_export($title, true) . ";\n" . substr($content, 6);
    } else {
        // No opening PHP tag — prepend PHP block
        $content = "<?php \$pageTitle = " . var_export($title, true) . "; ?>\n" . $content;
    }

    file_put_contents($path, $content);
    echo "✅ $folder/$filename.php  →  \"$title\"\n";
    $updated++;
}

echo "\n--- Done ---\n";
echo "Updated : $updated\n";
echo "Already : $already\n";
echo "Skipped : $skipped\n";
