<?php
/**
 * Master Data Guide
 * Documentation for Category, Brand, Product Type, and Model management
 */

$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$t = [
    'en' => [
        'page_title' => 'Master Data Guide',
        'back_btn' => 'Back to Master Data',
        'header_subtitle' => 'Learn how to organize your Categories, Brands, Products, and Models',
        'quick_links' => 'Quick Links',
        'manage_categories' => 'Manage Categories',
        'manage_brands' => 'Manage Brands',
        'manage_products' => 'Manage Products',
        'manage_models' => 'Manage Models',
        'understanding_hierarchy' => 'Understanding the Hierarchy',
        'hierarchy_desc' => 'Master data follows a hierarchical structure. Each level depends on the one above it:',
        'hierarchy_company' => 'YOUR COMPANY (Owner)',
        'hierarchy_company_desc' => 'All master data belongs to your company',
        'hierarchy_category' => 'CATEGORY - High-level grouping (e.g., Tour Packages, Transportation)',
        'hierarchy_brand' => 'BRAND - Supplier/Partner who provides services (linked to vendors)',
        'hierarchy_product' => 'PRODUCT TYPE - Specific service type within a category',
        'hierarchy_model' => 'MODEL - Specific package/variant with pricing',
        'industry_examples' => 'Industry Examples',
        'industry_desc' => 'Select your industry to see relevant examples:',
        'tab_travel' => 'Travel Agency',
        'tab_electronics' => 'Electronics',
        'tab_retail' => 'Retail/Fashion',
        'tab_food' => 'Food & Beverage',
        'th_category' => 'Category',
        'th_brand' => 'Brand',
        'th_brand_supplier' => 'Brand (Supplier)',
        'th_product_type' => 'Product Type',
        'th_model' => 'Model',
        'th_model_package' => 'Model (Package)',
        'th_model_item' => 'Model (Item)',
        'th_price' => 'Price',
        'travel_title' => 'Travel Agency Example',
        'travel_tip' => 'Tip for Travel Agencies:',
        'travel_tip_text' => 'Use Brands to represent your suppliers/partners (airlines, hotels, tour operators). This helps you track which supplier provides each service and manage vendor relationships.',
        'electronics_title' => 'Electronics Store Example',
        'retail_title' => 'Retail/Fashion Example',
        'food_title' => 'Food & Beverage Example',
        'step_title' => 'Step-by-Step Setup for Travel Agency',
        'step1_title' => 'Create Categories',
        'step1_desc' => 'Start by creating your main service categories:',
        'step1_tour' => 'Tour Packages',
        'step1_tour_desc' => 'Bundled travel experiences',
        'step1_transport' => 'Transportation',
        'step1_transport_desc' => 'Flights, buses, trains, car rentals',
        'step1_accom' => 'Accommodation',
        'step1_accom_desc' => 'Hotels, resorts, vacation rentals',
        'step1_activities' => 'Activities',
        'step1_activities_desc' => 'Day tours, attractions, experiences',
        'step1_insurance' => 'Insurance',
        'step1_insurance_desc' => 'Travel insurance products',
        'step1_visa' => 'Visa Services',
        'step1_visa_desc' => 'Visa processing and documentation',
        'btn_add_categories' => 'Add Categories',
        'step2_title' => 'Add Your Suppliers as Brands',
        'step2_desc' => 'Register your partners and suppliers as brands. Make sure to:',
        'step2_vendor' => 'First add the supplier as a <strong>Vendor</strong> in Company List',
        'step2_brand' => 'Then create a Brand and link it to that vendor',
        'step2_logo' => "Upload the supplier's logo for easy identification",
        'step2_examples' => 'Example suppliers: Thai Airways, Marriott Hotels, Klook, AXA Insurance',
        'btn_add_vendors' => 'Add Vendors First',
        'btn_add_brands' => 'Add Brands',
        'step3_title' => 'Define Product Types',
        'step3_desc' => 'Create specific product types under each category:',
        'step3_tour' => 'Under Tour Packages:',
        'step3_tour_items' => 'Beach Holiday, Cultural Tour, Adventure Trip, Honeymoon Package',
        'step3_transport' => 'Under Transportation:',
        'step3_transport_items' => 'Flight Ticket, Bus Ticket, Train Ticket, Car Rental',
        'step3_accom' => 'Under Accommodation:',
        'step3_accom_items' => 'Hotel Booking, Resort Stay, Vacation Rental, Hostel',
        'btn_add_types' => 'Add Product Types',
        'step4_title' => 'Create Models (Your Actual Products)',
        'step4_desc' => 'Finally, create specific packages/items with pricing:',
        'step4_select_type' => 'Select the Product Type (e.g., Beach Holiday)',
        'step4_select_brand' => 'Select the Brand/Supplier (e.g., Thailand Tourism)',
        'step4_enter_name' => 'Enter model name (e.g., "Phuket 4D3N Premium Package")',
        'step4_set_price' => 'Set the price',
        'btn_add_models' => 'Add Models',
        'invoicing_title' => 'Using Master Data in Invoicing',
        'invoicing_desc' => 'When creating an invoice or quotation, the master data hierarchy makes selection easy:',
        'invoicing_request' => 'Customer requests: "I want a beach holiday in Thailand"',
        'invoicing_workflow' => 'Your workflow in the system:',
        'invoicing_step1' => '1. Select Product Type:',
        'invoicing_step2' => '2. Select Brand:',
        'invoicing_step2_note' => '(filtered list)',
        'invoicing_step3' => '3. Select Model:',
        'invoicing_step4' => '4. Price auto-fills:',
        'invoicing_step5' => '5. Adjust quantity, add more items, generate invoice',
        'benefits_title' => 'Benefits:',
        'benefit_faster' => 'Faster quotations',
        'benefit_faster_desc' => 'No need to type prices manually',
        'benefit_consistent' => 'Consistent pricing',
        'benefit_consistent_desc' => 'All staff use the same prices',
        'benefit_reporting' => 'Better reporting',
        'benefit_reporting_desc' => 'Track sales by category, brand, or product',
        'benefit_supplier' => 'Supplier analysis',
        'benefit_supplier_desc' => 'See which suppliers generate most revenue',
        'best_practices' => 'Best Practices',
        'warning_important' => 'Important:',
        'warning_text' => 'Always set up data in order: Category → Brand → Product Type → Model. You cannot create a Model without first having a Product Type and Brand.',
        'naming_conventions' => 'Naming Conventions',
        'naming_cat' => 'Use broad terms (Tour Packages, not "Beach Tours")',
        'naming_brand' => 'Use official supplier names (Singapore Airlines, not "SQ")',
        'naming_type' => 'Be specific but not too narrow (Flight Ticket, not "Economy Flight")',
        'naming_model' => 'Include key details (Phuket 4D3N Premium, SIN-BKK Economy Class)',
        'keeping_clean' => 'Keeping Data Clean',
        'clean_archive' => 'Review and archive unused models regularly',
        'clean_prices' => 'Update prices when suppliers change their rates',
        'clean_caps' => 'Use consistent capitalization (Title Case recommended)',
        'clean_logos' => 'Add logos to brands for visual recognition',
        'faq_title' => 'Frequently Asked Questions',
        'faq1_q' => 'Q: Can I have multiple brands under one product type?',
        'faq1_a' => 'Yes! For example, under "Flight Ticket" product type, you can have models from Singapore Airlines, Thai Airways, AirAsia, etc.',
        'faq2_q' => 'Q: What if my supplier provides multiple types of services?',
        'faq2_a' => 'Create one brand for the supplier, then create multiple models under different product types. For example, "Marriott Hotels" brand can have models under both "Hotel Booking" and "Resort Stay" product types.',
        'faq3_q' => 'Q: How do I handle seasonal pricing?',
        'faq3_a' => 'You can either update the model price seasonally, or create separate models for peak/off-peak (e.g., "Phuket 4D3N Peak Season" vs "Phuket 4D3N Off-Peak").',
        'faq4_q' => 'Q: Can customers see this master data?',
        'faq4_a' => 'No, master data is internal. Customers only see the final invoice/quotation with selected items.',
        'footer_text' => 'Master Data Guide v1.0 | Last updated:',
    ],
    'th' => [
        'page_title' => 'คู่มือข้อมูลหลัก',
        'back_btn' => 'กลับไปข้อมูลหลัก',
        'header_subtitle' => 'เรียนรู้วิธีจัดการ หมวดหมู่ แบรนด์ ประเภทสินค้า และรุ่น',
        'quick_links' => 'ลิงก์ด่วน',
        'manage_categories' => 'จัดการหมวดหมู่',
        'manage_brands' => 'จัดการแบรนด์',
        'manage_products' => 'จัดการประเภทสินค้า',
        'manage_models' => 'จัดการรุ่น',
        'understanding_hierarchy' => 'ทำความเข้าใจลำดับชั้น',
        'hierarchy_desc' => 'ข้อมูลหลักมีโครงสร้างแบบลำดับชั้น แต่ละระดับขึ้นอยู่กับระดับที่อยู่เหนือ:',
        'hierarchy_company' => 'บริษัทของคุณ (เจ้าของ)',
        'hierarchy_company_desc' => 'ข้อมูลหลักทั้งหมดเป็นของบริษัทของคุณ',
        'hierarchy_category' => 'หมวดหมู่ - การจัดกลุ่มระดับสูง (เช่น แพ็คเกจทัวร์, การขนส่ง)',
        'hierarchy_brand' => 'แบรนด์ - ซัพพลายเออร์/พาร์ทเนอร์ที่ให้บริการ (เชื่อมกับผู้ขาย)',
        'hierarchy_product' => 'ประเภทสินค้า - ประเภทบริการเฉพาะภายในหมวดหมู่',
        'hierarchy_model' => 'รุ่น - แพ็คเกจ/รูปแบบเฉพาะพร้อมราคา',
        'industry_examples' => 'ตัวอย่างตามอุตสาหกรรม',
        'industry_desc' => 'เลือกอุตสาหกรรมของคุณเพื่อดูตัวอย่างที่เกี่ยวข้อง:',
        'tab_travel' => 'ตัวแทนท่องเที่ยว',
        'tab_electronics' => 'อิเล็กทรอนิกส์',
        'tab_retail' => 'ค้าปลีก/แฟชั่น',
        'tab_food' => 'อาหารและเครื่องดื่ม',
        'th_category' => 'หมวดหมู่',
        'th_brand' => 'แบรนด์',
        'th_brand_supplier' => 'แบรนด์ (ซัพพลายเออร์)',
        'th_product_type' => 'ประเภทสินค้า',
        'th_model' => 'รุ่น',
        'th_model_package' => 'รุ่น (แพ็คเกจ)',
        'th_model_item' => 'รุ่น (รายการ)',
        'th_price' => 'ราคา',
        'travel_title' => 'ตัวอย่างตัวแทนท่องเที่ยว',
        'travel_tip' => 'เคล็ดลับสำหรับตัวแทนท่องเที่ยว:',
        'travel_tip_text' => 'ใช้แบรนด์เพื่อแทนซัพพลายเออร์/พาร์ทเนอร์ (สายการบิน โรงแรม ผู้จัดทัวร์) ซึ่งช่วยให้คุณติดตามว่าซัพพลายเออร์ใดให้บริการแต่ละอย่างและจัดการความสัมพันธ์กับผู้ขาย',
        'electronics_title' => 'ตัวอย่างร้านอิเล็กทรอนิกส์',
        'retail_title' => 'ตัวอย่างค้าปลีก/แฟชั่น',
        'food_title' => 'ตัวอย่างอาหารและเครื่องดื่ม',
        'step_title' => 'ขั้นตอนการตั้งค่าสำหรับตัวแทนท่องเที่ยว',
        'step1_title' => 'สร้างหมวดหมู่',
        'step1_desc' => 'เริ่มต้นด้วยการสร้างหมวดหมู่บริการหลัก:',
        'step1_tour' => 'แพ็คเกจทัวร์',
        'step1_tour_desc' => 'ประสบการณ์การท่องเที่ยวแบบรวม',
        'step1_transport' => 'การขนส่ง',
        'step1_transport_desc' => 'เที่ยวบิน รถบัส รถไฟ รถเช่า',
        'step1_accom' => 'ที่พัก',
        'step1_accom_desc' => 'โรงแรม รีสอร์ท บ้านพักตากอากาศ',
        'step1_activities' => 'กิจกรรม',
        'step1_activities_desc' => 'ทัวร์วันเดียว สถานที่ท่องเที่ยว กิจกรรม',
        'step1_insurance' => 'ประกันภัย',
        'step1_insurance_desc' => 'ผลิตภัณฑ์ประกันการเดินทาง',
        'step1_visa' => 'บริการวีซ่า',
        'step1_visa_desc' => 'การดำเนินการและเอกสารวีซ่า',
        'btn_add_categories' => 'เพิ่มหมวดหมู่',
        'step2_title' => 'เพิ่มซัพพลายเออร์เป็นแบรนด์',
        'step2_desc' => 'ลงทะเบียนพาร์ทเนอร์และซัพพลายเออร์เป็นแบรนด์ ต้องแน่ใจว่า:',
        'step2_vendor' => 'เพิ่มซัพพลายเออร์เป็น <strong>ผู้ขาย</strong> ในรายชื่อบริษัทก่อน',
        'step2_brand' => 'จากนั้นสร้างแบรนด์และเชื่อมกับผู้ขายนั้น',
        'step2_logo' => 'อัปโหลดโลโก้ซัพพลายเออร์เพื่อการระบุง่าย',
        'step2_examples' => 'ตัวอย่างซัพพลายเออร์: Thai Airways, Marriott Hotels, Klook, AXA Insurance',
        'btn_add_vendors' => 'เพิ่มผู้ขายก่อน',
        'btn_add_brands' => 'เพิ่มแบรนด์',
        'step3_title' => 'กำหนดประเภทสินค้า',
        'step3_desc' => 'สร้างประเภทสินค้าเฉพาะภายใต้แต่ละหมวดหมู่:',
        'step3_tour' => 'ภายใต้แพ็คเกจทัวร์:',
        'step3_tour_items' => 'วันหยุดชายหาด, ทัวร์วัฒนธรรม, ทัวร์ผจญภัย, แพ็คเกจฮันนีมูน',
        'step3_transport' => 'ภายใต้การขนส่ง:',
        'step3_transport_items' => 'ตั๋วเครื่องบิน, ตั๋วรถบัส, ตั๋วรถไฟ, รถเช่า',
        'step3_accom' => 'ภายใต้ที่พัก:',
        'step3_accom_items' => 'จองโรงแรม, พักรีสอร์ท, บ้านพักตากอากาศ, โฮสเทล',
        'btn_add_types' => 'เพิ่มประเภทสินค้า',
        'step4_title' => 'สร้างรุ่น (สินค้าจริงของคุณ)',
        'step4_desc' => 'สุดท้าย สร้างแพ็คเกจ/รายการเฉพาะพร้อมราคา:',
        'step4_select_type' => 'เลือกประเภทสินค้า (เช่น วันหยุดชายหาด)',
        'step4_select_brand' => 'เลือกแบรนด์/ซัพพลายเออร์ (เช่น Thailand Tourism)',
        'step4_enter_name' => 'กรอกชื่อรุ่น (เช่น "Phuket 4D3N Premium Package")',
        'step4_set_price' => 'กำหนดราคา',
        'btn_add_models' => 'เพิ่มรุ่น',
        'invoicing_title' => 'การใช้ข้อมูลหลักในการออกใบแจ้งหนี้',
        'invoicing_desc' => 'เมื่อสร้างใบแจ้งหนี้หรือใบเสนอราคา ลำดับชั้นข้อมูลหลักช่วยให้เลือกได้ง่าย:',
        'invoicing_request' => 'ลูกค้าต้องการ: "ฉันต้องการวันหยุดชายหาดในประเทศไทย"',
        'invoicing_workflow' => 'ขั้นตอนการทำงานในระบบ:',
        'invoicing_step1' => '1. เลือกประเภทสินค้า:',
        'invoicing_step2' => '2. เลือกแบรนด์:',
        'invoicing_step2_note' => '(รายการที่กรอง)',
        'invoicing_step3' => '3. เลือกรุ่น:',
        'invoicing_step4' => '4. ราคาเติมอัตโนมัติ:',
        'invoicing_step5' => '5. ปรับจำนวน เพิ่มรายการ สร้างใบแจ้งหนี้',
        'benefits_title' => 'ข้อดี:',
        'benefit_faster' => 'ใบเสนอราคาเร็วขึ้น',
        'benefit_faster_desc' => 'ไม่ต้องพิมพ์ราคาด้วยตนเอง',
        'benefit_consistent' => 'ราคาสม่ำเสมอ',
        'benefit_consistent_desc' => 'พนักงานทุกคนใช้ราคาเดียวกัน',
        'benefit_reporting' => 'รายงานที่ดีขึ้น',
        'benefit_reporting_desc' => 'ติดตามยอดขายตามหมวดหมู่ แบรนด์ หรือสินค้า',
        'benefit_supplier' => 'วิเคราะห์ซัพพลายเออร์',
        'benefit_supplier_desc' => 'ดูว่าซัพพลายเออร์ใดสร้างรายได้สูงสุด',
        'best_practices' => 'แนวปฏิบัติที่ดี',
        'warning_important' => 'สำคัญ:',
        'warning_text' => 'ตั้งค่าข้อมูลตามลำดับเสมอ: หมวดหมู่ → แบรนด์ → ประเภทสินค้า → รุ่น คุณไม่สามารถสร้างรุ่นได้โดยไม่มีประเภทสินค้าและแบรนด์ก่อน',
        'naming_conventions' => 'หลักการตั้งชื่อ',
        'naming_cat' => 'ใช้คำกว้างๆ (แพ็คเกจทัวร์ ไม่ใช่ "ทัวร์ชายหาด")',
        'naming_brand' => 'ใช้ชื่อซัพพลายเออร์อย่างเป็นทางการ (Singapore Airlines ไม่ใช่ "SQ")',
        'naming_type' => 'เจาะจงแต่ไม่แคบเกินไป (ตั๋วเครื่องบิน ไม่ใช่ "เที่ยวบินชั้นประหยัด")',
        'naming_model' => 'ใส่รายละเอียดสำคัญ (Phuket 4D3N Premium, SIN-BKK Economy Class)',
        'keeping_clean' => 'การรักษาข้อมูลให้สะอาด',
        'clean_archive' => 'ตรวจสอบและเก็บถาวรรุ่นที่ไม่ใช้เป็นประจำ',
        'clean_prices' => 'อัปเดตราคาเมื่อซัพพลายเออร์เปลี่ยนอัตรา',
        'clean_caps' => 'ใช้ตัวพิมพ์ใหญ่ที่สม่ำเสมอ (แนะนำ Title Case)',
        'clean_logos' => 'เพิ่มโลโก้ให้แบรนด์เพื่อการจดจำ',
        'faq_title' => 'คำถามที่พบบ่อย',
        'faq1_q' => 'ถาม: สามารถมีหลายแบรนด์ภายใต้ประเภทสินค้าเดียวได้หรือไม่?',
        'faq1_a' => 'ได้! เช่น ภายใต้ประเภท "ตั๋วเครื่องบิน" คุณสามารถมีรุ่นจาก Singapore Airlines, Thai Airways, AirAsia ฯลฯ',
        'faq2_q' => 'ถาม: จะทำอย่างไรถ้าซัพพลายเออร์ให้บริการหลายประเภท?',
        'faq2_a' => 'สร้างแบรนด์เดียวสำหรับซัพพลายเออร์ จากนั้นสร้างหลายรุ่นภายใต้ประเภทสินค้าต่างๆ เช่น แบรนด์ "Marriott Hotels" สามารถมีรุ่นภายใต้ทั้ง "จองโรงแรม" และ "พักรีสอร์ท"',
        'faq3_q' => 'ถาม: จัดการราคาตามฤดูกาลอย่างไร?',
        'faq3_a' => 'คุณสามารถอัปเดตราคารุ่นตามฤดูกาล หรือสร้างรุ่นแยกสำหรับ high/low season (เช่น "Phuket 4D3N High Season" vs "Phuket 4D3N Low Season")',
        'faq4_q' => 'ถาม: ลูกค้าสามารถเห็นข้อมูลหลักนี้ได้หรือไม่?',
        'faq4_a' => 'ไม่ ข้อมูลหลักเป็นข้อมูลภายใน ลูกค้าจะเห็นเฉพาะใบแจ้งหนี้/ใบเสนอราคาสุดท้ายพร้อมรายการที่เลือก',
        'footer_text' => 'คู่มือข้อมูลหลัก v1.0 | อัปเดตล่าสุด:',
    ],
][$lang];

