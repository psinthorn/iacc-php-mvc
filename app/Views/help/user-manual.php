<?php
/**
 * User Manual — Complete Guide
 * Step-by-step guide from initial setup to daily operations
 * Bilingual: English / Thai
 */
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$t = [
    'en' => [
        'page_title'        => 'User Manual',
        'page_subtitle'     => 'Complete step-by-step guide to using iACC',
        'toc'               => 'Table of Contents',
        'back_help'         => 'Back to Help Center',
        'updated'           => 'Last Updated',
        'step'              => 'Step',
        'tip'               => 'Tip',
        'note'              => 'Note',
        'warning'           => 'Warning',
        'next'              => 'Next',
        'prev'              => 'Previous',
        'print'             => 'Print Guide',

        // Chapter 1
        'ch1_title'         => 'Getting Started',
        'ch1_1_title'       => 'Logging In',
        'ch1_1_desc'        => 'Open your browser and go to your iACC URL. Enter your email and password, then click <strong>Sign In</strong>. If you don\'t have an account, click <strong>Sign up free</strong> to register.',
        'ch1_2_title'       => 'Dashboard Overview',
        'ch1_2_desc'        => 'After login, you\'ll see the Dashboard with key metrics: total revenue, pending invoices, recent orders, and quick-action buttons. Use the sidebar on the left to navigate to any module.',
        'ch1_3_title'       => 'Switching Language',
        'ch1_3_desc'        => 'Click the <strong>English</strong> or <strong>ภาษาไทย</strong> button in the top navigation bar. The interface will reload in your chosen language. Your preference is saved automatically.',
        'ch1_4_title'       => 'Switching Company',
        'ch1_4_desc'        => 'Administrators can manage multiple companies. Go to <strong>Dashboard</strong> and select a company from the company list. Regular users are assigned to one company.',

        // Chapter 2
        'ch2_title'         => 'Initial Setup',
        'ch2_intro'         => 'Before creating transactions, you must set up your master data. Follow these steps in order:',
        'ch2_preloaded'     => '<strong><i class="fa fa-magic"></i> Good news!</strong> When you register, the system automatically creates starter data for you: <strong>10 expense categories</strong>, <strong>4 payment methods</strong> (Cash, Bank Transfer, Credit Card, Cheque), and <strong>20 chart of accounts</strong>. You can start using them right away or customize them to fit your business. See the <a href="index.php?page=master_data_guide">Master Data Guide</a> for the full list.',
        'ch2_1_title'       => 'Step 1: Create Your Company',
        'ch2_1_desc'        => 'Go to <strong>Master Data → Company</strong>. Click <strong>Add New</strong>. Fill in company name, address, tax ID, phone number, and branch. This is your business identity that appears on invoices, quotations, and receipts.',
        'ch2_1_fields'      => 'Required fields: Company Name, Address, Phone. Optional: Tax ID, Branch, Website, Logo.',
        'ch2_2_title'       => 'Step 2: Set Up Categories',
        'ch2_2_desc'        => 'Go to <strong>Master Data → Category</strong>. Categories are the highest level of product grouping. Examples: "Tour Packages", "Transportation", "Accommodation", "Electronics", "Food & Beverage".',
        'ch2_3_title'       => 'Step 3: Add Brands',
        'ch2_3_desc'        => 'Go to <strong>Master Data → Brand</strong>. Brands represent your suppliers or vendor partners. Each brand is linked to a category. Examples: "Singapore Airlines" under Transportation, "Marriott Hotels" under Accommodation.',
        'ch2_4_title'       => 'Step 4: Create Product Types',
        'ch2_4_desc'        => 'Go to <strong>Master Data → Product</strong>. Product types are specific items within a brand. Examples: "Flight Ticket", "Hotel Room", "Speedboat Tour". Each type belongs to a brand.',
        'ch2_5_title'       => 'Step 5: Add Models (Packages/SKUs)',
        'ch2_5_desc'        => 'Go to <strong>Master Data → Model</strong>. Models are the actual items you sell with pricing. Examples: "Phuket 4D3N Premium — ฿15,990", "Economy Flight SIN-BKK — ฿6,500". Each model belongs to a product type and has a price.',
        'ch2_hierarchy'     => 'Master Data Hierarchy',
        'ch2_hierarchy_desc'=> 'Category → Brand → Product Type → Model (with price). You must create them in this order.',
        'ch2_6_title'       => 'Step 6: Configure Payment Methods',
        'ch2_6_desc'        => 'Admin users: Go to <strong>Admin → Payment Methods</strong>. Set up the payment methods your business accepts: Bank Transfer, Cash, Credit Card, PromptPay, PayPal, etc.',
        'ch2_7_title'       => 'Step 7: Set Up Currency',
        'ch2_7_desc'        => 'Admin users: Go to <strong>Admin → Currency</strong>. The default currency is Thai Baht (THB). Add other currencies if you deal with international clients: USD, EUR, SGD, JPY, etc.',

        // Chapter 3
        'ch3_title'         => 'Creating Transactions',
        'ch3_intro'         => 'With master data in place, you can start creating business documents:',
        'ch3_1_title'       => 'Purchase Requisition (PR)',
        'ch3_1_desc'        => 'A PR is a request to buy or sell. Go to <strong>Purchase Request</strong> in the sidebar.',
        'ch3_1_steps'       => '<li>Click <strong>PR for Vendor</strong> (buying) or <strong>PR for Customer</strong> (selling)</li><li>Select the company/customer</li><li>Add products from your master data — select category, brand, type, model</li><li>Set quantity and confirm pricing</li><li>Click <strong>Save</strong> to create the PR</li>',
        'ch3_2_title'       => 'Quotation',
        'ch3_2_desc'        => 'A Quotation is a formal price offer to a customer. Go to <strong>Sales & Orders → Quotation</strong>.',
        'ch3_2_steps'       => '<li>Click <strong>Create</strong> to start a new quotation</li><li>Select customer company</li><li>Add line items with products, quantities, and prices</li><li>Set validity period and terms</li><li>Save and send to customer (PDF or email)</li>',
        'ch3_3_title'       => 'Purchase Order (PO)',
        'ch3_3_desc'        => 'A PO is a confirmed order. Go to <strong>Sales & Orders → Purchase Order</strong>. POs can be created from an approved PR or directly.',
        'ch3_3_steps'       => '<li>Click <strong>Create</strong> or convert from an existing PR</li><li>Add or adjust products and quantities</li><li>Set delivery date and payment terms</li><li>Save — the PO number is auto-generated</li><li>When you edit a PO, a new version is created automatically (version tracking)</li>',
        'ch3_4_title'       => 'Delivery Note',
        'ch3_4_desc'        => 'Track shipments linked to POs. Go to <strong>Sales & Orders → Delivery Note</strong>.',
        'ch3_4_steps'       => '<li>Click <strong>Create</strong> and link to a PO</li><li>Enter delivery details: date, carrier, tracking number</li><li>Mark items as delivered</li><li>Print delivery note PDF for the customer</li>',

        // Chapter 4
        'ch4_title'         => 'Billing & Payments',
        'ch4_1_title'       => 'Creating Invoices',
        'ch4_1_desc'        => 'Go to <strong>Billing & Invoices → Invoice</strong>. Create invoices from POs or standalone.',
        'ch4_1_steps'       => '<li>Click <strong>Create Invoice</strong></li><li>Select PO or enter items manually</li><li>Review totals, tax (VAT/WHT), and discounts</li><li>Save — invoice number is auto-generated</li><li>Print PDF or email to customer</li>',
        'ch4_2_title'       => 'Tax Invoice',
        'ch4_2_desc'        => 'For Thai tax compliance. Go to <strong>Billing & Invoices → Tax Invoice</strong>. Same process as regular invoice but generates a Thai tax-compliant document with tax ID and branch information.',
        'ch4_3_title'       => 'Recording Payments (Voucher & Receipt)',
        'ch4_3_desc'        => 'When money goes <strong>OUT</strong> (paying a vendor), create a <strong>Voucher</strong>. When money comes <strong>IN</strong> (customer pays you), create a <strong>Receipt</strong>.',
        'ch4_3_steps'       => '<li>Go to <strong>Payments → Voucher</strong> (out) or <strong>Receipt</strong> (in)</li><li>Select the invoice being paid</li><li>Enter payment amount and method (bank, cash, etc.)</li><li>Attach payment proof (slip upload) if applicable</li><li>Save — the invoice status updates automatically</li>',
        'ch4_4_title'       => 'Payment Tracking',
        'ch4_4_desc'        => 'Go to <strong>Payments → Payment Tracking</strong> to see all invoices and their payment status: Paid, Partial, or Overdue. You can filter by date range and company.',
        'ch4_5_title'       => 'Slip Review',
        'ch4_5_desc'        => 'When customers submit payment slips, go to <strong>Payments → Slip Review</strong> to verify and approve. This helps prevent fraud and ensures payment accuracy.',

        // Chapter 5
        'ch5_title'         => 'Expenses & Accounting',
        'ch5_1_title'       => 'Recording Expenses',
        'ch5_1_desc'        => 'Go to <strong>Expenses → New Expense</strong>. Record daily business expenses with category, amount, vendor, date, and optional project assignment.',
        'ch5_1_steps'       => '<li>Click <strong>New Expense</strong></li><li>Select expense category (create categories first under <strong>Expenses → Categories</strong>)</li><li>Enter amount, date, description, and vendor</li><li>Optionally link to a project for cost tracking</li><li>Save — expense is recorded and reflected in reports</li>',
        'ch5_2_title'       => 'Journal Entries',
        'ch5_2_desc'        => 'For double-entry bookkeeping. Go to <strong>Accounting → New Journal Entry</strong>. Select accounts from the Chart of Accounts, enter debit and credit amounts. The system validates that debits equal credits.',
        'ch5_3_title'       => 'Chart of Accounts',
        'ch5_3_desc'        => 'Go to <strong>Accounting → Chart of Accounts</strong> to view and manage your account structure. The default chart is auto-initialized when you first access it.',
        'ch5_4_title'       => 'Trial Balance',
        'ch5_4_desc'        => 'Go to <strong>Accounting → Trial Balance</strong> to verify that all debits equal all credits. This is essential for accurate financial reporting.',

        // Chapter 6
        'ch6_title'         => 'Reports & Analytics',
        'ch6_1_title'       => 'Reports Center',
        'ch6_1_desc'        => 'Go to <strong>Reports → Reports Center</strong> for a dashboard of all available reports. Quick filters: Today, This Week, This Month, This Year, All Time.',
        'ch6_2_title'       => 'Business Summary',
        'ch6_2_desc'        => 'Go to <strong>Reports → Business Summary</strong> for an overview of revenue, expenses, profit/loss by period.',
        'ch6_3_title'       => 'AR Aging Report',
        'ch6_3_desc'        => 'Go to <strong>Reports → AR Aging</strong> to analyze unpaid invoices by age: Current, 30 days, 60 days, 90+ days overdue.',
        'ch6_4_title'       => 'Tax Reports',
        'ch6_4_desc'        => 'Admin users: Go to <strong>Admin → Tax Reports</strong> for Thai tax compliance: VAT reports (PP30) and Withholding Tax (WHT) summaries.',

        // Chapter 7
        'ch7_title'         => 'User Management',
        'ch7_1_title'       => 'User Roles',
        'ch7_1_desc'        => 'iACC has three permission levels:',
        'ch7_1_roles'       => '<li><strong>User (Level 0)</strong> — Can access assigned company only, create and view documents</li><li><strong>Admin (Level 1)</strong> — Can manage master data, access all companies, manage users</li><li><strong>Super Admin (Level 2)</strong> — Full system access including payment gateway config, tax reports, audit logs, and developer tools</li>',
        'ch7_2_title'       => 'Adding Users',
        'ch7_2_desc'        => 'Admin users: Go to <strong>Admin → Users</strong>. Click <strong>Add User</strong>, enter name, email, password, select role level, and assign company. The new user can log in immediately.',
        'ch7_3_title'       => 'Audit Log',
        'ch7_3_desc'        => 'Go to <strong>Admin → Audit Log</strong> to track all user actions: who did what, when, and on which records. Essential for security and compliance.',

        // Chapter 8
        'ch8_title'         => 'Tips & Best Practices',
        'ch8_items'         => '<li><strong>Set up master data first</strong> — Company → Category → Brand → Product → Model. This hierarchy must be created in order.</li><li><strong>Use descriptive names</strong> — "Phuket 4D3N Premium Beach Package" is better than "Package 1".</li><li><strong>Keep payment records up to date</strong> — Record vouchers and receipts promptly so your dashboard shows accurate numbers.</li><li><strong>Review AR Aging weekly</strong> — Chase overdue invoices before they become too old.</li><li><strong>Back up regularly</strong> — Export your database periodically for safety.</li><li><strong>Use both languages</strong> — Fill in Thai names (name_th) for products and companies so Thai-speaking users see localized content.</li><li><strong>Check the Audit Log</strong> — If something looks wrong, the audit log shows exactly what changed and who changed it.</li><li><strong>Print to PDF</strong> — All documents (invoices, quotations, receipts, POs) can be exported as professional PDFs.</li>',
    ],
    'th' => [
        'page_title'        => 'คู่มือการใช้งาน',
        'page_subtitle'     => 'คู่มือแนะนำการใช้งาน iACC ฉบับสมบูรณ์',
        'toc'               => 'สารบัญ',
        'back_help'         => 'กลับไปศูนย์ช่วยเหลือ',
        'updated'           => 'อัปเดตล่าสุด',
        'step'              => 'ขั้นตอน',
        'tip'               => 'เคล็ดลับ',
        'note'              => 'หมายเหตุ',
        'warning'           => 'คำเตือน',
        'next'              => 'ถัดไป',
        'prev'              => 'ก่อนหน้า',
        'print'             => 'พิมพ์คู่มือ',

        'ch1_title'         => 'เริ่มต้นใช้งาน',
        'ch1_1_title'       => 'เข้าสู่ระบบ',
        'ch1_1_desc'        => 'เปิดเบราว์เซอร์แล้วไปที่ URL ของ iACC กรอกอีเมลและรหัสผ่าน จากนั้นคลิก <strong>Sign In</strong> หากยังไม่มีบัญชี คลิก <strong>สมัครฟรี</strong> เพื่อลงทะเบียน',
        'ch1_2_title'       => 'ภาพรวมแดชบอร์ด',
        'ch1_2_desc'        => 'หลังเข้าสู่ระบบ คุณจะเห็นแดชบอร์ดแสดงตัวเลขสำคัญ: รายได้รวม ใบแจ้งหนี้ค้างชำระ คำสั่งซื้อล่าสุด และปุ่มการทำงานด่วน ใช้เมนูด้านซ้ายเพื่อไปยังโมดูลต่างๆ',
        'ch1_3_title'       => 'เปลี่ยนภาษา',
        'ch1_3_desc'        => 'คลิกปุ่ม <strong>English</strong> หรือ <strong>ภาษาไทย</strong> ที่แถบนำทางด้านบน ระบบจะโหลดใหม่ในภาษาที่เลือก การตั้งค่าจะถูกบันทึกอัตโนมัติ',
        'ch1_4_title'       => 'สลับบริษัท',
        'ch1_4_desc'        => 'ผู้ดูแลระบบสามารถจัดการหลายบริษัท ไปที่ <strong>แดชบอร์ด</strong> แล้วเลือกบริษัทจากรายการ ผู้ใช้ทั่วไปจะถูกกำหนดให้เข้าถึงบริษัทเดียว',

        'ch2_title'         => 'การตั้งค่าเริ่มต้น',
        'ch2_intro'         => 'ก่อนสร้างรายการ คุณต้องตั้งค่าข้อมูลหลักก่อน ปฏิบัติตามขั้นตอนเหล่านี้ตามลำดับ:',
        'ch2_preloaded'     => '<strong><i class="fa fa-magic"></i> ข่าวดี!</strong> เมื่อคุณลงทะเบียน ระบบจะสร้างข้อมูลเริ่มต้นให้อัตโนมัติ: <strong>หมวดหมู่ค่าใช้จ่าย 10 รายการ</strong>, <strong>วิธีชำระเงิน 4 รายการ</strong> (เงินสด, โอนเงิน, บัตรเครดิต, เช็ค) และ <strong>ผังบัญชี 20 รายการ</strong> คุณสามารถเริ่มใช้งานได้ทันทีหรือปรับแต่งตามธุรกิจ ดูรายละเอียดทั้งหมดได้ที่ <a href="index.php?page=master_data_guide">คู่มือข้อมูลหลัก</a>',
        'ch2_1_title'       => 'ขั้นตอนที่ 1: สร้างบริษัท',
        'ch2_1_desc'        => 'ไปที่ <strong>ข้อมูลหลัก → บริษัท</strong> คลิก <strong>เพิ่มใหม่</strong> กรอกชื่อบริษัท ที่อยู่ เลขประจำตัวผู้เสียภาษี หมายเลขโทรศัพท์ และสาขา ข้อมูลนี้จะปรากฏบนใบแจ้งหนี้ ใบเสนอราคา และใบเสร็จ',
        'ch2_1_fields'      => 'ฟิลด์บังคับ: ชื่อบริษัท, ที่อยู่, โทรศัพท์ ไม่บังคับ: เลขประจำตัวผู้เสียภาษี, สาขา, เว็บไซต์, โลโก้',
        'ch2_2_title'       => 'ขั้นตอนที่ 2: ตั้งค่าหมวดหมู่',
        'ch2_2_desc'        => 'ไปที่ <strong>ข้อมูลหลัก → หมวดหมู่</strong> หมวดหมู่คือการจัดกลุ่มสินค้าระดับสูงสุด ตัวอย่าง: "แพ็คเกจทัวร์", "การขนส่ง", "ที่พัก", "อิเล็กทรอนิกส์"',
        'ch2_3_title'       => 'ขั้นตอนที่ 3: เพิ่มแบรนด์',
        'ch2_3_desc'        => 'ไปที่ <strong>ข้อมูลหลัก → แบรนด์</strong> แบรนด์คือซัพพลายเออร์หรือพันธมิตรทางธุรกิจ แต่ละแบรนด์เชื่อมกับหมวดหมู่ ตัวอย่าง: "สิงคโปร์แอร์ไลน์" ภายใต้การขนส่ง',
        'ch2_4_title'       => 'ขั้นตอนที่ 4: สร้างประเภทสินค้า',
        'ch2_4_desc'        => 'ไปที่ <strong>ข้อมูลหลัก → สินค้า</strong> ประเภทสินค้าคือรายการเฉพาะภายในแบรนด์ ตัวอย่าง: "ตั๋วเครื่องบิน", "ห้องพัก", "ทัวร์สปีดโบ๊ท"',
        'ch2_5_title'       => 'ขั้นตอนที่ 5: เพิ่มรุ่น (แพ็คเกจ/SKU)',
        'ch2_5_desc'        => 'ไปที่ <strong>ข้อมูลหลัก → รุ่น</strong> รุ่นคือสินค้าจริงที่คุณขายพร้อมราคา ตัวอย่าง: "ภูเก็ต 4วัน3คืน พรีเมียม — ฿15,990" แต่ละรุ่นอยู่ภายใต้ประเภทสินค้าและมีราคากำกับ',
        'ch2_hierarchy'     => 'ลำดับชั้นข้อมูลหลัก',
        'ch2_hierarchy_desc'=> 'หมวดหมู่ → แบรนด์ → ประเภทสินค้า → รุ่น (พร้อมราคา) ต้องสร้างตามลำดับนี้',
        'ch2_6_title'       => 'ขั้นตอนที่ 6: ตั้งค่าวิธีการชำระเงิน',
        'ch2_6_desc'        => 'ผู้ดูแลระบบ: ไปที่ <strong>ผู้ดูแล → วิธีการชำระเงิน</strong> ตั้งค่าวิธีการชำระเงินที่ธุรกิจรับ: โอนธนาคาร, เงินสด, บัตรเครดิต, PromptPay, PayPal ฯลฯ',
        'ch2_7_title'       => 'ขั้นตอนที่ 7: ตั้งค่าสกุลเงิน',
        'ch2_7_desc'        => 'ผู้ดูแลระบบ: ไปที่ <strong>ผู้ดูแล → สกุลเงิน</strong> สกุลเงินเริ่มต้นคือบาทไทย (THB) เพิ่มสกุลเงินอื่นหากมีลูกค้าต่างชาติ: USD, EUR, SGD, JPY',

        'ch3_title'         => 'การสร้างรายการ',
        'ch3_intro'         => 'เมื่อตั้งค่าข้อมูลหลักเรียบร้อยแล้ว คุณสามารถเริ่มสร้างเอกสารทางธุรกิจ:',
        'ch3_1_title'       => 'ใบขอซื้อ (PR)',
        'ch3_1_desc'        => 'PR คือคำขอซื้อหรือขาย ไปที่ <strong>ใบขอซื้อ</strong> ในเมนูด้านข้าง',
        'ch3_1_steps'       => '<li>คลิก <strong>PR สำหรับผู้ขาย</strong> (ซื้อ) หรือ <strong>PR สำหรับลูกค้า</strong> (ขาย)</li><li>เลือกบริษัท/ลูกค้า</li><li>เพิ่มสินค้าจากข้อมูลหลัก — เลือกหมวดหมู่ แบรนด์ ประเภท รุ่น</li><li>กำหนดจำนวนและยืนยันราคา</li><li>คลิก <strong>บันทึก</strong> เพื่อสร้าง PR</li>',
        'ch3_2_title'       => 'ใบเสนอราคา',
        'ch3_2_desc'        => 'ใบเสนอราคาคือข้อเสนอราคาอย่างเป็นทางการให้ลูกค้า ไปที่ <strong>การขาย → ใบเสนอราคา</strong>',
        'ch3_2_steps'       => '<li>คลิก <strong>สร้าง</strong> เพื่อเริ่มใบเสนอราคาใหม่</li><li>เลือกบริษัทลูกค้า</li><li>เพิ่มรายการสินค้า จำนวน และราคา</li><li>กำหนดระยะเวลาที่ใช้ได้และเงื่อนไข</li><li>บันทึกและส่งให้ลูกค้า (PDF หรืออีเมล)</li>',
        'ch3_3_title'       => 'ใบสั่งซื้อ (PO)',
        'ch3_3_desc'        => 'PO คือคำสั่งซื้อที่ยืนยันแล้ว ไปที่ <strong>การขาย → ใบสั่งซื้อ</strong> สร้างได้จาก PR ที่อนุมัติแล้วหรือสร้างตรง',
        'ch3_3_steps'       => '<li>คลิก <strong>สร้าง</strong> หรือแปลงจาก PR ที่มีอยู่</li><li>เพิ่มหรือปรับสินค้าและจำนวน</li><li>กำหนดวันส่งและเงื่อนไขการชำระ</li><li>บันทึก — เลขที่ PO สร้างอัตโนมัติ</li><li>เมื่อแก้ไข PO จะสร้างเวอร์ชันใหม่อัตโนมัติ (ติดตามเวอร์ชัน)</li>',
        'ch3_4_title'       => 'ใบส่งสินค้า',
        'ch3_4_desc'        => 'ติดตามการจัดส่งที่เชื่อมกับ PO ไปที่ <strong>การขาย → ใบส่งสินค้า</strong>',
        'ch3_4_steps'       => '<li>คลิก <strong>สร้าง</strong> และเชื่อมกับ PO</li><li>กรอกรายละเอียด: วันที่ ผู้ขนส่ง เลขพัสดุ</li><li>ระบุสินค้าที่จัดส่งแล้ว</li><li>พิมพ์ใบส่งสินค้า PDF สำหรับลูกค้า</li>',

        'ch4_title'         => 'การเรียกเก็บเงินและการชำระเงิน',
        'ch4_1_title'       => 'สร้างใบแจ้งหนี้',
        'ch4_1_desc'        => 'ไปที่ <strong>ใบแจ้งหนี้ → ใบแจ้งหนี้</strong> สร้างใบแจ้งหนี้จาก PO หรือสร้างเอง',
        'ch4_1_steps'       => '<li>คลิก <strong>สร้างใบแจ้งหนี้</strong></li><li>เลือก PO หรือกรอกรายการเอง</li><li>ตรวจสอบยอดรวม ภาษี (VAT/WHT) และส่วนลด</li><li>บันทึก — เลขที่ใบแจ้งหนี้สร้างอัตโนมัติ</li><li>พิมพ์ PDF หรือส่งอีเมลให้ลูกค้า</li>',
        'ch4_2_title'       => 'ใบกำกับภาษี',
        'ch4_2_desc'        => 'สำหรับการปฏิบัติตามกฎหมายภาษีไทย ไปที่ <strong>ใบแจ้งหนี้ → ใบกำกับภาษี</strong> กระบวนการเหมือนใบแจ้งหนี้ปกติ แต่สร้างเอกสารที่เป็นไปตามกฎหมายภาษีไทยพร้อมเลขประจำตัวผู้เสียภาษีและสาขา',
        'ch4_3_title'       => 'บันทึกการชำระเงิน (ใบสำคัญจ่ายและใบเสร็จ)',
        'ch4_3_desc'        => 'เมื่อเงิน <strong>ออก</strong> (จ่ายผู้ขาย) สร้าง <strong>ใบสำคัญจ่าย</strong> เมื่อเงิน <strong>เข้า</strong> (ลูกค้าจ่าย) สร้าง <strong>ใบเสร็จ</strong>',
        'ch4_3_steps'       => '<li>ไปที่ <strong>การชำระเงิน → ใบสำคัญจ่าย</strong> (ออก) หรือ <strong>ใบเสร็จ</strong> (เข้า)</li><li>เลือกใบแจ้งหนี้ที่ชำระ</li><li>กรอกจำนวนเงินและวิธีการ (ธนาคาร เงินสด ฯลฯ)</li><li>แนบหลักฐานการชำระ (อัพโหลดสลิป) ถ้ามี</li><li>บันทึก — สถานะใบแจ้งหนี้อัปเดตอัตโนมัติ</li>',
        'ch4_4_title'       => 'ติดตามการชำระเงิน',
        'ch4_4_desc'        => 'ไปที่ <strong>การชำระเงิน → ติดตามการชำระ</strong> เพื่อดูใบแจ้งหนี้ทั้งหมดและสถานะ: ชำระแล้ว บางส่วน หรือเกินกำหนด กรองตามช่วงวันที่และบริษัท',
        'ch4_5_title'       => 'ตรวจสอบสลิป',
        'ch4_5_desc'        => 'เมื่อลูกค้าส่งสลิปการชำระ ไปที่ <strong>การชำระเงิน → ตรวจสอบสลิป</strong> เพื่อตรวจสอบและอนุมัติ ช่วยป้องกันการทุจริตและรับรองความถูกต้อง',

        'ch5_title'         => 'ค่าใช้จ่ายและการบัญชี',
        'ch5_1_title'       => 'บันทึกค่าใช้จ่าย',
        'ch5_1_desc'        => 'ไปที่ <strong>ค่าใช้จ่าย → เพิ่มค่าใช้จ่าย</strong> บันทึกค่าใช้จ่ายธุรกิจรายวันพร้อมหมวดหมู่ ยอดเงิน ผู้ขาย วันที่ และโปรเจกต์',
        'ch5_1_steps'       => '<li>คลิก <strong>เพิ่มค่าใช้จ่าย</strong></li><li>เลือกหมวดหมู่ (สร้างหมวดหมู่ก่อนที่ <strong>ค่าใช้จ่าย → หมวดหมู่</strong>)</li><li>กรอกยอดเงิน วันที่ รายละเอียด และผู้ขาย</li><li>เลือกเชื่อมกับโปรเจกต์เพื่อติดตามต้นทุน (ไม่บังคับ)</li><li>บันทึก — ค่าใช้จ่ายจะแสดงในรายงาน</li>',
        'ch5_2_title'       => 'รายการบันทึก',
        'ch5_2_desc'        => 'สำหรับระบบบัญชีคู่ ไปที่ <strong>บัญชี → สร้างรายการบันทึกใหม่</strong> เลือกบัญชีจากผังบัญชี กรอกยอดเดบิตและเครดิต ระบบจะตรวจสอบว่าเดบิตเท่ากับเครดิต',
        'ch5_3_title'       => 'ผังบัญชี',
        'ch5_3_desc'        => 'ไปที่ <strong>บัญชี → ผังบัญชี</strong> เพื่อดูและจัดการโครงสร้างบัญชี ผังบัญชีเริ่มต้นจะถูกสร้างอัตโนมัติเมื่อเข้าใช้ครั้งแรก',
        'ch5_4_title'       => 'งบทดลอง',
        'ch5_4_desc'        => 'ไปที่ <strong>บัญชี → งบทดลอง</strong> เพื่อตรวจสอบว่าเดบิตรวมเท่ากับเครดิตรวม จำเป็นสำหรับรายงานทางการเงินที่ถูกต้อง',

        'ch6_title'         => 'รายงานและการวิเคราะห์',
        'ch6_1_title'       => 'ศูนย์รายงาน',
        'ch6_1_desc'        => 'ไปที่ <strong>รายงาน → ศูนย์รายงาน</strong> สำหรับแดชบอร์ดรายงานทั้งหมด กรองด่วน: วันนี้ สัปดาห์นี้ เดือนนี้ ปีนี้ ทั้งหมด',
        'ch6_2_title'       => 'สรุปธุรกิจ',
        'ch6_2_desc'        => 'ไปที่ <strong>รายงาน → สรุปธุรกิจ</strong> สำหรับภาพรวมรายได้ ค่าใช้จ่าย กำไร/ขาดทุนตามช่วงเวลา',
        'ch6_3_title'       => 'รายงาน AR Aging',
        'ch6_3_desc'        => 'ไปที่ <strong>รายงาน → AR Aging</strong> เพื่อวิเคราะห์ใบแจ้งหนี้ค้างชำระตามอายุ: ปัจจุบัน 30 วัน 60 วัน 90+ วัน',
        'ch6_4_title'       => 'รายงานภาษี',
        'ch6_4_desc'        => 'ผู้ดูแลระบบ: ไปที่ <strong>ผู้ดูแล → รายงานภาษี</strong> สำหรับการปฏิบัติตามกฎหมายภาษีไทย: รายงาน VAT (ภ.พ.30) และสรุปภาษีหัก ณ ที่จ่าย',

        'ch7_title'         => 'การจัดการผู้ใช้',
        'ch7_1_title'       => 'บทบาทผู้ใช้',
        'ch7_1_desc'        => 'iACC มี 3 ระดับสิทธิ์:',
        'ch7_1_roles'       => '<li><strong>ผู้ใช้ (ระดับ 0)</strong> — เข้าถึงได้เฉพาะบริษัทที่ได้รับมอบหมาย สร้างและดูเอกสาร</li><li><strong>ผู้ดูแล (ระดับ 1)</strong> — จัดการข้อมูลหลัก เข้าถึงทุกบริษัท จัดการผู้ใช้</li><li><strong>ผู้ดูแลสูงสุด (ระดับ 2)</strong> — เข้าถึงระบบเต็มรูปแบบ รวมถึงตั้งค่า payment gateway รายงานภาษี บันทึกตรวจสอบ และเครื่องมือนักพัฒนา</li>',
        'ch7_2_title'       => 'เพิ่มผู้ใช้',
        'ch7_2_desc'        => 'ผู้ดูแลระบบ: ไปที่ <strong>ผู้ดูแล → ผู้ใช้</strong> คลิก <strong>เพิ่มผู้ใช้</strong> กรอกชื่อ อีเมล รหัสผ่าน เลือกระดับบทบาท และกำหนดบริษัท ผู้ใช้ใหม่สามารถเข้าสู่ระบบได้ทันที',
        'ch7_3_title'       => 'บันทึกตรวจสอบ',
        'ch7_3_desc'        => 'ไปที่ <strong>ผู้ดูแล → บันทึกตรวจสอบ</strong> เพื่อติดตามการกระทำของผู้ใช้: ใครทำอะไร เมื่อไหร่ และกับข้อมูลใด สำคัญสำหรับความปลอดภัยและการตรวจสอบ',

        'ch8_title'         => 'เคล็ดลับและแนวปฏิบัติที่ดี',
        'ch8_items'         => '<li><strong>ตั้งค่าข้อมูลหลักก่อน</strong> — บริษัท → หมวดหมู่ → แบรนด์ → สินค้า → รุ่น ต้องสร้างตามลำดับนี้</li><li><strong>ใช้ชื่อที่สื่อความหมาย</strong> — "ภูเก็ต 4วัน3คืน พรีเมียม แพ็คเกจชายหาด" ดีกว่า "แพ็คเกจ 1"</li><li><strong>อัปเดตการชำระเงินให้ทันสมัย</strong> — บันทึกใบสำคัญจ่ายและใบเสร็จทันทีเพื่อให้แดชบอร์ดแสดงตัวเลขที่ถูกต้อง</li><li><strong>ตรวจสอบ AR Aging ทุกสัปดาห์</strong> — ติดตามใบแจ้งหนี้เกินกำหนดก่อนที่จะล่าช้าเกินไป</li><li><strong>สำรองข้อมูลสม่ำเสมอ</strong> — ส่งออกฐานข้อมูลเป็นระยะเพื่อความปลอดภัย</li><li><strong>กรอกทั้ง 2 ภาษา</strong> — กรอกชื่อภาษาไทย (name_th) สำหรับสินค้าและบริษัทเพื่อให้ผู้ใช้ที่พูดภาษาไทยเห็นเนื้อหาท้องถิ่น</li><li><strong>ตรวจสอบบันทึกตรวจสอบ</strong> — หากมีอะไรผิดปกติ บันทึกจะแสดงว่าอะไรเปลี่ยนแปลงและใครเปลี่ยน</li><li><strong>พิมพ์เป็น PDF</strong> — เอกสารทั้งหมด (ใบแจ้งหนี้ ใบเสนอราคา ใบเสร็จ PO) สามารถส่งออกเป็น PDF มืออาชีพ</li>',
    ]
][$lang];
?>

