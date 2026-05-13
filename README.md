# TechShop - E-Commerce Platform

## 📋 Descrizione Generale
**TechShop** è un'applicazione e-commerce full-stack sviluppata in **PHP 8+ con MySQL**, che implementa un'architettura **MVC personalizzata**. La piattaforma offre una gestione completa dei prodotti, un sistema di carrello avanzato, pagamenti sicuri via **Stripe**, autenticazione con **Google OAuth 2.0** e **2FA**, e un pannello amministrativo completo.

### Obiettivi Principali
- ✅ Vendita di prodotti tecnologici online
- ✅ Gestione sicura di autenticazione e transazioni
- ✅ Esperienza utente reattiva e responsive
- ✅ Amministrazione centralizzata di prodotti e ordini
- ✅ Conformità alle best practices di sicurezza

---

## 🏗️ Architettura

### Pattern: MVC (Model-View-Controller)
```
app/
├── controllers/       # Logica applicativa
│   ├── BaseController.php      # Classe base con metodi comuni
│   ├── AuthController.php      # Autenticazione, login, registrazione
│   ├── CartController.php      # Gestione carrello
│   ├── CheckoutController.php  # Checkout e pagamenti
│   └── AdminXxxController.php  # Pannello amministrativo
├── models/           # Logica di accesso ai dati (DB)
├── views/            # Template HTML/PHP
├── helpers/          # Utility: validazione, CSRF, rate limiting
├── middleware/       # Autenticazione e autorizzazione
└── services/         # Logica di dominio: email, pagamenti
```

### Flusso di Richiesta
```
index.php (entry point)
    ↓
Router (analizza URL e carica controller)
    ↓
Controller (elabora richiesta)
    ↓
Model (accede al database)
    ↓
View (renderizza HTML)
    ↓
Risposta HTTP/JSON
```

---

## 🔐 Sicurezza - Implementazioni

### 1. **Error Handling Globale** (`ErrorHandler.php`)
```php
// Registrato in index.php all'avvio
ErrorHandler::register();

// Gestisce:
// - Eccezioni non catturate
// - Errori PHP convertiti in eccezioni
// - Mostra stack trace in development, nasconde in production
```
**Protegge contro:** Information disclosure, crash applicazione

### 2. **CSRF Protection** (`CsrfHelper.php`)
```php
// Nel form: genera e inserisce token nascosto
<?= CsrfHelper::field() ?>

// Nel controller: valida prima di modificare dati
CsrfHelper::validate(); // Lancia eccezione se invalido

// AJAX: CsrfHelper::validateAjax() legge header X-CSRF-Token
```
**Protegge contro:** Attacchi CSRF (Cross-Site Request Forgery)

### 3. **Validazione Password Robusta** (`ValidationHelper.php`)
```php
// Requisiti: 8+ caratteri, maiuscola, minuscola, numero, speciale
ValidationHelper::password("MyPass@123");  // ✓ Valido
ValidationHelper::password("password");    // ✗ Manca numero/speciale

// Implementato in AuthController::register()
```
**Protegge contro:** Brute force, password deboli

### 4. **Rate Limiting** (`RateLimitHelper.php`)
```php
// Max 5 tentativi di login in 300 secondi
if (!RateLimitHelper::isAllowed('login_attempt', 5, 300)) {
    Flash::error('Troppi tentativi. Riprova tra 5 minuti.');
}
RateLimitHelper::reset('login_attempt'); // Reset dopo successo
```
**Protegge contro:** Brute force attack, bot spam

### 5. **Validazione File Upload** (`AdminProductController.php`)
```php
// Controlli multipli:
// 1. Dimensione massima: 5MB
// 2. MIME type reale con finfo_file() (non solo estensione)
// 3. Estensione da whitelist
// 4. Genera filename unico con uniqid()

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!ValidationHelper::fileExtension($file['name'], ['jpg', 'jpeg', 'png', 'webp'])) {
    return null; // Rifiuta
}
```
**Protegge contro:** File upload dannosi, esecuzione codice