$page_title = $t['page_title'];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$page_title?> - iAcc</title>
    <?php include __DIR__ . '/../layouts/head.php'; ?>
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
<?php include __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="guide-container">
    <a href="javascript:history.back()" class="back-btn">
        <i class="fa fa-arrow-left"></i> <?= $t['back_btn'] ?>
    </a>

    <div class="guide-header">
        <h1><i class="fa fa-book"></i> <?= $t['page_title'] ?></h1>
        <p><?= $t['header_subtitle'] ?></p>
    </div>

    <!-- Quick Links -->
    <div class="guide-section">
        <h2><i class="fa fa-link"></i> <?= $t['quick_links'] ?></h2>
        <div class="quick-links">
            <a href="category-list.php?page=category" class="quick-link">
                <i class="fa fa-folder icon-category"></i> <?= $t['manage_categories'] ?>
            </a>
            <a href="brand-list.php?page=brand" class="quick-link">
                <i class="fa fa-certificate icon-brand"></i> <?= $t['manage_brands'] ?>
            </a>
            <a href="type-list.php?page=type" class="quick-link">
                <i class="fa fa-cube icon-product"></i> <?= $t['manage_products'] ?>
            </a>
            <a href="mo-list.php?page=mo_list" class="quick-link">
                <i class="fa fa-barcode icon-model"></i> <?= $t['manage_models'] ?>
            </a>
        </div>
    </div>

    <!-- Understanding the Hierarchy -->
    <div class="guide-section">
        <h2><i class="fa fa-sitemap"></i> <?= $t['understanding_hierarchy'] ?></h2>
        <p><?= $t['hierarchy_desc'] ?></p>
        
        <div class="hierarchy-diagram">
