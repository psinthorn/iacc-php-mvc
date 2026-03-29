# iACC Tour Company Template

A complete website template that connects to the **iACC Sales Channel API** to display products and accept bookings.

## Quick Start

1. Upload all files to your web hosting (PHP 8.0+ with SQLite required)
2. Open `setup.php` in your browser
3. Enter your iACC API Key and Secret
4. Test the connection
5. Sync your products
6. Done - your website is live

## File Structure

- `index.php` - Dynamic homepage (reads products from SQLite)
- `setup.php` - 3-step setup wizard
- `sync.php` - Re-sync products from iACC API
- `book.php` - Booking handler, creates orders via API
- `config.php` - Auto-generated configuration
- `.htaccess` - Security rules
- `css/style.css` - Theme stylesheet
- `includes/api-client.php` - IaccApiClient, cURL wrapper for iACC API
- `includes/database.php` - LocalDatabase, SQLite cache for products
- `data/template.db` - SQLite database (auto-created)

## How It Works

### Setup Flow

Customer downloads template, uploads to hosting, opens setup.php,
enters API Key and Secret, tests connection, syncs products,
website goes live with real products from iACC.

### Booking Flow

Website visitor clicks Book, fills booking form, submits.
book.php sends POST to iACC API /v1/orders.
iACC creates: channel_order, Customer, PR, PO, Product.
Visitor sees confirmation with order reference.

## API Endpoints Used

- `GET /api.php/v1/subscription` - Test connection, get plan info
- `GET /api.php/v1/products` - Fetch product catalog
- `GET /api.php/v1/categories` - Fetch categories with types
- `POST /api.php/v1/orders` - Create booking (PR, PO pipeline)

## Requirements

- PHP 8.0+
- SQLite3 extension
- cURL extension
- Write permissions for `config.php` and `data/` directory

## Customization

- **Theme color** - Set during setup (Step 3)
- **Site title** - Set during setup (Step 3)
- **CSS** - Edit `css/style.css` for full design control
- **Layout** - Edit `index.php` for structure changes

## Security

- `.htaccess` blocks direct access to `config.php`, `data/`, and `includes/`
- API credentials stored server-side only
- SQLite database not web-accessible

## License

Part of the iACC Template System. 2026 iACC.
