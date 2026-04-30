# Tour Operator Contract Management — User Guide

**For:** Tour operator admins and their assigned agents
**Reading time:** ~10 minutes

---

## Quick Tour by Role

- **You're a tour operator admin** → start at [As an Operator](#as-an-operator)
- **You're an agent selling tours from an operator** → start at [As an Agent](#as-an-agent)
- **You're a super admin** → start at [As a Super Admin](#as-a-super-admin)

---

## As an Operator

### 1. Create a Contract

1. Sidebar → **Tour Operator → Contracts**
2. Click **New Contract** (top right)
3. Fill in the **Details** tab:
   - Contract Name (e.g. "General Contract 2026")
   - Validity period (Valid From / Valid To)
   - Payment terms, credit days, deposit %
4. In **Product Types**, tick the types of products covered (e.g. Speedboat, Day Tour, Hotel)
5. Click **Save**.

After saving, three more tabs become active.

### 2. Set Pricing (Seasons & Rates)

Go to the **Seasons & Rates** tab.

There's always a **Base Rate** section — this applies whenever no season matches a booking date. You don't *have* to fill it in, but if you don't, bookings outside any season will have no rate.

Click **+ Add Season** to define a season period (e.g. "High Season", 15 Dec → 15 Jan).
- For each product, tick the checkbox to enable a rate
- Set the default Adult and Child price
- Optionally set Thai/Foreigner overrides if pricing differs by nationality
- Optionally set entrance fees
- Pick rate type: **Net Rate (฿)** or **Percentage (%)**

**Priority** matters: if two seasons overlap a booking date, the higher priority wins. Use this for special promotions.

### 3. Assign Agents

Go to the **Agents** tab.

- Pick an agent from the dropdown and click **Assign**
- The agent immediately gets that contract's products in their portal (auto-sync)
- To remove an agent: click **Remove** next to their name

### 4. Approve Agent Registrations

Sidebar → **Tour Operator → Agent Registrations**

Pending registrations show as warning cards. Click **View** on one to see the agent's profile, then:
- Optionally pick a **Default Contract** to auto-assign
- Click **Approve** — the agent gets an email and immediate portal access
- Or **Reject** with an optional reason

You can later **Suspend** approved agents (their portal access is blocked) and **Reactivate** them.

### 5. Share Documents with Agents

Sidebar → **Tour Operator → Documents**

Upload a PDF, image, or office doc (max 10MB). Choose visibility:
- **All Agents** — every approved agent of yours can download it
- **Contract Only** — only agents assigned to a specific contract see it
- **Internal Only** — your team only

### 6. View Reports

Sidebar → **Tour Operator → Contract Reports**

Three sections, each with **Email Now** to send a digest to your admin email:
- **Daily** — today's contract/agent/sync/booking activity
- **Weekly** — last 7 days, top 5 agents
- **Monthly** — current month, MoM change, top 10 products

For **automatic daily/weekly/monthly emails**, your developer needs to set up cron jobs (see [TOUR_OPERATOR_CONTRACT_V2.md](TOUR_OPERATOR_CONTRACT_V2.md#cron-cpanel)).

### 7. Resync When Things Look Wrong

If an agent reports missing products in their portal:
1. Open the contract
2. Switch to the **Sync Status** tab
3. Click **Resync Now**

The agent's catalog rebuilds within seconds.

---

## As an Agent

If you've been approved as an agent for at least one tour operator, your sidebar shows an **Agent Portal** menu.

### Dashboard

Sidebar → **Agent Portal → Dashboard**

KPI cards across the top:
- Operators (how many operators approved you)
- Active Products (synced into your catalog)
- Contracts (you're assigned to)
- Bookings 30d (recent bookings you've made)

Below: list of your operators and your most recent bookings.

### Browse Products

Sidebar → **Agent Portal → Products**

Products are grouped by operator. Use the dropdown to filter by a single operator. Each card shows:
- Product type
- Product name + description
- Source contract
- Last sync date

If something's missing, click **Resync** at the top right — this refreshes your catalog from all operators.

### View Your Contracts

Sidebar → **Agent Portal → Contracts**

Each contract card shows the operator name, validity period, payment terms. Click **View Rates** to see the rate breakdown by season for each product.

### See Bookings

Sidebar → **Agent Portal → Bookings**

A table of bookings you've created (sorted newest first), with customer name, travel date, status, and amount.

### Download Documents

Sidebar → **Agent Portal → Documents**

Files your operators have shared with you. Each card shows the document title, file size, category, and a Download button.

---

## As a Super Admin

Sidebar → **Admin → Tour Operator Platform**

This page gives you a cross-tenant view of the entire platform:

- **6 KPI cards**: active operators, active agents, contracts today, pending approvals platform-wide, sync events, bookings today
- **Send Digest Emails Now**: bulk-trigger daily/weekly/monthly digests to *every* operator's admin email at once
- **Operators table**: every operator with the tour module enabled, their agent count, contract count, and last 30d bookings
- **Pending Approvals**: pending agent registrations across all operators (so you can chase up slow operators)
- **Recent Sync Activity**: last 20 sync events with what was added/removed

You also get the **cron snippet** ready to paste into cPanel for automated email digests.

---

## Common Questions

### "I assigned an agent but they don't see the products"
Sync runs automatically on assignment, but you can force it: open the contract → **Sync Status** tab → **Resync Now**.

### "I changed the season rate but bookings are still using the old price"
Existing bookings keep the price they were quoted at. New bookings will use the latest rate. Confirm by checking `Travel Date` falls inside the new season's period.

### "An agent is showing products they shouldn't see anymore"
The sync deactivates rows (`is_active=0`) instead of deleting them — so removed products vanish from the agent UI but stay in the audit trail. If they're still visible, hit **Resync Now** on the contract.

### "Default contract — what does it do?"
When approving a new agent, picking a default contract auto-assigns them and triggers an immediate sync. Without a default, the agent is approved but has no contracts (and therefore no products) until you manually assign them on a contract.

### "Net rate vs. percentage — which do I pick?"
- **Net rate**: a fixed Baht amount per pax (most common for direct contracts)
- **Percentage**: a percentage of the operator's published price (used when agents get a % discount). The actual price calculation happens in the booking flow.

### "Can two seasons overlap?"
Yes. The pricing engine picks the one with **higher priority** number. If priorities tie, the database picks the first match — make priorities unique to be safe.

### "What if a booking falls outside every season?"
The base rate (the always-shown Base Rate section in the form) applies. If you haven't set a base rate, the booking won't have a contract price and the operator will need to enter it manually.

---

## Need More Help?

- Technical reference: [docs/TOUR_OPERATOR_CONTRACT_V2.md](TOUR_OPERATOR_CONTRACT_V2.md)
- Skill file (for developers/AI assistants): `.github/skills/tour-operator-contracts/SKILL.md`
- Issues: open one in your team's project tracker
