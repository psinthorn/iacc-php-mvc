<?php
/**
 * iACC Template — Dynamic Homepage
 * Loads products from local SQLite cache (synced from iACC API)
 * Redirects to setup.php if not yet configured
 */
$config = require __DIR__ . '/config.php';

if (!($config['configured'] ?? false)) {
    header('Location: setup.php');
    exit;
}

require_once __DIR__ . '/includes/database.php';
$db = new LocalDatabase();
$categories = $db->getCategories();
$allProducts = $db->getProducts();

// Group products by category
$productsByCategory = [];
foreach ($allProducts as $p) {
    $catName = $p['category_name'] ?: 'Other';
    $productsByCategory[$catName][] = $p;
}

$siteTitle  = htmlspecialchars($config['site_title'] ?? 'My Tour Company');
$themeColor = htmlspecialchars($config['theme_color'] ?? '#0369a1');
$currency   = $config['currency'] ?? 'THB';
$currencySymbol = match($currency) { 'USD' => '$', 'EUR' => '€', default => '฿' };
$productCount = count($allProducts);

// Placeholder images by category keyword
function getImageUrl(string $name, int $index = 0): string {
    $name = strtolower($name);
    $images = [
        'https://www.mysamuiisland.com/wp-content/uploads/2024/03/angthong-nation-marine-park.jpg',
        'https://www.mysamuiisland.com/wp-content/uploads/2023/12/koh-nangyuan-viewpoint-thailand.jpg',
        'https://www.mysamuiisland.com/wp-content/uploads/2024/03/fullmoon-party.jpg',
        'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=600',
        'https://images.unsplash.com/photo-1682687220742-aba13b6e50ba?w=600',
        'https://images.unsplash.com/photo-1506929562872-bb421503ef21?w=600',
    ];
    if (str_contains($name, 'angthong'))   return $images[0];
    if (str_contains($name, 'tao'))        return $images[1];
    if (str_contains($name, 'nangyuan'))   return $images[1];
    if (str_contains($name, 'moon'))       return $images[2];
    if (str_contains($name, 'sunset'))     return $images[3];
    if (str_contains($name, 'snorkel'))    return $images[4];
    if (str_contains($name, 'private'))    return $images[5];
    return $images[$index % count($images)];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $siteTitle ?></title>
    <meta name="description" content="<?= $siteTitle ?> — Book tours and experiences online. Powered by iACC.">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --ocean: <?= $themeColor ?>; }
        .category-filter { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; margin-bottom: 32px; }
        .category-btn { padding: 8px 20px; border-radius: 50px; border: 2px solid #e2e8f0; background: white; font-family: inherit; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; color: #475569; }
        .category-btn.active, .category-btn:hover { background: var(--ocean); color: white; border-color: var(--ocean); }
        .booking-modal { display: none; position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; padding: 24px; }
        .booking-modal.show { display: flex; }
        .modal-card { background: white; border-radius: 20px; max-width: 500px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 32px; position: relative; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
        .modal-close { position: absolute; top: 16px; right: 16px; width: 36px; height: 36px; border-radius: 50%; border: none; background: #f1f5f9; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center; }
        .modal-close:hover { background: #e2e8f0; }
        .modal-product { display: flex; gap: 16px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0; }
        .modal-product img { width: 80px; height: 80px; border-radius: 12px; object-fit: cover; }
        .modal-product h3 { font-size: 16px; margin-bottom: 4px; }
        .modal-product .price { font-size: 18px; font-weight: 700; color: var(--ocean); }
        .modal-form .form-group { margin-bottom: 14px; }
        .modal-form label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #334155; }
        .modal-form input, .modal-form textarea, .modal-form select { width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 10px; font-family: inherit; font-size: 14px; }
        .modal-form input:focus, .modal-form textarea:focus { outline: none; border-color: var(--ocean); }
        .modal-form .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .modal-result { padding: 16px; border-radius: 12px; margin-top: 16px; font-size: 14px; }
        .modal-result.success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .modal-result.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .admin-bar { background: #1e293b; color: #94a3b8; font-size: 12px; padding: 6px 0; text-align: center; }
        .admin-bar a { color: #38bdf8; margin-left: 16px; }
    </style>
</head>
<body>

<!-- Admin bar (visible to site owner) -->
<div class="admin-bar">
    <i class="fa-solid fa-gear"></i> Template Admin:
    <a href="sync.php"><i class="fa-solid fa-rotate"></i> Sync Products</a>
    <a href="setup.php"><i class="fa-solid fa-wrench"></i> Settings</a>
</div>

<!-- ============ NAVIGATION ============ -->
<nav class="navbar" id="navbar">
    <div class="container nav-container">
        <a href="#" class="logo">
            <span class="logo-icon">🚤</span>
            <span class="logo-text"><?= $siteTitle ?></span>
        </a>
        <ul class="nav-links" id="navLinks">
            <li><a href="#tours">Tours</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#contact">Contact</a></li>
        </ul>
        <div class="nav-actions">
            <a href="#tours" class="btn btn-primary">Book Now</a>
            <button class="mobile-toggle" id="mobileToggle"><i class="fa-solid fa-bars"></i></button>
        </div>
    </div>
</nav>

<!-- ============ HERO ============ -->
<section class="hero">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <span class="hero-badge">🌴 Welcome to <?= $siteTitle ?></span>
        <h1>Explore Paradise<br><span class="hero-highlight">By Speedboat</span></h1>
        <p class="hero-sub">Discover the Gulf of Thailand's most stunning islands, crystal-clear waters, and hidden beaches with our expert-guided speedboat tours.</p>
        <div class="hero-buttons">
            <a href="#tours" class="btn btn-primary btn-lg"><i class="fa-solid fa-compass"></i> Explore Tours</a>
            <a href="#contact" class="btn btn-outline-white btn-lg"><i class="fa-solid fa-phone"></i> Contact Us</a>
        </div>
        <div class="hero-stats">
            <div class="stat"><strong>500+</strong><span>Happy Customers</span></div>
            <div class="stat"><strong><?= $productCount ?></strong><span>Tour Packages</span></div>
            <div class="stat"><strong>10+</strong><span>Years Experience</span></div>
        </div>
    </div>
</section>

<!-- ============ TOUR PACKAGES (Dynamic from API) ============ -->
<section class="tours" id="tours">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Our Tours</span>
            <h2>Tour Packages</h2>
            <p>Browse all <?= $productCount ?> packages — fetched live from our booking system</p>
        </div>

        <!-- Category Filter -->
        <div class="category-filter">
            <button class="category-btn active" data-filter="all">All (<?= $productCount ?>)</button>
            <?php foreach ($productsByCategory as $catName => $prods): ?>
            <button class="category-btn" data-filter="<?= htmlspecialchars($catName) ?>">
                <?= htmlspecialchars($catName) ?> (<?= count($prods) ?>)
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Product Grid -->
        <div class="tour-grid">
            <?php $idx = 0; foreach ($allProducts as $product):
                $imgUrl = getImageUrl($product['name'], $idx);
                $price = number_format(floatval($product['price']));
                $catName = $product['category_name'] ?: 'Other';
                $typeName = $product['type_name'] ?? '';
            ?>
            <div class="tour-card" data-category="<?= htmlspecialchars($catName) ?>">
                <div class="tour-image" style="background-image:url('<?= $imgUrl ?>');">
                    <?php if ($idx === 0): ?><span class="tour-badge">Best Seller</span><?php endif; ?>
                    <?php if ($typeName): ?>
                    <span class="tour-type"><i class="fa-solid fa-tag"></i> <?= htmlspecialchars($typeName) ?></span>
                    <?php endif; ?>
                </div>
                <div class="tour-body">
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <p><?= htmlspecialchars($product['description'] ?: "Enjoy our {$catName} experience.") ?></p>
                    <div class="tour-meta">
                        <span><i class="fa-solid fa-layer-group"></i> <?= htmlspecialchars($catName) ?></span>
                    </div>
                    <div class="tour-footer">
                        <div class="tour-price"><?= $currencySymbol ?><?= $price ?> <small>/person</small></div>
                        <button class="btn btn-primary btn-book"
                            data-id="<?= $product['id'] ?>"
                            data-name="<?= htmlspecialchars($product['name']) ?>"
                            data-price="<?= $product['price'] ?>"
                            data-image="<?= $imgUrl ?>">
                            Book
                        </button>
                    </div>
                </div>
            </div>
            <?php $idx++; endforeach; ?>
        </div>

        <?php if (empty($allProducts)): ?>
        <div style="text-align:center; padding:60px 20px; color:#64748b;">
            <i class="fa-solid fa-box-open" style="font-size:48px; margin-bottom:16px; display:block;"></i>
            <h3>No Products Yet</h3>
            <p>Please <a href="sync.php" style="color:var(--ocean);">sync your products</a> from iACC.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============ ABOUT ============ -->
<section class="about" id="about">
    <div class="container">
        <div class="about-grid">
            <div class="about-content">
                <span class="section-badge">About Us</span>
                <h2>Your Trusted Tour Operator</h2>
                <p>We specialize in creating unforgettable experiences. With modern speedboats, experienced captains, and multilingual guides, we ensure your safety and enjoyment.</p>
                <div class="about-highlights">
                    <div class="ah"><i class="fa-solid fa-certificate"></i> Licensed Operator</div>
                    <div class="ah"><i class="fa-solid fa-life-ring"></i> Safety First</div>
                    <div class="ah"><i class="fa-solid fa-language"></i> English Speaking</div>
                    <div class="ah"><i class="fa-solid fa-clock"></i> 10+ Years</div>
                </div>
            </div>
            <div class="about-image">
                <img src="https://www.mysamuiisland.com/wp-content/uploads/2024/03/angthong-nation-marine-park.jpg" alt="Tour">
            </div>
        </div>
    </div>
</section>

<!-- ============ CONTACT ============ -->
<section class="contact" id="contact">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Contact</span>
            <h2>Get In Touch</h2>
            <p>Have questions? We're here to help!</p>
        </div>
        <div class="contact-grid">
            <div class="contact-card"><i class="fa-solid fa-phone"></i><h3>Phone</h3><p>077-962-220</p></div>
            <div class="contact-card"><i class="fa-solid fa-envelope"></i><h3>Email</h3><p>info@mysamuiisland.com</p></div>
            <div class="contact-card"><i class="fa-solid fa-location-dot"></i><h3>Location</h3><p>Koh Samui, Surat Thani</p></div>
            <div class="contact-card"><i class="fa-brands fa-facebook"></i><h3>Social</h3><p>@mysamuiislandtour</p></div>
        </div>
    </div>
</section>

<!-- ============ FOOTER ============ -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <span class="logo-icon">🚤</span>
                <span class="logo-text"><?= $siteTitle ?></span>
                <p>Book tours and experiences online.</p>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <a href="#tours">Tour Packages</a>
                <a href="#about">About Us</a>
                <a href="#contact">Contact</a>
            </div>
            <div class="footer-links">
                <h4>Categories</h4>
                <?php foreach (array_slice(array_keys($productsByCategory), 0, 5) as $cat): ?>
                <a href="#tours"><?= htmlspecialchars($cat) ?></a>
                <?php endforeach; ?>
            </div>
            <div class="footer-links">
                <h4>Powered By</h4>
                <a href="https://iacc.f2.co.th" target="_blank">iACC Accounting</a>
                <a href="https://iacc.f2.co.th" target="_blank">Sales Channel API</a>
                <p style="margin-top:10px;font-size:12px;opacity:0.6;">Invoicing & payments via iACC API</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= $siteTitle ?>. All rights reserved. | Template by <a href="https://iacc.f2.co.th" style="color:#06b6d4;">iACC</a></p>
        </div>
    </div>
</footer>

<!-- ============ BOOKING MODAL ============ -->
<div class="booking-modal" id="bookingModal">
    <div class="modal-card">
        <button class="modal-close" onclick="closeModal()">×</button>
        <div class="modal-product" id="modalProduct">
            <img id="modalImg" src="" alt="">
            <div>
                <h3 id="modalName"></h3>
                <div class="price" id="modalPrice"></div>
            </div>
        </div>
        <form class="modal-form" id="bookingForm">
            <input type="hidden" name="product_id" id="modalProductId">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="guest_name" placeholder="John Smith" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="guest_email" placeholder="john@email.com" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="guest_phone" placeholder="+66 8x-xxx-xxxx">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Tour Date</label>
                    <input type="date" name="check_in" id="tourDate">
                </div>
                <div class="form-group">
                    <label>Guests</label>
                    <input type="number" name="guests" min="1" max="50" value="2" id="guestsInput">
                </div>
            </div>
            <div class="form-group">
                <label>Special Requests</label>
                <textarea name="notes" rows="2" placeholder="Hotel pickup, dietary needs..."></textarea>
            </div>

            <div style="background:#f0f9ff; border-radius:10px; padding:14px; margin-bottom:16px; font-size:14px; color:#0369a1;">
                <strong>Total: <span id="totalAmount"><?= $currencySymbol ?>0</span></strong>
                <span style="font-size:12px; color:#64748b; display:block;">= <span id="pricePerPerson">0</span>/person × <span id="guestsDisplay">2</span> guests</span>
            </div>

            <button type="submit" class="btn btn-primary btn-block" id="btnSubmitBooking">
                <i class="fa-solid fa-paper-plane"></i> Confirm Booking
            </button>
            <div id="bookingResult"></div>
        </form>
    </div>
</div>

<!-- ============ JavaScript ============ -->
<script>
const CURRENCY = '<?= $currencySymbol ?>';
let currentPrice = 0;

// ---- Category Filter ----
document.querySelectorAll('.category-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const filter = this.dataset.filter;
        document.querySelectorAll('.tour-card').forEach(card => {
            card.style.display = (filter === 'all' || card.dataset.category === filter) ? '' : 'none';
        });
    });
});

// ---- Book Button → Open Modal ----
document.querySelectorAll('.btn-book').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const name = this.dataset.name;
        const price = parseFloat(this.dataset.price);
        const image = this.dataset.image;

        document.getElementById('modalProductId').value = id;
        document.getElementById('modalName').textContent = name;
        document.getElementById('modalImg').src = image;
        document.getElementById('modalPrice').textContent = CURRENCY + price.toLocaleString() + ' /person';
        currentPrice = price;

        updateTotal();
        document.getElementById('bookingModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    });
});

function closeModal() {
    document.getElementById('bookingModal').classList.remove('show');
    document.body.style.overflow = '';
    document.getElementById('bookingResult').innerHTML = '';
    document.getElementById('bookingForm').reset();
}

// Close on backdrop click
document.getElementById('bookingModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// ---- Update Total ----
function updateTotal() {
    const guests = parseInt(document.getElementById('guestsInput').value) || 1;
    const total = currentPrice * guests;
    document.getElementById('totalAmount').textContent = CURRENCY + total.toLocaleString();
    document.getElementById('pricePerPerson').textContent = CURRENCY + currentPrice.toLocaleString();
    document.getElementById('guestsDisplay').textContent = guests;
}
document.getElementById('guestsInput').addEventListener('input', updateTotal);

// ---- Submit Booking ----
document.getElementById('bookingForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitBooking');
    const resultDiv = document.getElementById('bookingResult');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;

    try {
        const fd = new FormData(this);
        const res = await fetch('book.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            resultDiv.className = 'modal-result success';
            resultDiv.innerHTML = `
                <h4 style="margin:0 0 8px;"><i class="fa-solid fa-circle-check"></i> ${data.message}</h4>
                <p><strong>Order:</strong> ${data.booking.reference || data.booking.order_id}</p>
                <p><strong>Tour:</strong> ${data.booking.product}</p>
                <p><strong>Total:</strong> ${CURRENCY}${parseFloat(data.booking.total_amount).toLocaleString()} ${data.booking.currency}</p>
                <p style="font-size:12px;color:#64748b;margin-top:8px;">Confirmation sent to your email.</p>
            `;
            btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Booked!';
            btn.style.background = '#10b981';
        } else {
            throw new Error(data.message);
        }
    } catch (err) {
        resultDiv.className = 'modal-result error';
        resultDiv.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> ' + err.message;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Confirm Booking';
        btn.disabled = false;
    }
});

// ---- Navbar scroll effect ----
window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 50);
});

// ---- Mobile menu ----
document.getElementById('mobileToggle').addEventListener('click', () => {
    document.getElementById('navLinks').classList.toggle('active');
});

// ---- Smooth scroll ----
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) target.scrollIntoView({ behavior: 'smooth' });
        document.getElementById('navLinks').classList.remove('active');
    });
});

// ---- Default date to tomorrow ----
const tomorrow = new Date();
tomorrow.setDate(tomorrow.getDate() + 1);
document.getElementById('tourDate').valueAsDate = tomorrow;
</script>

</body>
</html>
