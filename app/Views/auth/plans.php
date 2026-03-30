<?php
/**
 * Plan Comparison & Upgrade Page
 * Shows subscription plans after trial activation
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Plan - iACC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        :root { --primary: #8e44ad; --primary-dark: #6c3483; --dark: #2c3e50; --success: #27ae60; --gray-600: #6c757d; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #f8f9fa;
            color: var(--dark);
        }
        .header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 { font-size: 2rem; margin-bottom: 8px; }
        .header p { opacity: 0.9; font-size: 1rem; }
        .header .nav-links { margin-top: 15px; }
        .header .nav-links a { color: rgba(255,255,255,0.8); text-decoration: none; margin: 0 15px; font-size: 0.9rem; }
        .header .nav-links a:hover { color: white; }

        .plans-container {
            max-width: 1100px;
            margin: -30px auto 60px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .plan-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 30px;
            text-align: center;
            position: relative;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }
        .plan-card.recommended {
            border: 2px solid var(--primary);
        }
        .plan-card.recommended::before {
            content: 'POPULAR';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary);
            color: white;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .plan-card.current { background: #f8f4fb; }
        .plan-card.current::after {
            content: 'CURRENT';
            position: absolute;
            top: 12px;
            right: 12px;
            background: var(--success);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 700;
        }

        .plan-icon {
            width: 50px; height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 22px;
        }
        .plan-trial .plan-icon { background: #e8f8f5; color: #1abc9c; }
        .plan-starter .plan-icon { background: #ebf5fb; color: #3498db; }
        .plan-professional .plan-icon { background: #f4ecf7; color: #8e44ad; }
        .plan-enterprise .plan-icon { background: #fef9e7; color: #f39c12; }

        .plan-name { font-size: 1.1rem; font-weight: 700; margin-bottom: 5px; }
        .plan-price { font-size: 2rem; font-weight: 700; color: var(--primary); margin: 10px 0; }
        .plan-price span { font-size: 0.9rem; font-weight: 400; color: var(--gray-600); }
        .plan-duration { color: var(--gray-600); font-size: 0.8rem; margin-bottom: 20px; }

        .plan-features {
            list-style: none;
            text-align: left;
            margin-bottom: 25px;
        }
        .plan-features li {
            padding: 6px 0;
            font-size: 0.82rem;
            color: #555;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .plan-features li i.fa-check { color: var(--success); }
        .plan-features li i.fa-times { color: #ccc; }

        .btn-plan {
            display: block;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            text-decoration: none;
            text-align: center;
            font-family: inherit;
        }
        .btn-plan:hover { transform: translateY(-1px); }
        .btn-plan-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }
        .btn-plan-outline {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        .btn-plan-disabled {
            background: var(--success);
            color: white;
            cursor: default;
        }

        .comparison-section {
            max-width: 1100px;
            margin: 0 auto 60px;
            padding: 0 20px;
        }
        .comparison-section h2 { text-align: center; margin-bottom: 25px; color: var(--dark); }
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .comparison-table th, .comparison-table td {
            padding: 12px 16px;
            text-align: center;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.85rem;
        }
        .comparison-table th { background: #f8f9fa; font-weight: 600; color: var(--dark); }
        .comparison-table td:first-child { text-align: left; font-weight: 500; }
        .comparison-table .fa-check { color: var(--success); }
        .comparison-table .fa-times { color: #ddd; }

        .back-link { text-align: center; margin: 30px 0; }
        .back-link a { color: var(--primary); text-decoration: none; font-weight: 600; }

        @media (max-width: 900px) {
            .plans-container { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 600px) {
            .plans-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Choose Your Plan</h1>
        <p>Start with a free trial, upgrade anytime as your business grows</p>
        <div class="nav-links">
            <a href="index.php?page=dashboard"><i class="fa fa-arrow-left"></i> Dashboard</a>
        </div>
    </div>

    <div class="plans-container">
        <!-- Trial -->
        <div class="plan-card plan-trial current">
            <div class="plan-icon"><i class="fa fa-gift"></i></div>
            <div class="plan-name">Trial</div>
            <div class="plan-price">Free <span>/ 14 days</span></div>
            <div class="plan-duration">No credit card required</div>
            <ul class="plan-features">
                <li><i class="fa fa-check"></i> 50 orders/month</li>
                <li><i class="fa fa-check"></i> 1 API key</li>
                <li><i class="fa fa-check"></i> Website channel</li>
                <li><i class="fa fa-check"></i> Ollama AI (local)</li>
                <li><i class="fa fa-times"></i> Email channel</li>
                <li><i class="fa fa-times"></i> LINE / Facebook</li>
            </ul>
            <button class="btn-plan btn-plan-disabled" disabled>Current Plan</button>
        </div>

        <!-- Starter -->
        <div class="plan-card plan-starter">
            <div class="plan-icon"><i class="fa fa-rocket"></i></div>
            <div class="plan-name">Starter</div>
            <div class="plan-price">฿990 <span>/ month</span></div>
            <div class="plan-duration">30-day billing cycle</div>
            <ul class="plan-features">
                <li><i class="fa fa-check"></i> 500 orders/month</li>
                <li><i class="fa fa-check"></i> 3 API keys</li>
                <li><i class="fa fa-check"></i> Website + Email</li>
                <li><i class="fa fa-check"></i> Ollama + OpenAI</li>
                <li><i class="fa fa-times"></i> LINE / Facebook</li>
                <li><i class="fa fa-times"></i> Priority support</li>
            </ul>
            <a href="index.php?page=api_upgrade&plan=starter" class="btn-plan btn-plan-outline">Upgrade</a>
        </div>

        <!-- Professional -->
        <div class="plan-card plan-professional recommended">
            <div class="plan-icon"><i class="fa fa-diamond"></i></div>
            <div class="plan-name">Professional</div>
            <div class="plan-price">฿2,490 <span>/ month</span></div>
            <div class="plan-duration">30-day billing cycle</div>
            <ul class="plan-features">
                <li><i class="fa fa-check"></i> 5,000 orders/month</li>
                <li><i class="fa fa-check"></i> 10 API keys</li>
                <li><i class="fa fa-check"></i> All channels</li>
                <li><i class="fa fa-check"></i> All AI providers</li>
                <li><i class="fa fa-check"></i> LINE + Facebook</li>
                <li><i class="fa fa-check"></i> Priority support</li>
            </ul>
            <a href="index.php?page=api_upgrade&plan=professional" class="btn-plan btn-plan-primary">Upgrade</a>
        </div>

        <!-- Enterprise -->
        <div class="plan-card plan-enterprise">
            <div class="plan-icon"><i class="fa fa-building"></i></div>
            <div class="plan-name">Enterprise</div>
            <div class="plan-price">Custom</div>
            <div class="plan-duration">Annual billing</div>
            <ul class="plan-features">
                <li><i class="fa fa-check"></i> Unlimited orders</li>
                <li><i class="fa fa-check"></i> Unlimited API keys</li>
                <li><i class="fa fa-check"></i> All channels</li>
                <li><i class="fa fa-check"></i> All AI providers</li>
                <li><i class="fa fa-check"></i> Dedicated support</li>
                <li><i class="fa fa-check"></i> Custom integrations</li>
            </ul>
            <a href="contact.php" class="btn-plan btn-plan-outline">Contact Sales</a>
        </div>
    </div>

    <!-- Feature Comparison Table -->
    <div class="comparison-section">
        <h2>Feature Comparison</h2>
        <table class="comparison-table">
            <thead>
                <tr>
                    <th>Feature</th>
                    <th>Trial</th>
                    <th>Starter</th>
                    <th>Professional</th>
                    <th>Enterprise</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Orders per month</td>
                    <td>50</td>
                    <td>500</td>
                    <td>5,000</td>
                    <td>Unlimited</td>
                </tr>
                <tr>
                    <td>API Keys</td>
                    <td>1</td>
                    <td>3</td>
                    <td>10</td>
                    <td>Unlimited</td>
                </tr>
                <tr>
                    <td>Duration</td>
                    <td>14 days</td>
                    <td>30 days</td>
                    <td>30 days</td>
                    <td>365 days</td>
                </tr>
                <tr>
                    <td>Invoicing & Quotations</td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                </tr>
                <tr>
                    <td>Expense Tracking</td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                </tr>
                <tr>
                    <td>PDF Generation</td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                </tr>
                <tr>
                    <td>Multi-Currency</td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                </tr>
                <tr>
                    <td>Thai Tax Reports (VAT/WHT)</td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                </tr>
                <tr>
                    <td>Website Channel</td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                </tr>
                <tr>
                    <td>Email Channel</td>
                    <td><i class="fa fa-times"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                </tr>
                <tr>
                    <td>LINE / Facebook</td>
                    <td><i class="fa fa-times"></i></td>
                    <td><i class="fa fa-times"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                </tr>
                <tr>
                    <td>AI Chatbot (Ollama)</td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                </tr>
                <tr>
                    <td>OpenAI Integration</td>
                    <td><i class="fa fa-times"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                </tr>
                <tr>
                    <td>Claude / Gemini AI</td>
                    <td><i class="fa fa-times"></i></td>
                    <td><i class="fa fa-times"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                </tr>
                <tr>
                    <td>Webhooks</td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                    <td><i class="fa fa-check"></i></td>
                </tr>
                <tr>
                    <td>Custom Integrations</td>
                    <td><i class="fa fa-times"></i></td>
                    <td><i class="fa fa-times"></i></td>
                    <td><i class="fa fa-times"></i></td>
                    <td><i class="fa fa-check"></i></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="back-link">
        <a href="index.php?page=dashboard"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>