### 6. **SQL Injection Prevention** (Parametri Preparati)
```php
// ✓ SICURO: usa placeholder ?
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);

// ✗ PERICOLOSO: concatenazione diretta
$sql = "SELECT * FROM users WHERE email = '" . $email . "'";
```
**Protegge contro:** SQL Injection

### 7. **Google OAuth con State Token** (`AuthController.php`)
```php
// Genera state token per prevenire CSRF su OAuth
$state = bin2hex(random_bytes(32));
$_SESSION['google_oauth_state'] = $state;

// Verifica state nel callback
if (!hash_equals($stateFromRequest, $stateFromSession)) {
    Flash::error('State token non valido.');
}
```
**Protegge contro:** CSRF su OAuth callback

### 8. **Password Hashing Sicuro**
```php
// Registrazione
$hash = password_hash($password, PASSWORD_DEFAULT); // bcrypt con salt random

// Login
if (!password_verify($inputPassword, $storedHash)) {
    Flash::error('Credenziali non valide.');
}
```
**Protegge contro:** Rainbow table attack, accesso ai dati

### 9. **Cookie Remember-Me Sicuro**
```php
// Genera token casuale lungo 64 caratteri
$token = bin2hex(random_bytes(32));

// Salva hash SHA-256 nel DB (non il token in chiaro)
hash('sha256', $token)

// Cookie con flag di sicurezza
setcookie('remember_token', $token, [
    'expires'  => time() + 2592000,  // 30 giorni
    'httponly' => true,              // Non accessibile da JS
    'samesite' => 'Lax',             // CSRF protection
]);
```
**Protegge contro:** Token theft, cookie manipulation

### 10. **Two-Factor Authentication (2FA)**
```php
// Genera OTP a 6 cifre, scade dopo 10 minuti
$code = (string)random_int(100000, 999999);
$expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Invia via email con PHPMailer
$mailService->sendTwoFactorCode($email, $fullName, $code);
```
**Protegge contro:** Account takeover

### 11. **Sanitizzazione Output** (Nelle Views)
```php
// ✓ SICURO: escapa HTML
<h1><?= htmlspecialchars($product['name'], ENT_QUOTES) ?></h1>

// ✗ PERICOLOSO: XSS vulnerability
<h1><?= $product['name'] ?></h1>
```
**Protegge contro:** XSS (Cross-Site Scripting)

---

## 🗄️ Database Schema

### Tabelle Principali

#### `users`
```sql
id, email, password, full_name, wallet_balance, role, 
google_id, auth_provider, email_verified_at, created_at
```

#### `products`
```sql
id, category_id, name, description, price, stock, 
image_path, created_at, updated_at
```

#### `cart`
```sql
id, user_id, product_id, quantity, added_at
```

#### `orders`
```sql
id, user_id, customer_email, customer_name, total_amount, 
status, payment_status, stripe_payment_id, created_at
```

#### `order_items`
```sql
id, order_id, product_id, quantity, price_at_purchase
```

#### `user_sessions` (Remember-Me)
```sql
id, user_id, token, ip_address, last_activity
```

#### `two_factor_codes` (2FA)
```sql
id, user_id, otp_code, expires_at, is_used
```

---

## 🚀 Funzionalità Principali

### Autenticazione
1. **Registrazione locale** con validazione password robusta
2. **Login con email/password** o **Google OAuth**
3. **Two-Factor Authentication (2FA)** via email
4. **Remember-Me** (auto-login per 30 giorni)
5. **Logout** con pulizia della sessione

### Gestione Carrello
1. **Aggiunta prodotti** con verifica stock
2. **Aggiornamento quantità** con validazione
3. **Rimozione prodotti**
4. **Mini-cart offcanvas** in tempo reale (AJAX)
5. **Persisted in DB** per utenti loggati, **in sessione** per ospiti

### Checkout e Pagamenti
1. **Integrazione Stripe** per pagamenti con carta
2. **Verifica stock** prima della creazione ordine
3. **Riduzione automatica stock** dopo pagamento
4. **Email di conferma** con PHPMailer
5. **Webhook Stripe** per gestire pagamenti da altri canali

