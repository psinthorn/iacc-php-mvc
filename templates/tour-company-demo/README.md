# 🚤 Tour Company Demo — iACC Template

A professional tour operator website template showcasing **iACC Sales Channel API** integration. Built for speedboat tour operators, travel agencies, and activity booking businesses.

## Preview

Open `index.html` in your browser to preview the template.

## Features

- ✅ Responsive design (mobile, tablet, desktop)
- ✅ Modern UI with ocean-blue theme
- ✅ Tour package cards with pricing
- ✅ Destination showcase gallery
- ✅ Booking form with tour selection
- ✅ iACC API integration ready
- ✅ Smooth scroll navigation
- ✅ Contact section with social links
- ✅ SEO-friendly structure
- ✅ No build tools required — pure HTML/CSS/JS

## iACC API Integration

This template includes ready-to-use JavaScript code for connecting to the **iACC Sales Channel API**. The API allows you to:

1. **Create Orders** — Submit booking requests directly to iACC
2. **Generate Invoices** — Automatic invoice creation from orders
3. **Accept Payments** — Multiple payment methods (bank transfer, PromptPay, credit card)
4. **Track Status** — Real-time order and payment status

### Setup

1. Sign up at [iacc.f2.co.th](https://iacc.f2.co.th)
2. Create a company profile
3. Get your API credentials from Settings → API
4. Update the API configuration in `index.html`:

```javascript
const IACC_API = {
    endpoint: 'https://iacc.f2.co.th/api/v1',
    apiKey: 'YOUR_API_KEY',      // ← Replace
    apiSecret: 'YOUR_API_SECRET' // ← Replace
};
```

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/orders` | Create new booking order |
| GET | `/api/v1/orders/:id` | Get order details |
| GET | `/api/v1/invoices/:id` | Get invoice details |
| POST | `/api/v1/payments` | Record payment |

## File Structure

```
tour-company-demo/
├── index.html       # Main template file
├── css/
│   └── style.css    # Template styles
└── README.md        # This file
```

## Customization

### Colors
Edit CSS variables in `css/style.css`:
```css
:root {
    --ocean: #0369a1;      /* Primary color */
    --teal: #0d9488;       /* Secondary color */
    --sky: #06b6d4;        /* Accent color */
    --coral: #f43f5e;      /* Badge/alert color */
}
```

### Tour Packages
Edit the tour cards in the `#tours` section of `index.html`. Each card follows this structure:
```html
<div class="tour-card">
    <div class="tour-image" style="background-image:url('YOUR_IMAGE');">
        <span class="tour-type"><i class="fa-solid fa-ship"></i> Type</span>
    </div>
    <div class="tour-body">
        <h3>Tour Name</h3>
        <p>Description</p>
        <div class="tour-footer">
            <div class="tour-price">฿1,900 <small>/person</small></div>
            <button class="btn btn-primary btn-book" data-tour="Tour Name" data-price="1900">Book</button>
        </div>
    </div>
</div>
```

## Demo Data

This template comes with sample data for **Tour Company Demo** (company_id: 244) in iACC:

- **19 tour packages** from ฿350 to ฿25,000
- **5 sample quotations** with real line items
- **4 payment terms** (Cash, Bank Transfer, Credit Card, PromptPay)
- **3 payment methods** configured

## License

Free to use for iACC customers. Template by [iACC](https://iacc.f2.co.th).

---

**Need help?** Contact support at iacc.f2.co.th or check the API documentation.