<style>
.manual-container { max-width: 960px; margin: 0 auto; }
.manual-header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; }
.manual-header h1 { font-size: 32px; color: #333; margin: 0 0 8px 0; }
.manual-header p { color: #6c757d; font-size: 16px; margin: 0; }
.manual-meta { display: flex; justify-content: center; gap: 20px; margin-top: 15px; }
.manual-meta span { font-size: 13px; color: #999; }
.manual-actions { display: flex; justify-content: center; gap: 12px; margin-top: 15px; }
.manual-actions a, .manual-actions button { font-size: 13px; padding: 6px 16px; border-radius: 6px; text-decoration: none; border: 1px solid #ddd; background: white; color: #555; cursor: pointer; transition: all 0.2s; }
.manual-actions a:hover, .manual-actions button:hover { background: #8e44ad; color: white; border-color: #8e44ad; }

/* TOC */
.manual-toc { background: #f8f9fa; border-radius: 12px; padding: 24px 30px; margin-bottom: 30px; }
.manual-toc h3 { margin: 0 0 15px 0; font-size: 18px; color: #333; }
.manual-toc ol { margin: 0; padding-left: 20px; }
.manual-toc li { margin-bottom: 6px; }
.manual-toc a { color: #8e44ad; text-decoration: none; font-size: 15px; }
.manual-toc a:hover { text-decoration: underline; }

/* Chapters */
.manual-chapter { margin-bottom: 40px; padding-top: 10px; }
.manual-chapter h2 { font-size: 24px; font-weight: 600; color: #333; margin: 0 0 16px 0; padding-bottom: 10px; border-bottom: 2px solid #8e44ad; display: flex; align-items: center; gap: 10px; }
.manual-chapter h2 .ch-num { background: #8e44ad; color: white; width: 36px; height: 36px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }

.manual-section { background: white; border-radius: 10px; padding: 20px 24px; margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); border-left: 4px solid #8e44ad; }
.manual-section h3 { margin: 0 0 10px 0; font-size: 17px; font-weight: 600; color: #333; }
.manual-section p { margin: 0 0 10px 0; font-size: 14px; color: #555; line-height: 1.7; }
.manual-section ol, .manual-section ul { margin: 8px 0 0 0; padding-left: 22px; font-size: 14px; color: #555; line-height: 1.9; }

.manual-callout { border-radius: 8px; padding: 14px 18px; margin: 12px 0; font-size: 13px; line-height: 1.6; }
.manual-callout.tip { background: #e8f5e9; border-left: 4px solid #4caf50; color: #2e7d32; }
.manual-callout.note { background: #e3f2fd; border-left: 4px solid #2196f3; color: #1565c0; }
.manual-callout.warning { background: #fff3e0; border-left: 4px solid #ff9800; color: #e65100; }
.manual-callout strong { font-weight: 600; }

/* Hierarchy diagram */
.hierarchy-box { background: #faf5ff; border: 1px solid #e0d0f0; border-radius: 10px; padding: 20px; margin: 12px 0; font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.8; color: #5b2d8e; }

/* Workflow diagram */
.workflow-flow { display: flex; align-items: center; flex-wrap: wrap; gap: 4px; margin: 12px 0; }
.workflow-flow .wf-step { background: #8e44ad; color: white; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 500; }
.workflow-flow .wf-arrow { color: #8e44ad; font-size: 18px; font-weight: bold; }

/* Roles table */
.roles-table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 14px; }
.roles-table th { background: #8e44ad; color: white; padding: 10px 14px; text-align: left; }
.roles-table td { padding: 10px 14px; border-bottom: 1px solid #eee; }
.roles-table tr:hover td { background: #faf5ff; }

@media print {
    .manual-actions, .manual-toc { page-break-after: always; }
    .manual-section { break-inside: avoid; }
    .no-print { display: none !important; }
}

@media (max-width: 768px) {
    .manual-container { padding: 0 10px; }
    .manual-header h1 { font-size: 24px; }
    .workflow-flow { flex-direction: column; }
    .workflow-flow .wf-arrow { transform: rotate(90deg); }
}
</style>

<div class="manual-container">
    <!-- Header -->
    <div class="manual-header">
        <h1><i class="fa fa-book"></i> <?= $t['page_title'] ?></h1>
        <p><?= $t['page_subtitle'] ?></p>
        <div class="manual-meta">
            <span><i class="fa fa-calendar"></i> <?= $t['updated'] ?>: 2026-03-31</span>
            <span><i class="fa fa-tag"></i> v6.0</span>
        </div>
        <div class="manual-actions no-print">
            <a href="index.php?page=help"><i class="fa fa-arrow-left"></i> <?= $t['back_help'] ?></a>
            <button onclick="window.print()"><i class="fa fa-print"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <!-- Table of Contents -->
    <div class="manual-toc no-print">
        <h3><i class="fa fa-list-ol"></i> <?= $t['toc'] ?></h3>
        <ol>
            <li><a href="#ch1"><?= $t['ch1_title'] ?></a></li>
            <li><a href="#ch2"><?= $t['ch2_title'] ?></a></li>
            <li><a href="#ch3"><?= $t['ch3_title'] ?></a></li>
            <li><a href="#ch4"><?= $t['ch4_title'] ?></a></li>
            <li><a href="#ch5"><?= $t['ch5_title'] ?></a></li>
            <li><a href="#ch6"><?= $t['ch6_title'] ?></a></li>
            <li><a href="#ch7"><?= $t['ch7_title'] ?></a></li>
            <li><a href="#ch8"><?= $t['ch8_title'] ?></a></li>
        </ol>
    </div>

    <!-- Chapter 1: Getting Started -->
    <div class="manual-chapter" id="ch1">
        <h2><span class="ch-num">1</span> <?= $t['ch1_title'] ?></h2>

        <div class="manual-section">
            <h3><i class="fa fa-sign-in"></i> <?= $t['ch1_1_title'] ?></h3>
            <p><?= $t['ch1_1_desc'] ?></p>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-dashboard"></i> <?= $t['ch1_2_title'] ?></h3>
            <p><?= $t['ch1_2_desc'] ?></p>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-language"></i> <?= $t['ch1_3_title'] ?></h3>
            <p><?= $t['ch1_3_desc'] ?></p>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-building"></i> <?= $t['ch1_4_title'] ?></h3>
            <p><?= $t['ch1_4_desc'] ?></p>
        </div>
    </div>

    <!-- Chapter 2: Initial Setup -->
    <div class="manual-chapter" id="ch2">
        <h2><span class="ch-num">2</span> <?= $t['ch2_title'] ?></h2>
        <p style="color:#555; font-size:14px; margin-bottom:16px;"><?= $t['ch2_intro'] ?></p>

        <div class="manual-callout note" style="background:#f0fdf4; border-left-color:#22c55e;">
            <?= $t['ch2_preloaded'] ?>
        </div>

        <div class="manual-callout note">
            <strong><i class="fa fa-info-circle"></i> <?= $t['ch2_hierarchy'] ?>:</strong> <?= $t['ch2_hierarchy_desc'] ?>
        </div>

        <div class="hierarchy-box">
            Category (<?= $lang === 'th' ? 'หมวดหมู่' : 'e.g. Tour Packages' ?>)<br>
            &nbsp;&nbsp;└── Brand (<?= $lang === 'th' ? 'แบรนด์ เช่น Thailand Tourism' : 'e.g. Thailand Tourism' ?>)<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;└── Product Type (<?= $lang === 'th' ? 'ประเภท เช่น ทัวร์ชายหาด' : 'e.g. Beach Holiday' ?>)<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;├── Model: Phuket 4D3N Premium — ฿15,990<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;└── Model: Phuket 4D3N Budget — ฿9,990
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-building"></i> <?= $t['ch2_1_title'] ?></h3>
            <p><?= $t['ch2_1_desc'] ?></p>
            <div class="manual-callout tip"><strong><?= $t['tip'] ?>:</strong> <?= $t['ch2_1_fields'] ?></div>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-folder"></i> <?= $t['ch2_2_title'] ?></h3>
            <p><?= $t['ch2_2_desc'] ?></p>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-bookmark"></i> <?= $t['ch2_3_title'] ?></h3>
            <p><?= $t['ch2_3_desc'] ?></p>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-cube"></i> <?= $t['ch2_4_title'] ?></h3>
            <p><?= $t['ch2_4_desc'] ?></p>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-cubes"></i> <?= $t['ch2_5_title'] ?></h3>
            <p><?= $t['ch2_5_desc'] ?></p>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-credit-card-alt"></i> <?= $t['ch2_6_title'] ?></h3>
            <p><?= $t['ch2_6_desc'] ?></p>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-money"></i> <?= $t['ch2_7_title'] ?></h3>
            <p><?= $t['ch2_7_desc'] ?></p>
        </div>
    </div>

    <!-- Chapter 3: Creating Transactions -->
    <div class="manual-chapter" id="ch3">
        <h2><span class="ch-num">3</span> <?= $t['ch3_title'] ?></h2>
        <p style="color:#555; font-size:14px; margin-bottom:16px;"><?= $t['ch3_intro'] ?></p>

        <div class="workflow-flow">
            <span class="wf-step">PR</span><span class="wf-arrow">→</span>
            <span class="wf-step"><?= $lang === 'th' ? 'ใบเสนอราคา' : 'Quotation' ?></span><span class="wf-arrow">→</span>
            <span class="wf-step">PO</span><span class="wf-arrow">→</span>
            <span class="wf-step"><?= $lang === 'th' ? 'ใบส่งสินค้า' : 'Delivery' ?></span><span class="wf-arrow">→</span>
            <span class="wf-step"><?= $lang === 'th' ? 'ใบแจ้งหนี้' : 'Invoice' ?></span><span class="wf-arrow">→</span>
            <span class="wf-step"><?= $lang === 'th' ? 'ชำระเงิน' : 'Payment' ?></span>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-pencil-square-o"></i> <?= $t['ch3_1_title'] ?></h3>
            <p><?= $t['ch3_1_desc'] ?></p>
            <ol><?= $t['ch3_1_steps'] ?></ol>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-file-text-o"></i> <?= $t['ch3_2_title'] ?></h3>
            <p><?= $t['ch3_2_desc'] ?></p>
            <ol><?= $t['ch3_2_steps'] ?></ol>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-shopping-cart"></i> <?= $t['ch3_3_title'] ?></h3>
            <p><?= $t['ch3_3_desc'] ?></p>
            <ol><?= $t['ch3_3_steps'] ?></ol>
            <div class="manual-callout note"><strong><?= $t['note'] ?>:</strong> <?= $lang === 'th' ? 'เมื่อแก้ไข PO เวอร์ชันเก่าจะถูกเก็บไว้และเชื่อมกับเวอร์ชันใหม่โดยอัตโนมัติ' : 'When you edit a PO, the old version is preserved and linked to the new version automatically.' ?></div>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-truck"></i> <?= $t['ch3_4_title'] ?></h3>
            <p><?= $t['ch3_4_desc'] ?></p>
            <ol><?= $t['ch3_4_steps'] ?></ol>
        </div>
    </div>

    <!-- Chapter 4: Billing & Payments -->
    <div class="manual-chapter" id="ch4">
        <h2><span class="ch-num">4</span> <?= $t['ch4_title'] ?></h2>

        <div class="manual-section">
            <h3><i class="fa fa-file-text"></i> <?= $t['ch4_1_title'] ?></h3>
            <p><?= $t['ch4_1_desc'] ?></p>
            <ol><?= $t['ch4_1_steps'] ?></ol>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-file"></i> <?= $t['ch4_2_title'] ?></h3>
            <p><?= $t['ch4_2_desc'] ?></p>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-credit-card"></i> <?= $t['ch4_3_title'] ?></h3>
            <p><?= $t['ch4_3_desc'] ?></p>
            <ol><?= $t['ch4_3_steps'] ?></ol>
            <div class="manual-callout tip"><strong><?= $t['tip'] ?>:</strong> <?= $lang === 'th' ? 'ใบสำคัญจ่าย = เงินออก (ป้ายแดง), ใบเสร็จ = เงินเข้า (ป้ายเขียว)' : 'Voucher = Money OUT (red tag), Receipt = Money IN (green tag)' ?></div>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-money"></i> <?= $t['ch4_4_title'] ?></h3>
            <p><?= $t['ch4_4_desc'] ?></p>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-qrcode"></i> <?= $t['ch4_5_title'] ?></h3>
            <p><?= $t['ch4_5_desc'] ?></p>
        </div>
    </div>

    <!-- Chapter 5: Expenses & Accounting -->
    <div class="manual-chapter" id="ch5">
        <h2><span class="ch-num">5</span> <?= $t['ch5_title'] ?></h2>

        <div class="manual-section">
            <h3><i class="fa fa-money"></i> <?= $t['ch5_1_title'] ?></h3>
            <p><?= $t['ch5_1_desc'] ?></p>
            <ol><?= $t['ch5_1_steps'] ?></ol>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-list-alt"></i> <?= $t['ch5_2_title'] ?></h3>
            <p><?= $t['ch5_2_desc'] ?></p>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-sitemap"></i> <?= $t['ch5_3_title'] ?></h3>
            <p><?= $t['ch5_3_desc'] ?></p>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-balance-scale"></i> <?= $t['ch5_4_title'] ?></h3>
            <p><?= $t['ch5_4_desc'] ?></p>
        </div>
    </div>

    <!-- Chapter 6: Reports -->
    <div class="manual-chapter" id="ch6">
        <h2><span class="ch-num">6</span> <?= $t['ch6_title'] ?></h2>

        <div class="manual-section">
            <h3><i class="fa fa-th-large"></i> <?= $t['ch6_1_title'] ?></h3>
            <p><?= $t['ch6_1_desc'] ?></p>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-bar-chart-o"></i> <?= $t['ch6_2_title'] ?></h3>
            <p><?= $t['ch6_2_desc'] ?></p>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-clock-o"></i> <?= $t['ch6_3_title'] ?></h3>
            <p><?= $t['ch6_3_desc'] ?></p>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-file-text"></i> <?= $t['ch6_4_title'] ?></h3>
            <p><?= $t['ch6_4_desc'] ?></p>
        </div>
    </div>

    <!-- Chapter 7: User Management -->
    <div class="manual-chapter" id="ch7">
        <h2><span class="ch-num">7</span> <?= $t['ch7_title'] ?></h2>

        <div class="manual-section">
            <h3><i class="fa fa-users"></i> <?= $t['ch7_1_title'] ?></h3>
            <p><?= $t['ch7_1_desc'] ?></p>
            <ul><?= $t['ch7_1_roles'] ?></ul>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-user-plus"></i> <?= $t['ch7_2_title'] ?></h3>
            <p><?= $t['ch7_2_desc'] ?></p>
        </div>

        <div class="manual-section">
            <h3><i class="fa fa-history"></i> <?= $t['ch7_3_title'] ?></h3>
            <p><?= $t['ch7_3_desc'] ?></p>
        </div>
    </div>

    <!-- Chapter 8: Tips -->
    <div class="manual-chapter" id="ch8">
        <h2><span class="ch-num">8</span> <?= $t['ch8_title'] ?></h2>

        <div class="manual-section">
            <ul><?= $t['ch8_items'] ?></ul>
        </div>
    </div>

    <!-- Version -->
    <div style="background: linear-gradient(135deg, #8e44ad, #9b59b6); color: white; border-radius: 12px; padding: 24px; text-align: center; margin-top: 20px;">
        <h3 style="margin: 0 0 6px 0; font-size: 18px;">iACC Accounting System</h3>
        <p style="margin: 0; opacity: 0.9; font-size: 14px;"><?= $lang === 'th' ? 'ระบบจัดการบัญชีมืออาชีพสำหรับธุรกิจยุคใหม่' : 'Professional accounting management for modern businesses' ?></p>
        <span style="display: inline-block; background: rgba(255,255,255,0.2); padding: 4px 14px; border-radius: 20px; font-size: 13px; margin-top: 10px;">v6.0</span>
    </div>
</div>
