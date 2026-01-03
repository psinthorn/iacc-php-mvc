<?php
/**
 * Master Data Guide
 * Documentation for Category, Brand, Product Type, and Model management
 */
require_once("inc/security.php");

$page_title = "Master Data Guide";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$page_title?> - iAcc</title>
    <?php include 'css.php'; ?>
    <link rel="stylesheet" href="css/master-data.css">
    <style>
        .guide-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            margin-top: 80px;
        }
        .guide-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        .guide-header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
        }
        .guide-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1em;
        }
        .guide-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .guide-section h2 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
            margin-top: 0;
        }
        .guide-section h3 {
            color: #555;
            margin-top: 25px;
        }
        .hierarchy-diagram {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
        }
        .example-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .example-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        .example-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        .example-table tr:hover {
            background: #f8f9fa;
        }
        .step-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 15px 0;
            border-radius: 0 8px 8px 0;
        }
        .step-box h4 {
            margin: 0 0 10px 0;
            color: #667eea;
        }
        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            margin-right: 10px;
        }
        .tip-box {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px 20px;
            margin: 15px 0;
            border-radius: 0 8px 8px 0;
        }
        .tip-box strong {
            color: #2e7d32;
        }
        .warning-box {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px 20px;
            margin: 15px 0;
            border-radius: 0 8px 8px 0;
        }
        .warning-box strong {
            color: #e65100;
        }
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .quick-link {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background: white;
            border: 2px solid #667eea;
            border-radius: 10px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        .quick-link:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102,126,234,0.3);
        }
        .quick-link i {
            margin-right: 10px;
            font-size: 1.2em;
        }
        .icon-category { color: #9c27b0; }
        .icon-brand { color: #2196f3; }
        .icon-product { color: #4caf50; }
        .icon-model { color: #ff9800; }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            border-radius: 20px;
            text-decoration: none;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .back-btn:hover {
            background: #545b62;
            color: white;
            text-decoration: none;
        }
        .industry-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .industry-tab {
            padding: 10px 20px;
            background: #e9ecef;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        .industry-tab.active {
            background: #667eea;
            color: white;
        }
        .industry-tab:hover:not(.active) {
            background: #dee2e6;
        }
        .industry-content {
            display: none;
        }
        .industry-content.active {
            display: block;
        }
    </style>
</head>
<body>
<?php include 'menu.php'; ?>

<div class="guide-container">
    <a href="javascript:history.back()" class="back-btn">
        <i class="fa fa-arrow-left"></i> Back to Master Data
    </a>

    <div class="guide-header">
        <h1><i class="fa fa-book"></i> Master Data Guide</h1>
        <p>Learn how to organize your Categories, Brands, Products, and Models</p>
    </div>

    <!-- Quick Links -->
    <div class="guide-section">
        <h2><i class="fa fa-link"></i> Quick Links</h2>
        <div class="quick-links">
            <a href="category-list.php?page=category" class="quick-link">
                <i class="fa fa-folder icon-category"></i> Manage Categories
            </a>
            <a href="brand-list.php?page=brand" class="quick-link">
                <i class="fa fa-certificate icon-brand"></i> Manage Brands
            </a>
            <a href="type-list.php?page=type" class="quick-link">
                <i class="fa fa-cube icon-product"></i> Manage Products
            </a>
            <a href="mo-list.php?page=mo_list" class="quick-link">
                <i class="fa fa-barcode icon-model"></i> Manage Models
            </a>
        </div>
    </div>

    <!-- Understanding the Hierarchy -->
    <div class="guide-section">
        <h2><i class="fa fa-sitemap"></i> Understanding the Hierarchy</h2>
        <p>Master data follows a hierarchical structure. Each level depends on the one above it:</p>
        
        <div class="hierarchy-diagram">
<pre>
┌─────────────────────────────────────────────────────────────────────────────┐
│                           YOUR COMPANY (Owner)                               │
│                    All master data belongs to your company                   │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  <span class="icon-category">■</span> CATEGORY - High-level grouping (e.g., Tour Packages, Transportation)    │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  <span class="icon-brand">■</span> BRAND - Supplier/Partner who provides services (linked to vendors)       │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  <span class="icon-product">■</span> PRODUCT TYPE - Specific service type within a category                   │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  <span class="icon-model">■</span> MODEL - Specific package/variant with pricing                             │
└─────────────────────────────────────────────────────────────────────────────┘
</pre>
        </div>
    </div>

    <!-- Industry Examples -->
    <div class="guide-section">
        <h2><i class="fa fa-industry"></i> Industry Examples</h2>
        <p>Select your industry to see relevant examples:</p>
        
        <div class="industry-tabs">
            <button class="industry-tab active" onclick="showIndustry('travel')">
                <i class="fa fa-plane"></i> Travel Agency
            </button>
            <button class="industry-tab" onclick="showIndustry('electronics')">
                <i class="fa fa-laptop"></i> Electronics
            </button>
            <button class="industry-tab" onclick="showIndustry('retail')">
                <i class="fa fa-shopping-bag"></i> Retail/Fashion
            </button>
            <button class="industry-tab" onclick="showIndustry('food')">
                <i class="fa fa-cutlery"></i> Food & Beverage
            </button>
        </div>

        <!-- Travel Agency Example -->
        <div id="travel" class="industry-content active">
            <h3><i class="fa fa-plane"></i> Travel Agency Example</h3>
            
            <table class="example-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Brand (Supplier)</th>
                        <th>Product Type</th>
                        <th>Model (Package)</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Tour Packages</td>
                        <td><i class="fa fa-certificate icon-brand"></i> Thailand Tourism</td>
                        <td><i class="fa fa-cube icon-product"></i> Beach Holiday</td>
                        <td><i class="fa fa-barcode icon-model"></i> Phuket 4D3N Premium</td>
                        <td>$599</td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Tour Packages</td>
                        <td><i class="fa fa-certificate icon-brand"></i> Thailand Tourism</td>
                        <td><i class="fa fa-cube icon-product"></i> Beach Holiday</td>
                        <td><i class="fa fa-barcode icon-model"></i> Phuket 4D3N Budget</td>
                        <td>$399</td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Tour Packages</td>
                        <td><i class="fa fa-certificate icon-brand"></i> Japan Travel Co.</td>
                        <td><i class="fa fa-cube icon-product"></i> Cultural Tour</td>
                        <td><i class="fa fa-barcode icon-model"></i> Kyoto 5D4N Heritage</td>
                        <td>$1,299</td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Transportation</td>
                        <td><i class="fa fa-certificate icon-brand"></i> Singapore Airlines</td>
                        <td><i class="fa fa-cube icon-product"></i> Flight Ticket</td>
                        <td><i class="fa fa-barcode icon-model"></i> SIN-BKK Economy</td>
                        <td>$180</td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Transportation</td>
                        <td><i class="fa fa-certificate icon-brand"></i> Singapore Airlines</td>
                        <td><i class="fa fa-cube icon-product"></i> Flight Ticket</td>
                        <td><i class="fa fa-barcode icon-model"></i> SIN-BKK Business</td>
                        <td>$650</td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Accommodation</td>
                        <td><i class="fa fa-certificate icon-brand"></i> Marriott Hotels</td>
                        <td><i class="fa fa-cube icon-product"></i> Hotel Booking</td>
                        <td><i class="fa fa-barcode icon-model"></i> Bangkok Marriott Deluxe</td>
                        <td>$150/night</td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Accommodation</td>
                        <td><i class="fa fa-certificate icon-brand"></i> Airbnb Partners</td>
                        <td><i class="fa fa-cube icon-product"></i> Vacation Rental</td>
                        <td><i class="fa fa-barcode icon-model"></i> Bali Villa 3BR</td>
                        <td>$200/night</td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Activities</td>
                        <td><i class="fa fa-certificate icon-brand"></i> Klook</td>
                        <td><i class="fa fa-cube icon-product"></i> Day Tour</td>
                        <td><i class="fa fa-barcode icon-model"></i> Universal Studios SG</td>
                        <td>$79</td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Insurance</td>
                        <td><i class="fa fa-certificate icon-brand"></i> AXA Insurance</td>
                        <td><i class="fa fa-cube icon-product"></i> Travel Insurance</td>
                        <td><i class="fa fa-barcode icon-model"></i> Asia 7-Day Plan</td>
                        <td>$25</td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Visa Services</td>
                        <td><i class="fa fa-certificate icon-brand"></i> VisaHQ</td>
                        <td><i class="fa fa-cube icon-product"></i> Visa Application</td>
                        <td><i class="fa fa-barcode icon-model"></i> Japan Tourist Visa</td>
                        <td>$85</td>
                    </tr>
                </tbody>
            </table>

            <div class="tip-box">
                <strong><i class="fa fa-lightbulb-o"></i> Tip for Travel Agencies:</strong><br>
                Use Brands to represent your suppliers/partners (airlines, hotels, tour operators). 
                This helps you track which supplier provides each service and manage vendor relationships.
            </div>
        </div>

        <!-- Electronics Example -->
        <div id="electronics" class="industry-content">
            <h3><i class="fa fa-laptop"></i> Electronics Store Example</h3>
            
            <table class="example-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Product Type</th>
                        <th>Model</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Computers</td>
                        <td><i class="fa fa-certificate icon-brand"></i> Apple</td>
                        <td><i class="fa fa-cube icon-product"></i> Laptop</td>
                        <td><i class="fa fa-barcode icon-model"></i> MacBook Pro 14" M3</td>
                        <td>$1,999</td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Computers</td>
                        <td><i class="fa fa-certificate icon-brand"></i> Apple</td>
                        <td><i class="fa fa-cube icon-product"></i> Laptop</td>
                        <td><i class="fa fa-barcode icon-model"></i> MacBook Air 13" M2</td>
                        <td>$1,099</td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Mobile Devices</td>
                        <td><i class="fa fa-certificate icon-brand"></i> Samsung</td>
                        <td><i class="fa fa-cube icon-product"></i> Smartphone</td>
                        <td><i class="fa fa-barcode icon-model"></i> Galaxy S24 Ultra</td>
                        <td>$1,199</td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Accessories</td>
                        <td><i class="fa fa-certificate icon-brand"></i> Logitech</td>
                        <td><i class="fa fa-cube icon-product"></i> Mouse</td>
                        <td><i class="fa fa-barcode icon-model"></i> MX Master 3S</td>
                        <td>$99</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Retail Example -->
        <div id="retail" class="industry-content">
            <h3><i class="fa fa-shopping-bag"></i> Retail/Fashion Example</h3>
            
            <table class="example-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Product Type</th>
                        <th>Model</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Men's Wear</td>
                        <td><i class="fa fa-certificate icon-brand"></i> Nike</td>
                        <td><i class="fa fa-cube icon-product"></i> Running Shoes</td>
                        <td><i class="fa fa-barcode icon-model"></i> Air Max 90 Black</td>
                        <td>$130</td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Women's Wear</td>
                        <td><i class="fa fa-certificate icon-brand"></i> Zara</td>
                        <td><i class="fa fa-cube icon-product"></i> Dress</td>
                        <td><i class="fa fa-barcode icon-model"></i> Summer Floral Midi</td>
                        <td>$59</td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Accessories</td>
                        <td><i class="fa fa-certificate icon-brand"></i> Ray-Ban</td>
                        <td><i class="fa fa-cube icon-product"></i> Sunglasses</td>
                        <td><i class="fa fa-barcode icon-model"></i> Aviator Classic Gold</td>
                        <td>$154</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Food Example -->
        <div id="food" class="industry-content">
            <h3><i class="fa fa-cutlery"></i> Food & Beverage Example</h3>
            
            <table class="example-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Brand (Supplier)</th>
                        <th>Product Type</th>
                        <th>Model (Item)</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Beverages</td>
                        <td><i class="fa fa-certificate icon-brand"></i> Coca-Cola</td>
                        <td><i class="fa fa-cube icon-product"></i> Soft Drinks</td>
                        <td><i class="fa fa-barcode icon-model"></i> Coke Original 330ml</td>
                        <td>$1.50</td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Snacks</td>
                        <td><i class="fa fa-certificate icon-brand"></i> Lay's</td>
                        <td><i class="fa fa-cube icon-product"></i> Chips</td>
                        <td><i class="fa fa-barcode icon-model"></i> Classic Salted 150g</td>
                        <td>$3.99</td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-folder icon-category"></i> Dairy</td>
                        <td><i class="fa fa-certificate icon-brand"></i> Meiji</td>
                        <td><i class="fa fa-cube icon-product"></i> Fresh Milk</td>
                        <td><i class="fa fa-barcode icon-model"></i> Full Cream 1L</td>
                        <td>$2.80</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Step-by-Step Setup -->
    <div class="guide-section">
        <h2><i class="fa fa-tasks"></i> Step-by-Step Setup for Travel Agency</h2>
        
        <div class="step-box">
            <h4><span class="step-number">1</span> Create Categories</h4>
            <p>Start by creating your main service categories:</p>
            <ul>
                <li><strong>Tour Packages</strong> - Bundled travel experiences</li>
                <li><strong>Transportation</strong> - Flights, buses, trains, car rentals</li>
                <li><strong>Accommodation</strong> - Hotels, resorts, vacation rentals</li>
                <li><strong>Activities</strong> - Day tours, attractions, experiences</li>
                <li><strong>Insurance</strong> - Travel insurance products</li>
                <li><strong>Visa Services</strong> - Visa processing and documentation</li>
            </ul>
            <p><a href="category-list.php?page=category" class="btn btn-sm btn-primary">
                <i class="fa fa-plus"></i> Add Categories
            </a></p>
        </div>

        <div class="step-box">
            <h4><span class="step-number">2</span> Add Your Suppliers as Brands</h4>
            <p>Register your partners and suppliers as brands. Make sure to:</p>
            <ul>
                <li>First add the supplier as a <strong>Vendor</strong> in Company List</li>
                <li>Then create a Brand and link it to that vendor</li>
                <li>Upload the supplier's logo for easy identification</li>
            </ul>
            <p>Example suppliers: Thai Airways, Marriott Hotels, Klook, AXA Insurance</p>
            <p>
                <a href="company-list.php?page=company&type=vendor" class="btn btn-sm btn-info">
                    <i class="fa fa-building"></i> Add Vendors First
                </a>
                <a href="brand-list.php?page=brand" class="btn btn-sm btn-primary">
                    <i class="fa fa-plus"></i> Add Brands
                </a>
            </p>
        </div>

        <div class="step-box">
            <h4><span class="step-number">3</span> Define Product Types</h4>
            <p>Create specific product types under each category:</p>
            <ul>
                <li><strong>Under Tour Packages:</strong> Beach Holiday, Cultural Tour, Adventure Trip, Honeymoon Package</li>
                <li><strong>Under Transportation:</strong> Flight Ticket, Bus Ticket, Train Ticket, Car Rental</li>
                <li><strong>Under Accommodation:</strong> Hotel Booking, Resort Stay, Vacation Rental, Hostel</li>
            </ul>
            <p><a href="type-list.php?page=type" class="btn btn-sm btn-primary">
                <i class="fa fa-plus"></i> Add Product Types
            </a></p>
        </div>

        <div class="step-box">
            <h4><span class="step-number">4</span> Create Models (Your Actual Products)</h4>
            <p>Finally, create specific packages/items with pricing:</p>
            <ul>
                <li>Select the Product Type (e.g., Beach Holiday)</li>
                <li>Select the Brand/Supplier (e.g., Thailand Tourism)</li>
                <li>Enter model name (e.g., "Phuket 4D3N Premium Package")</li>
                <li>Set the price</li>
            </ul>
            <p><a href="mo-list.php?page=mo_list" class="btn btn-sm btn-primary">
                <i class="fa fa-plus"></i> Add Models
            </a></p>
        </div>
    </div>

    <!-- How It Works in Invoicing -->
    <div class="guide-section">
        <h2><i class="fa fa-file-text-o"></i> Using Master Data in Invoicing</h2>
        <p>When creating an invoice or quotation, the master data hierarchy makes selection easy:</p>
        
        <div class="hierarchy-diagram">
<pre>
Customer requests: "I want a beach holiday in Thailand"

Your workflow in the system:
┌────────────────────────────────────────────────────────────────┐
│ 1. Select Product Type: <strong>Beach Holiday</strong>                         │
│    ↓                                                           │
│ 2. Select Brand: <strong>Thailand Tourism</strong> (filtered list)             │
│    ↓                                                           │
│ 3. Select Model: <strong>Phuket 4D3N Premium</strong> (filtered list)          │
│    ↓                                                           │
│ 4. Price auto-fills: <strong>$599</strong>                                     │
│    ↓                                                           │
│ 5. Adjust quantity, add more items, generate invoice           │
└────────────────────────────────────────────────────────────────┘
</pre>
        </div>

        <div class="tip-box">
            <strong><i class="fa fa-lightbulb-o"></i> Benefits:</strong>
            <ul style="margin-bottom:0;">
                <li><strong>Faster quotations</strong> - No need to type prices manually</li>
                <li><strong>Consistent pricing</strong> - All staff use the same prices</li>
                <li><strong>Better reporting</strong> - Track sales by category, brand, or product</li>
                <li><strong>Supplier analysis</strong> - See which suppliers generate most revenue</li>
            </ul>
        </div>
    </div>

    <!-- Best Practices -->
    <div class="guide-section">
        <h2><i class="fa fa-star"></i> Best Practices</h2>
        
        <div class="warning-box">
            <strong><i class="fa fa-exclamation-triangle"></i> Important:</strong>
            Always set up data in order: Category → Brand → Product Type → Model. 
            You cannot create a Model without first having a Product Type and Brand.
        </div>

        <h3>Naming Conventions</h3>
        <ul>
            <li><strong>Categories:</strong> Use broad terms (Tour Packages, not "Beach Tours")</li>
            <li><strong>Brands:</strong> Use official supplier names (Singapore Airlines, not "SQ")</li>
            <li><strong>Product Types:</strong> Be specific but not too narrow (Flight Ticket, not "Economy Flight")</li>
            <li><strong>Models:</strong> Include key details (Phuket 4D3N Premium, SIN-BKK Economy Class)</li>
        </ul>

        <h3>Keeping Data Clean</h3>
        <ul>
            <li>Review and archive unused models regularly</li>
            <li>Update prices when suppliers change their rates</li>
            <li>Use consistent capitalization (Title Case recommended)</li>
            <li>Add logos to brands for visual recognition</li>
        </ul>
    </div>

    <!-- FAQ -->
    <div class="guide-section">
        <h2><i class="fa fa-question-circle"></i> Frequently Asked Questions</h2>
        
        <h3>Q: Can I have multiple brands under one product type?</h3>
        <p>Yes! For example, under "Flight Ticket" product type, you can have models from Singapore Airlines, Thai Airways, AirAsia, etc.</p>

        <h3>Q: What if my supplier provides multiple types of services?</h3>
        <p>Create one brand for the supplier, then create multiple models under different product types. For example, "Marriott Hotels" brand can have models under both "Hotel Booking" and "Resort Stay" product types.</p>

        <h3>Q: How do I handle seasonal pricing?</h3>
        <p>You can either update the model price seasonally, or create separate models for peak/off-peak (e.g., "Phuket 4D3N Peak Season" vs "Phuket 4D3N Off-Peak").</p>

        <h3>Q: Can customers see this master data?</h3>
        <p>No, master data is internal. Customers only see the final invoice/quotation with selected items.</p>
    </div>

    <!-- Footer -->
    <div style="text-align:center; padding:30px; color:#888;">
        <p><i class="fa fa-book"></i> Master Data Guide v1.0 | Last updated: <?=date('F Y')?></p>
    </div>
</div>

<script>
function showIndustry(industry) {
    // Hide all content
    document.querySelectorAll('.industry-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.industry-tab').forEach(el => el.classList.remove('active'));
    
    // Show selected
    document.getElementById(industry).classList.add('active');
    event.target.classList.add('active');
}
</script>

<?php include 'script.php'; ?>
</body>
</html>
