# Collins M Phone Store

A dynamic PHP and MySQL phone catalogue for Samsung Galaxy and Apple iPhone devices. Customers can filter the live stock, read product details, submit moderated reviews, and contact Collins M by call or WhatsApp. Administrators manage the catalogue from a protected back office.

## Run with XAMPP

1. Copy this folder to `C:\xampp\htdocs\portfolio` (or point Apache to this folder).
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Open phpMyAdmin at `http://localhost/phpmyadmin`, choose **Import**, and import `database.sql` from this project. This creates `collins_portfolio`, its tables, categories, and a small starter catalogue.
4. Visit `http://localhost/portfolio/`.
5. Visit `http://localhost/portfolio/register.php` once to create the first administrator. Registration automatically closes to the public after the first account is created.
6. Sign in at `http://localhost/portfolio/login.php` and manage the catalogue at `http://localhost/portfolio/admin/`.

For MySQL Workbench, create a connection to your local MySQL server, then use **File → Run SQL Script** and choose `database.sql`.

## Configuration

The local XAMPP credentials are set in `config/database.php` (`root` and an empty password by default). Change them there if your MySQL setup differs. Do not commit production credentials; use environment-specific values in production.

## Features

- Live PHP/MySQL product list with search, brand, price, condition, and price-order filters
- Dedicated product pages, image gallery, colours, technical details, stock indicators, related phones, and KSh pricing
- Call and WhatsApp actions throughout the site; WhatsApp points to the supplied Collins M link
- Password-hashed, session-protected admin account and CSRF checks on state-changing forms
- Product CRUD, validated image uploads, categories, stock updates, and review moderation
- Customer review submission with duplicate throttling and approval workflow

## E-commerce upgrade

For an existing installation, do **not** re-import `database.sql` (that file is for a fresh database). Back up `collins_portfolio`, then apply the new migration once from PowerShell:

```powershell
& 'C:\xampp\mysql\bin\mysql.exe' --user=root --host=localhost --execute="$(Get-Content -Raw .\migrations\20261101_ecommerce_upgrade.sql)"
```

The migration adds customer accounts/password resets, wishlists, recently viewed products, status/audit history, flash sales, website settings, stock alert tracking, and payment transaction records. It extends the order status values and adds delivery and inventory-restoration fields. Existing products, customers, orders and line items are retained.

New storefront routes: `account.php`, `forgot-password.php`, `reset-password.php`, and `track-order.php`. Customer registration is separate from the existing protected administrator login. Guest checkout remains available. Account order history is matched to checkout email addresses; guest tracking requires the order reference and checkout phone number.

Admin routes: **Orders**, **Flash Sales**, and **Website Customisation** are available from the back office. Only unpaid Pending orders can be deleted; inventory is restored and a full order snapshot is written to `admin_audit_log`. Other orders should be cancelled through status management so that history remains. Status changes and supported profile edits are audited.

Flash sale dates are checked against MySQL server time on every price lookup. Customers see sale prices only while the promotion is active and before its end time. The catalogue filters use `api/catalogue.php`, prepared statements, a matching-product count, and load-more pagination. Wishlist and recently viewed data use browser storage for guests and database records for signed-in customers.

### Email and payments

Password reset email needs `COLLINS_M_BASE_URL` (for example `https://shop.example`) and `COLLINS_M_FROM_EMAIL` configured in the Apache/PHP environment, plus working PHP `mail()`/SMTP. Low-stock mail additionally needs `COLLINS_M_ADMIN_EMAIL`. Reset links expire after 30 minutes and their tokens are stored as hashes.

BNPL is **not active**. Checkout continues to offer Cash on Delivery and M-Pesa on confirmation. No provider API credentials or merchant agreement were available, so no financing plan, payment initiation, webhook success, or simulated payment is exposed. `payment_transactions` is only the storage foundation; a verified provider integration and callback validation still need implementing. The official [Lipa Later API documentation](https://docs.lipalater.com/) describes customer-specific eligibility and instalment-plan endpoints; confirm current merchant onboarding, charges, terms, and production API details with the provider before enabling. The Central Bank of Kenya's [September 2026 digital credit provider directory](https://www.centralbank.go.ke/wp-content/uploads/2026/09/Directory-of-Digital-Credit-Providers-September-2026.pdf) lists Lipa Later Limited as licensed. This listing alone does not establish a Collins M merchant integration.

### Verification

After the migration, test locally with Apache/MySQL started in XAMPP and open `http://localhost/portfolio/`:

1. Browse and search catalogue products; adjust brand, category, storage, quality, condition, stock and price filters without a full page reload; try **Clear filters** and **Load more**.
2. Open product details, verify related items and wishlist buttons, visit several products, then check the recent-view strip on the homepage.
3. Add an item to cart and submit guest checkout using test contact details. Verify the order reference, stock decrement and guest tracking using the reference plus checkout phone.
4. Register a customer account, sign in, save/unsave a product, and confirm order history and wishlist. Configure PHP mail before testing password reset delivery.
5. Sign in to the admin area; update an order status and ETA, inspect its audit/status history, and verify delete is offered only for unpaid Pending orders with a confirmation dialog.
6. Create a short flash sale and check start/end behavior and countdown. Change the low-stock threshold and verify the admin alert list.
7. Test mobile widths and keyboard navigation. BNPL should remain labelled unavailable until an official provider integration is configured and verified.

No Figma file was created. See [`DESIGN-SYSTEM.md`](DESIGN-SYSTEM.md) for the Figma-ready colours, typography, components, desktop/mobile references, breakpoints, and implementation mapping.

Uploads are saved under `uploads/products/`; ensure the web server can write to that folder. The database intentionally contains no customer reviews, so no fake testimonials are shown.

## Existing database update and password recovery

- For an already-imported database, run `migrations/20261021_catalogue_images.sql` once to link the supplied local model artwork to the starter products.
- If an existing administrator has a broken password hash, use `http://localhost/portfolio/admin-reset.php` from the same computer running XAMPP. Enter the administrator's existing email and a new 10+ character password. The page rejects non-local requests; remove `admin-reset.php` as soon as recovery is complete.
- The obsolete password verification file has been removed because it exposed a real email and password in the project source.
# Portfolio


## Collins M AI chatbot

The website now includes a floating chat widget and a same-origin PHP endpoint at `api/chat.php`. The endpoint forwards chat requests from PHP to n8n, so the browser does not need cross-origin access and no OpenAI key is exposed in the page.

1. In n8n, configure the **Ask OpenAI** node with your API key, save, and activate the workflow.
2. The default PHP endpoint configuration is `http://127.0.0.1:5678/webhook/collins-m-chat`, appropriate when Apache on Windows can reach your WSL n8n service on localhost port 5678. If n8n is on another host or port, set the `COLLINS_M_CHAT_WEBHOOK` environment variable for Apache/PHP to the active production webhook URL.
3. Ensure PHP's cURL extension is enabled. In XAMPP, restart Apache after changing PHP configuration or environment variables.
4. Visit `http://localhost/portfolio/`, open **Chat with us**, and send a message. The workflow must be active for the production URL. For a one-time n8n test, temporarily set the webhook setting to the node's `/webhook-test/collins-m-chat` URL and click **Execute workflow** before sending.

The conversation is held only in page memory and is not stored by the site. n8n may retain execution data according to its own settings.