<pre>
┌─────────────────────────────────────────────────────────────────────────────┐
│                           <?= $t['hierarchy_company'] ?>                               │
│                    <?= $t['hierarchy_company_desc'] ?>                   │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  <span class="icon-category">■</span> <?= $t['hierarchy_category'] ?>    │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  <span class="icon-brand">■</span> <?= $t['hierarchy_brand'] ?>       │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  <span class="icon-product">■</span> <?= $t['hierarchy_product'] ?>                   │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  <span class="icon-model">■</span> <?= $t['hierarchy_model'] ?>                             │
└─────────────────────────────────────────────────────────────────────────────┘
</pre>
        </div>
    </div>

    <!-- Industry Examples -->
    <div class="guide-section">
        <h2><i class="fa fa-industry"></i> <?= $t['industry_examples'] ?></h2>
        <p><?= $t['industry_desc'] ?></p>
        
        <div class="industry-tabs">
            <button class="industry-tab active" onclick="showIndustry('travel')">
                <i class="fa fa-plane"></i> <?= $t['tab_travel'] ?>
            </button>
            <button class="industry-tab" onclick="showIndustry('electronics')">
                <i class="fa fa-laptop"></i> <?= $t['tab_electronics'] ?>
            </button>
            <button class="industry-tab" onclick="showIndustry('retail')">
                <i class="fa fa-shopping-bag"></i> <?= $t['tab_retail'] ?>
            </button>
            <button class="industry-tab" onclick="showIndustry('food')">
                <i class="fa fa-cutlery"></i> <?= $t['tab_food'] ?>
            </button>
        </div>

        <!-- Travel Agency Example -->
        <div id="travel" class="industry-content active">
            <h3><i class="fa fa-plane"></i> <?= $t['travel_title'] ?></h3>
            
            <table class="example-table">
                <thead>
                    <tr>
                        <th><?= $t['th_category'] ?></th>
                        <th><?= $t['th_brand_supplier'] ?></th>
                        <th><?= $t['th_product_type'] ?></th>
                        <th><?= $t['th_model_package'] ?></th>
                        <th><?= $t['th_price'] ?></th>
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
                <strong><i class="fa fa-lightbulb-o"></i> <?= $t['travel_tip'] ?></strong><br>
                <?= $t['travel_tip_text'] ?>
            </div>
        </div>

        <!-- Electronics Example -->
        <div id="electronics" class="industry-content">
            <h3><i class="fa fa-laptop"></i> <?= $t['electronics_title'] ?></h3>
            
            <table class="example-table">
                <thead>
                    <tr>
                        <th><?= $t['th_category'] ?></th>
                        <th><?= $t['th_brand'] ?></th>
                        <th><?= $t['th_product_type'] ?></th>
                        <th><?= $t['th_model'] ?></th>
                        <th><?= $t['th_price'] ?></th>
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
            <h3><i class="fa fa-shopping-bag"></i> <?= $t['retail_title'] ?></h3>
            
            <table class="example-table">
                <thead>
                    <tr>
                        <th><?= $t['th_category'] ?></th>
                        <th><?= $t['th_brand'] ?></th>
                        <th><?= $t['th_product_type'] ?></th>
                        <th><?= $t['th_model'] ?></th>
                        <th><?= $t['th_price'] ?></th>
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
            <h3><i class="fa fa-cutlery"></i> <?= $t['food_title'] ?></h3>
            
            <table class="example-table">
                <thead>
                    <tr>
                        <th><?= $t['th_category'] ?></th>
                        <th><?= $t['th_brand_supplier'] ?></th>
                        <th><?= $t['th_product_type'] ?></th>
                        <th><?= $t['th_model_item'] ?></th>
                        <th><?= $t['th_price'] ?></th>
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
        <h2><i class="fa fa-tasks"></i> <?= $t['step_title'] ?></h2>
        
        <div class="step-box">
            <h4><span class="step-number">1</span> <?= $t['step1_title'] ?></h4>
            <p><?= $t['step1_desc'] ?></p>
            <ul>
                <li><strong><?= $t['step1_tour'] ?></strong> - <?= $t['step1_tour_desc'] ?></li>
                <li><strong><?= $t['step1_transport'] ?></strong> - <?= $t['step1_transport_desc'] ?></li>
                <li><strong><?= $t['step1_accom'] ?></strong> - <?= $t['step1_accom_desc'] ?></li>
                <li><strong><?= $t['step1_activities'] ?></strong> - <?= $t['step1_activities_desc'] ?></li>
                <li><strong><?= $t['step1_insurance'] ?></strong> - <?= $t['step1_insurance_desc'] ?></li>
                <li><strong><?= $t['step1_visa'] ?></strong> - <?= $t['step1_visa_desc'] ?></li>
            </ul>
            <p><a href="category-list.php?page=category" class="btn btn-sm btn-primary">
                <i class="fa fa-plus"></i> <?= $t['btn_add_categories'] ?>
            </a></p>
        </div>

        <div class="step-box">
            <h4><span class="step-number">2</span> <?= $t['step2_title'] ?></h4>
            <p><?= $t['step2_desc'] ?></p>
            <ul>
                <li><?= $t['step2_vendor'] ?></li>
                <li><?= $t['step2_brand'] ?></li>
                <li><?= $t['step2_logo'] ?></li>
            </ul>
            <p><?= $t['step2_examples'] ?></p>
            <p>
                <a href="company-list.php?page=company&type=vendor" class="btn btn-sm btn-info">
                    <i class="fa fa-building"></i> <?= $t['btn_add_vendors'] ?>
                </a>
                <a href="brand-list.php?page=brand" class="btn btn-sm btn-primary">
                    <i class="fa fa-plus"></i> <?= $t['btn_add_brands'] ?>
                </a>
            </p>
        </div>

        <div class="step-box">
            <h4><span class="step-number">3</span> <?= $t['step3_title'] ?></h4>
            <p><?= $t['step3_desc'] ?></p>
            <ul>
                <li><strong><?= $t['step3_tour'] ?></strong> <?= $t['step3_tour_items'] ?></li>
                <li><strong><?= $t['step3_transport'] ?></strong> <?= $t['step3_transport_items'] ?></li>
                <li><strong><?= $t['step3_accom'] ?></strong> <?= $t['step3_accom_items'] ?></li>
            </ul>
            <p><a href="type-list.php?page=type" class="btn btn-sm btn-primary">
                <i class="fa fa-plus"></i> <?= $t['btn_add_types'] ?>
            </a></p>
        </div>

        <div class="step-box">
            <h4><span class="step-number">4</span> <?= $t['step4_title'] ?></h4>
            <p><?= $t['step4_desc'] ?></p>
            <ul>
                <li><?= $t['step4_select_type'] ?></li>
                <li><?= $t['step4_select_brand'] ?></li>
                <li><?= $t['step4_enter_name'] ?></li>
                <li><?= $t['step4_set_price'] ?></li>
            </ul>
            <p><a href="mo-list.php?page=mo_list" class="btn btn-sm btn-primary">
                <i class="fa fa-plus"></i> <?= $t['btn_add_models'] ?>
            </a></p>
        </div>
    </div>

    <!-- How It Works in Invoicing -->
    <div class="guide-section">
        <h2><i class="fa fa-file-text-o"></i> <?= $t['invoicing_title'] ?></h2>
        <p><?= $t['invoicing_desc'] ?></p>
        
        <div class="hierarchy-diagram">