### Pannello Admin
1. **CRUD prodotti** (Create, Read, Update, Delete)
2. **Import CSV** prodotti in batch
3. **Gestione stock** in tempo reale
4. **Visualizzazione ordini** filtrati per stato
5. **Gestione utenti** (visualizzazione, ruoli)
6. **Ricarica Wallet** utenti con Stripe

---

## 📁 Struttura File Importanti

### `public/index.php` - Entry Point
```php
// 1. Carica config e dipendenze
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Registra error handler
ErrorHandler::register();

// 3. Router: legge URL e carica controller
$controller = new $controllerClass($pdo);
$controller->$action();
```

### `app/controllers/BaseController.php` - Classe Base
Metodi comuni a tutti i controller:
```php
protected function getUserId(): ?int
protected function isAuthenticated(): bool
protected function isAdmin(): bool
protected function getCartItems(): array
protected function getCartTotal(array $items): float
protected function jsonResponse(array $data): void
protected function redirect(string $url): void
```

### `app/helpers/` - Utility Helper
1. **ValidationHelper.php** - Validazione input (email, password, numeri, file)
2. **CsrfHelper.php** - CSRF token generation e validazione
3. **RateLimitHelper.php** - Rate limiting per brute force protection
4. **Flash.php** - Messaggi flash (successo/errore) in sessione
5. **ErrorHandler.php** - Gestione errori globale

### `app/services/` - Logica di Dominio
1. **MailService.php** - Invio email (conferme ordini, 2FA)
2. **StripeService.php** - Integrazione Stripe per pagamenti

### `app/models/` - Accesso ai Dati
1. **User.php** - Gestione utenti
2. **Product.php** - Gestione prodotti
3. **Cart.php** - Gestione carrello
4. **Order.php** - Gestione ordini

---

## 🔄 Flussi Principali

### Flusso Registrazione
```
1. Form registrazione (register.php)
2. Validazione (ValidationHelper)
3. Hash password (password_hash)
4. Insert utente in DB
5. Auto-login utente
6. Redirect home
```

### Flusso Login con 2FA
```
1. Form login (login.php)
2. Verifica email/password
3. Genera OTP a 6 cifre
4. Invia email con OTP (MailService)
5. Redirect a verify2fa.php
6. Utente inserisce OTP
7. Se valido: completa login e imposta cookie remember-me
```

### Flusso Checkout
```
1. Visualizza carrello (cart/index.php)
2. Click "Procedi al checkout"
3. Stripe JavaScript tokenizza carta
4. Submit a CheckoutController::checkout()
5. Verifica stock con transazione DB
6. Crea ordine e order_items
7. Riduce stock prodotti
8. Svuota carrello
9. Invia email conferma ordine
10. Redirect pagina successo
```

### Flusso Admin Upload Prodotto
```
1. Form upload immagine (admin/products.php)
2. Validazione file (MIME type, dimensione, estensione)
3. Genera filename unico
4. Sposta file in /public/assets/images/
5. Crea record prodotto in DB
6. Redirect con messaggio successo
```

---

## 🛠️ Setup e Installazione

### Prerequisiti
- PHP 8.0+
- MySQL 5.7+
- Composer
- XAMPP (consigliato)

### Step 1: Clonare il Repository
```bash
git clone <URL_REPOSITORY>
cd Progetto-Finale
```

### Step 2: Installare Dipendenze
```bash
composer install
```

### Step 3: Creare Database
```bash
mysql -u root -p techshop < database/schema.sql
```

### Step 4: Configurare Ambiente
Crea `.env` nella root:
```
DB_HOST=localhost
DB_NAME=techshop
DB_USER=root
DB_PASS=

STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...

GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...

SMTP_HOST=smtp.gmail.com
SMTP_USER=your_email@gmail.com
SMTP_PASS=your_app_password
SMTP_PORT=587

APP_URL=http://localhost/Progetto-Finale/public
SESSION_SECRET=your_random_secret
```

