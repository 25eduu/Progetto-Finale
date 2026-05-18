# TechShop - E-Commerce Platform

## 📋 Descrizione generale
**TechShop** è un'applicazione e-commerce in **PHP 8+** con architettura **MVC personalizzata** e database **MySQL/MariaDB**. La piattaforma include:
- autenticazione locale e Google OAuth
- **2FA via email**
- gestione carrello con controlli stock sicuri
- checkout con **Stripe**
- pannello amministrativo per prodotti, ordini e utenti
- invio email tramite **PHPMailer**

## 🚀 Funzionalità principali

### Autenticazione
- registrazione locale con password hashed
- login con email/password
- login con **Google OAuth**
- **two-factor authentication (2FA)** via email
- remember-me persistente con token sicuro
- gestione sessione utente

### Carrello e checkout
- carrello persistente in DB per utenti loggati
- carrello in sessione per ospiti
- verifica stock in transazioni con `LOCK IN SHARE MODE`
- aggiornamento quantità con validazione stock
- checkout Stripe e webhook per conferma ordine
- riduzione stock sicura dopo pagamento
- supporto pagamento wallet misto

### Amministrazione
- gestione prodotti
- gestione ordini con stati
- gestione utenti e ruoli

### Sicurezza
- validazione input lato server con `ValidationHelper`
- protezione CSRF con `CsrfHelper`
- query PDO con placeholder per prevenire SQL injection
- verifica Google OAuth `state`
- gestione errori centralizzata tramite `ErrorHandler`
- validazione upload file per admin
- output HTML sanitizzato con `htmlspecialchars`
- rate limiting per i tentativi di login

## 🏗️ Architettura del progetto

```
app/
  controllers/
  models/
  views/
  helpers/
  middleware/
  services/
config/
  config.php
  database.php
  mail.php
  stripe.php
database/
  ecommerce.sql
public/
  index.php
  assets/
vendor/
README.md
composer.json
```

## ⚙️ Requisiti e installazione

### Prerequisiti
- PHP 8+ con PDO/MySQL
- MySQL/MariaDB
- Composer
- Server web Apache o NGINX
- SMTP funzionante per invio email

### Installazione
```bash
composer install
cp .env.example .env
```

### Configurazione `.env`
Definisci questi valori nel file `.env`:
```env
DB_HOST=127.0.0.1
DB_NAME=ecommerce
DB_USER=root
DB_PASS=

GOOGLE_CLIENT_ID=

STRIPE_SECRET_KEY=
STRIPE_WEBHOOK_SECRET=

MAIL_FROM_EMAIL=info@techshop.it
MAIL_FROM_NAME=TechShop
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
SMTP_USER=...
SMTP_PASS=...
```

### Importare il database
```bash
mysql -u root -p ecommerce < database/ecommerce.sql
```

### Avvio applicazione
- Copia il progetto nella cartella web di Apache (es. `htdocs/Progetto-Finale`)
- Accedi a `http://localhost/Progetto-Finale/public`

## 🗄️ Schema database aggiornato

### Tabelle chiave

#### `users`
- id
- email
- google_id
- auth_provider
- password
- full_name
- wallet_balance
- role
- remember_token
- created_at
- email_verified_at

#### `products`
- id
- category_id
- name
- description
- price
- stock
- image_path
- created_at

#### `cart`
- id
- user_id
- product_id
- quantity

#### `orders`
- id
- user_id
- customer_name
- customer_email
- total_amount
- status
- payment_method
- wallet_amount_paid
- stripe_amount_paid
- paypal_amount_paid
- stripe_session_id
- paypal_order_id
- payment_status
- notes
- created_at

#### `order_items`
- id
- order_id
- product_id
- quantity
- unit_price

#### `two_factor_codes`
- id
- user_id
- otp_code
- expires_at
- is_used

#### `user_sessions`
- id
- user_id
- token
- ip_address
- last_activity

#### altre tabelle utili
- `categories`
- `product_specs`
- `related_products`
- `wallet_logs`

## 💡 Miglioramenti suggeriti

1. Aggiungere il flusso di **reset password via email**.
2. Abilitare la **verifica email** dopo la registrazione.
3. Implementare **test automatizzati** per controller, repository e servizi.
4. Aggiungere **paginazione e filtri** per la lista prodotti.
5. Migliorare la **documentazione API/AJAX**.
6. Aggiungere **dashboard analytics** nel pannello admin.
7. Introdurre un sistema di **logging centralizzato** per errori e azioni utente.
8. Usare **PSR-4 / namespace** e un autoloading più modulare, se possibile.
9. Ottimizzare le query per liste e ricerca prodotti.
10. Rendere l’accesso più pulito configurando un **virtual host** in modo da non mostrare `public/` nell’URL.

## 📌 Note finali
Questo README è stato aggiornato per riflettere il database `database/ecommerce.sql` e le funzionalità attuali del progetto.