<pre>
<?= $t['invoicing_request'] ?>

<?= $t['invoicing_workflow'] ?>
┌────────────────────────────────────────────────────────────────┐
│ 1. Select Product Type: <strong>Beach Holiday</strong>                         │
│    ↓                                                           │
│ 2. Select Brand: <strong>Thailand Tourism</strong> (filtered list)             │
│    ↓                                                           │
│ 3. Select Model: <strong>Phuket 4D3N Premium</strong> (filtered list)          │
│    ↓                                                           │
│ 4. Price auto-fills: <strong>$599</strong>                                     │
│    ↓                                                           │
│ 5. <?= $t['invoicing_step5'] ?>           │
└────────────────────────────────────────────────────────────────┘
</pre>
        </div>

        <div class="tip-box">
            <strong><i class="fa fa-lightbulb-o"></i> <?= $t['benefits_title'] ?></strong>
            <ul style="margin-bottom:0;">
                <li><strong><?= $t['benefit_faster'] ?></strong> - <?= $t['benefit_faster_desc'] ?></li>
                <li><strong><?= $t['benefit_consistent'] ?></strong> - <?= $t['benefit_consistent_desc'] ?></li>
                <li><strong><?= $t['benefit_reporting'] ?></strong> - <?= $t['benefit_reporting_desc'] ?></li>
                <li><strong><?= $t['benefit_supplier'] ?></strong> - <?= $t['benefit_supplier_desc'] ?></li>
            </ul>
        </div>
    </div>

    <!-- Best Practices -->
    <div class="guide-section">
        <h2><i class="fa fa-star"></i> <?= $t['best_practices'] ?></h2>
        
        <div class="warning-box">
            <strong><i class="fa fa-exclamation-triangle"></i> <?= $t['warning_important'] ?></strong>
            <?= $t['warning_text'] ?>
        </div>

        <h3><?= $t['naming_conventions'] ?></h3>
        <ul>
            <li><strong><?= $t['th_category'] ?>:</strong> <?= $t['naming_cat'] ?></li>
            <li><strong><?= $t['th_brand'] ?>:</strong> <?= $t['naming_brand'] ?></li>
            <li><strong><?= $t['th_product_type'] ?>:</strong> <?= $t['naming_type'] ?></li>
            <li><strong><?= $t['th_model'] ?>:</strong> <?= $t['naming_model'] ?></li>
        </ul>

        <h3><?= $t['keeping_clean'] ?></h3>
        <ul>
            <li><?= $t['clean_archive'] ?></li>
            <li><?= $t['clean_prices'] ?></li>
            <li><?= $t['clean_caps'] ?></li>
            <li><?= $t['clean_logos'] ?></li>
        </ul>
    </div>

    <!-- FAQ -->
    <div class="guide-section">
        <h2><i class="fa fa-question-circle"></i> <?= $t['faq_title'] ?></h2>
        
        <h3><?= $t['faq1_q'] ?></h3>
        <p><?= $t['faq1_a'] ?></p>

        <h3><?= $t['faq2_q'] ?></h3>
        <p><?= $t['faq2_a'] ?></p>

        <h3><?= $t['faq3_q'] ?></h3>
        <p><?= $t['faq3_a'] ?></p>

        <h3><?= $t['faq4_q'] ?></h3>
        <p><?= $t['faq4_a'] ?></p>
    </div>

    <!-- Footer -->
    <div style="text-align:center; padding:30px; color:#888;">
        <p><i class="fa fa-book"></i> <?= $t['footer_text'] ?> <?=date('F Y')?></p>
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

<?php include __DIR__ . '/../layouts/scripts.php'; ?>
</body>
</html>