### Step 5: Avviare Applicazione
```bash
# In XAMPP: Start Apache e MySQL
# Apri browser: http://localhost/Progetto-Finale/public/
```

---

## 📚 Stack Tecnologico

| Layer | Tecnologia | Descrizione |
|-------|-----------|-------------|
| **Backend** | PHP 8+ | Server-side logic |
| **Database** | MySQL 5.7+ | Data persistence |
| **Frontend** | Bootstrap 5 | Responsive UI |
| **JavaScript** | Vanilla JS | AJAX, DOM manipulation |
| **Pagamenti** | Stripe API | Processamento pagamenti |
| **Email** | PHPMailer | Invio email SMTP |
| **Auth** | Google OAuth 2.0 | Login sociale |
| **Dipendenze** | Composer | Package manager PHP |

---

## 📖 Convenzioni del Codice

### Naming
```php
// Controller: PascalCase + "Controller"
class UserController

// Metodi: camelCase
public function getUser()

// Costanti: UPPER_SNAKE_CASE
const MAX_LOGIN_ATTEMPTS = 5

// Variabili: camelCase
$userId, $cartTotal
```

### Tipo Hint Rigoroso
```php
declare(strict_types=1);  // Obbligatorio in ogni file

// Return type
public function getUserId(): ?int { }

// Parametri tipizzati
public function addToCart(int $productId, int $quantity): void { }
```

### Error Handling
```php
// Validazione: usa Flash per reindirizzare con messaggio
if (!ValidationHelper::email($email)) {
    Flash::error('Email non valida.', $redirectUrl);
}

// Exception: per errori fatali
throw new RuntimeException('Errore critico');
```

---

## 🧪 Testing Manuale

### Test Autenticazione
1. Registra nuovo utente
2. Login con email/password
3. Abilita 2FA
4. Login con Google OAuth
5. Verifica auto-login con Remember-Me

### Test Carrello
1. Aggiungi prodotti (utente loggato e ospite)
2. Modifica quantità
3. Rimuovi prodotto
4. Svuota carrello

### Test Pagamento
1. Aggiungi prodotti al carrello
2. Procedi a checkout
3. Usa test card Stripe: 4242 4242 4242 4242
4. Verifica email di conferma
5. Verifica riduzione stock

### Test Admin
1. Accedi come admin
2. Crea nuovo prodotto
3. Upload immagine
4. Verifica visualizzazione in catalogo
5. Modifica stock e prezzo

---

## 📋 Checklist di Sicurezza

- ✅ CSRF token su tutti i form POST
- ✅ Rate limiting su login
- ✅ Password hashing con bcrypt
- ✅ Validazione input (server-side)
- ✅ SQL injection prevention (parametri preparati)
- ✅ File upload validation (MIME, estensione, size)
- ✅ HTTPS cookie flags (httponly, samesite)
- ✅ Error handling globale (no stack trace in production)
- ✅ OAuth state token
- ✅ 2FA abilitato
- ✅ Sanitizzazione output (htmlspecialchars)
- ✅ Session regenerate dopo login

---

## 🐛 Debug Mode

Abilita debug in `.env`:
```
APP_ENV=development
```

Questo abilita:
- Stack trace completo negli errori
- SQL error logging
- Email logging (anziché invio reale)

Per production:
```
APP_ENV=production
```

---

## 📞 Supporto

Per issues o domande, contatta il team di sviluppo.

---

**Ultima modifica:** May 13, 2026  
**Versione:** 2.0 (Refactored for Security & Code Quality)

4. Pusha il branch: `git push origin feature/nome-feature`.
5. Apri una Pull Request.

## Licenza
Questo progetto è distribuito sotto licenza MIT. Vedi il file `LICENSE` per dettagli.

## Note Finali
- **Versione**: Questa è la versione aggiornata al 1 aprile 2026.
- **Supporto**: Per problemi, apri un issue nel repository.
- **Produzione**: In produzione, configura webhook Stripe e usa HTTPS. Rimuovi `.env` dal repository e usa variabili d'ambiente del server.

Sviluppato con ❤️ per il progetto finale.
