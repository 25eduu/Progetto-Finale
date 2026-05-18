# TechShop - Documentazione completa del progetto

Questa documentazione spiega ogni file PHP principale del progetto e il ruolo di ogni funzione. Serve a te come supporto per la presentazione, per capire rapidamente cosa fa ogni componente.

---

## Note premesse
- Ho individuato un bug reale e già corretto: l'upload immagine di `app/controllers/admin/AdminProductController.php` salvava nella directory sbagliata. Ora punta correttamente a `public/assets/images/`.
- Non ho trovato altri bug strutturali evidenti a livello di logica principale. Ci sono sempre piccole aree migliorabili, ma la maggior parte del workflow sembra funzionare correttamente.
- La documentazione include anche un suggerimento rapido su come trovare funzioni e file in VS Code.

---

## Struttura principale

### `public/index.php`
- Punto di accesso dell'applicazione.
- Inizia la sessione e carica le configurazioni e gli helper.
- Registra l'`ErrorHandler` globale.
- Esegue il `tryAutoLogin()` di `AuthController` per la funzionalità "remember me".
- Mappa la rotta `?r=controller/action` in un controller e un metodo.
- Cerca controller in sottocartelle (`admin`, `user`, `auth`, `api`, `public`) e in fallback nella root `app/controllers/`.
- Carica il controller, istanzia l'oggetto e invoca l'azione.

### `config/config.php`
- Legge il file `.env`.
- Definisce `BASE_URL` e `GOOGLE_CLIENT_ID`.
- Carica i file `config/database.php`, `config/mail.php`, `config/stripe.php`.

### `config/database.php`
- Legge la configurazione DB da `.env`.
- Crea una connessione PDO con `utf8mb4`.
- Imposta `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `EMULATE_PREPARES=false`.

### `config/mail.php`
- Legge variabili SMTP e mittente da `.env`.
- Definisce costanti come `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION`, `MAIL_USERNAME`, `MAIL_PASSWORD`.

### `config/stripe.php`
- Legge `STRIPE_SECRET_KEY` e `STRIPE_WEBHOOK_SECRET` da `.env`.

---

## Controller base

### `app/controllers/BaseController.php`
Funzioni:
- `__construct(PDO $pdo)` - salva il PDO del progetto.
- `getUserId(): ?int` - ritorna l'ID utente da sessione.
- `getUser(): ?array` - ritorna i dati utente dalla sessione.
- `isAuthenticated(): bool` - controlla se l'utente è loggato.
- `isAdmin(): bool` - controlla se l'utente ha ruolo `admin`.
- `getCartItems(): array` - ottiene gli articoli del carrello dal database tramite `Cart` se l'utente è autenticato.
- `getCartTotal(array $items = []): float` - somma il totale del carrello.
- `getCartCount(): int` - conta gli articoli del carrello.
- `render(string $viewPath, array $variables = []): void` - renderizza una vista con variabili estratte.
- `jsonResponse(array $data, int $statusCode = 200): void` - invia JSON come risposta.
- `redirect(string $url): void` - redirect HTTP.

---

## Controller autenticazione

### `app/controllers/auth/AuthController.php`
Funzioni:
- `__construct(PDO $pdo)` - imposta il PDO.
- `mergeSessionCartToDatabase(int $userId): void` - unisce il carrello in sessione con quello DB dopo login.
- `loginUser(array $user): void` - avvia sessione, salva dati utente e merge carrello.
- `setRememberMeCookie(int $userId): void` - genera token sicuro, salva hash in DB e imposta cookie.
- `clearRememberMeCookie(int $userId): void` - elimina il token dal DB e scade il cookie.
- `tryAutoLogin(): void` - tenta login automatico da cookie `remember_token`.
- `startTwoFactorLogin(array $user, bool $remember = false): void` - genera codice 2FA, lo salva in DB, invia email e redirige alla form 2FA.
- `loginForm(): void` - mostra la pagina di login.
- `registerForm(): void` - mostra la pagina di registrazione.
- `verify2faForm(): void` - mostra la pagina di verifica 2FA.
- `login(): void` - gestisce il post del login, verifica email/password, avvia 2FA.
- `register(): void` - gestisce la registrazione locale, valida dati, salva utente con password hashed e fa login.
- `logout(): void` - distrugge sessione e cancella cookie remember me.
- `verify2fa(): void` - verifica il codice 2FA, completa il login e imposta cookie se richiesto.
- `resend2fa(): void` - invia nuovamente il codice 2FA all'utente in attesa.
- `googleCallback(): void` - gestisce il callback da Google, valida `state`, verifica ID token, e login/register utente Google.
- `googleAuthStart(): void` - genera lo stato OAuth e redirige alla pagina di login Google.

---

## Controller admin

### `app/controllers/admin/AdminDashboardController.php`
Funzioni:
- `__construct(PDO $pdo)` - richiede admin tramite middleware.
- `index(): void` - carica statistiche dashboard e ordini recenti per la vista admin.

### `app/controllers/admin/AdminProductController.php`
Funzioni:
- `__construct(PDO $pdo)` - richiede admin.
- `index(): void` - mostra lista prodotti admin.
- `updateStock(): void` - aggiorna stock da admin.
- `update(): void` - aggiorna prezzo e stock prodotto.
- `create(): void` - crea un nuovo prodotto, salva immagine se presente.
- `importCsv(): void` - importa prodotti da CSV + ZIP immagini.
- `delete(): void` - elimina prodotto.
- `uploadImage(array $file): ?string` - valida e salva l'immagine in `public/assets/images/`.

### `app/controllers/admin/AdminOrderController.php`
Funzioni:
- `__construct(PDO $pdo)` - richiede admin.
- `index(): void` - mostra lista ordini filtrabile per stato.
- `updateStatus(): void` - aggiorna lo stato di un ordine.

### `app/controllers/admin/AdminUserController.php`
Funzioni:
- `__construct(PDO $pdo)` - richiede admin.
- `index(): void` - mostra la lista utenti.
- `addWallet(): void` - ricarica wallet manualmente per un utente.
- `delete(): void` - elimina un utente (con protezione per l'admin loggato).

---

## Controller public / prodotti

### `app/controllers/public/HomeController.php`
Funzioni:
- `__construct(PDO $pdo)` - imposta PDO.
- `index(): void` - mostra la home page con ultimi prodotti.

### `app/controllers/public/ProductsController.php`
Funzioni:
- `__construct(PDO $pdo)` - imposta PDO.
- `index(): void` - mostra la lista prodotti.
- `show(): void` - mostra la scheda prodotto.
- `search(): void` - mostra pagina di ricerca.
- `searchAjax(): void` - endpoint AJAX che restituisce JSON per la ricerca live.

### `app/controllers/ProductsController.php`
- File duplicate di `app/controllers/public/ProductsController.php`.
- Contiene la stessa logica, ma non è usato dal router principale grazie alla mappatura delle sottocartelle.
- È probabilmente un file legacy o duplicato e può essere ignorato per la presentazione.

---

## Controller utente

### `app/controllers/user/CartController.php`
Funzioni:
- Helper privati:
  - `getProductOrNull(int $productId): ?array` - ottiene prodotto da DB.
  - `getCurrentQuantity(int $productId): int` - legge quantità carrello sessione o DB.
  - `renderMiniCartHtml(): string` - renderizza l'HTML del mini carrello.
  - `jsonCartResponse(bool $success, ?string $message = null): void` - invia risultato JSON per AJAX.
- `index(): void` - mostra la pagina del carrello.
- `sidebar(): void` - carica il mini carrello per sidebar.
- `add(): void` - aggiunge un prodotto al carrello.
- `update(): void` - aggiorna quantità carrello.
- `remove(): void` - rimuove prodotto dal carrello.
- `addAjax(): void` - aggiunge prodotto al carrello via AJAX.
- `updateAjax(): void` - aggiorna quantità via AJAX.
- `removeAjax(): void` - rimuove prodotto via AJAX.

### `app/controllers/user/CheckoutController.php`
Funzioni:
- `__construct(PDO $pdo)` - istanzia `OrderService` e `CheckoutService`.
- `getUserId(): ?int` - ottiene l'utente loggato dalla sessione.
- `getAppUrl(): string` - ricava l'URL base dell'app.
- `index(): void` - mostra pagina checkout e controlla stock.
- `success(): void` - pagina di successo checkout, completa ordine se necessario.
- `process(): void` - gestisce il post del checkout, calcola metodi di pagamento, e reindirizza a Stripe.
- `redirectToStripe(int $orderId, float $amount, string $email, string $productName, Order $orderModel): never` - crea sessione Stripe e redirige.

### `app/controllers/user/WalletController.php`
Funzioni:
- `__construct(PDO $pdo)` - imposta PDO.
- `getUserId(): ?int` - ottiene utente loggato.
- `getAppUrl(): string` - ricava URL base.
- `logWallet(int $userId, float $amount, string $description): void` - registra una ricarica wallet.
- `recharge(): void` - avvia la ricarica wallet Stripe.
- `success(): void` - callback di successo per la ricarica wallet.

### `app/controllers/user/AccountController.php`
Funzioni:
- `__construct(PDO $pdo)` - imposta PDO.
- `requireLogin(): int` - forza l'accesso e redirige al login se necessario.
- `dashboard(): void` - mostra dashboard utente con ordini e wallet.
- `profile(): void` - mostra pagina profilo.
- `updateProfile(): void` - aggiorna il nome utente.
- `updatePassword(): void` - aggiorna la password dopo verifica.

---

## Helpers

### `app/helpers/security/CsrfHelper.php`
Funzioni:
- `generate(): string` - genera token CSRF.
- `field(): string` - ritorna campo HTML hidden con token.
- `validate(): void` - controlla token POST.
- `validateAjax(): void` - controlla token AJAX se inviato via header.

### `app/helpers/security/ErrorHandler.php`
Funzioni:
- `register(): void` - registra handler errori/exception globali.
- `handleFatal(): void` - gestisce errori fatali.
- `handleException(Throwable $exception): void` - mostra/ logga eccezioni.
- `handleError(int $errno, string $errstr, string $errfile, int $errline): bool` - converte errori PHP in eccezioni.

### `app/helpers/security/RateLimitHelper.php`
Funzioni:
- `isAllowed(string $action, int $limit = 5, int $windowSeconds = 300): bool` - controlla se sono consentiti nuovi tentativi.
- `reset(string $action): void` - resetta il contatore per l'azione.
- `getRemaining(string $action, int $limit = 5): int` - ritorna quanti tentativi restano.
- `getIdentifier(): string` - ritorna identificatore unico per l'utente/sessione.

### `app/helpers/ui/Flash.php`
Funzioni:
- `error(string $message, string $redirectUrl): never` - salva errore in sessione e redirige.
- `success(string $message, string $redirectUrl): never` - salva successo e redirige.
- `get(): array` - legge e pulisce i messaggi flash.

### `app/helpers/validation/ValidationHelper.php`
Funzioni:
- `notEmpty(string $value): bool`
- `email(string $value): bool`
- `password(string $value, int $minLength = 8): bool`
- `positiveFloat(float|string $value): bool`
- `positiveInt(int|string $value): bool`
- `between(float|int $value, float|int $min, float|int $max): bool`
- `matches(string $a, string $b): bool`
- `maxLength(string $value, int $max): bool`
- `minLength(string $value, int $min): bool`
- `fileExtension(string $filename, array $allowed = []): bool`
- `mimeType(string $mimeType, array $allowed = []): bool`

---

## Middleware

### `app/middleware/AuthMiddleware.php`
Funzioni:
- `requireUser(): int` - richiede l'accesso utente, redirige se guest.
- `requireAdmin(): int` - richiede ruolo admin.
- `requireGuest(): void` - richiede che non ci sia un utente loggato.

---

## Modelli e repository

### `app/models/repositories/Cart.php`
Funzioni:
- `__construct(PDO $pdo)` - salva PDO.
- `getItemsByUserId(int $userId): array` - recupera articoli del carrello con join su `products`.
- `addProduct(int $userId, int $productId, int $qty = 1): void` - aggiunge prodotto con transazione, `LOCK IN SHARE MODE` e controllo stock.
- `updateQuantity(int $userId, int $productId, int $qty): void` - aggiorna quantità con transazione e controllo stock.
- `removeProduct(int $userId, int $productId): void` - elimina prodotto dal carrello.
- `clear(int $userId): void` - svuota carrello.
- `countItems(int $userId): int` - conta il totale degli articoli.
- `mergeSessionCart(int $userId, array $sessionCart): void` - converte il carrello da sessione in DB dopo login.

### `app/models/entities/Product.php`
Funzioni:
- `__construct(PDO $pdo)` - salva PDO.
- `getLatest(int $limit = 8): array` - ultimi prodotti.
- `getAll(): array` - tutti prodotti con categoria.
- `findById(int $id): ?array` - trova prodotto specifico.
- `getSpecs(int $productId): array` - specifiche prodotto.
- `getRelated($categoryId, $excludeId)` - prodotti correlati nella stessa categoria.
- `getAccessories(int $productId): array` - prodotti correlati tramite `related_products`.

### `app/models/entities/User.php`
Funzioni:
- `__construct(PDO $pdo)` - salva PDO.
- `findById(int $id): ?array`
- `findByEmail(string $email): ?array`
- `findByGoogleId(string $googleId): ?array`
- `findByGoogleIdOrEmail(string $googleId, string $email): ?array`
- `create(array $data): int` - crea nuovo utente.
- `updateName(int $id, string $fullName): void`
- `updatePassword(int $id, string $hashedPassword): void`
- `updateGoogleId(int $id, string $googleId): void`
- `getWalletBalance(int $id): float`
- `addWalletBalance(int $id, float $amount): void`
- `subtractWalletBalance(int $id, float $amount): bool`
- `getAll(): array`

### `app/models/entities/Order.php`
Funzioni:
- `__construct(PDO $pdo)` - salva PDO.
- `create(array $data): int` - inserisce ordine.
- `addItem(int $orderId, int $productId, int $quantity, float $unitPrice): void`
- `updateStripeSessionId(int $orderId, string $stripeSessionId): void`
- `markPaid(int $orderId, ?string $paymentIntentId = null): void` - marca ordine pagato.
- `findById(int $orderId): ?array`
- `findByStripeSessionId(string $sessionId): ?array`

---

## Servizi

### `app/services/CheckoutService.php`
Funzioni:
- `__construct(PDO $pdo)` - salva PDO.
- `getCartItems(?int $userId): array` - ottiene carrello utente o sessione.
- `getTotal(array $items): float` - somma totale carrello.
- `getWalletBalance(int $userId): float` - saldo wallet utente.
- `processWalletOrder(int $userId, string $name, string $email, ?string $notes, array $items, float $total): int` - paga solo col wallet.
- `processCardOrder(?int $userId, string $name, string $email, ?string $notes, array $items, float $total): int` - crea ordine con pagamento via Stripe.
- `processMixedOrder(int $userId, string $name, string $email, ?string $notes, array $items, float $total, float $walletBalance): array` - paga con wallet + Stripe.

### `app/services/OrderService.php`
Funzioni:
- `__construct(PDO $pdo)` - salva PDO e modelli.
- `validateCartStock(array $items): void` - controlla valità e disponibilità stock.
- `createOrder(array $data): int` - delega ad `Order`.
- `addItems(int $orderId, array $items): void` - aggiunge item a ordine.
- `decreaseStock(array $items): void` - decrementa stock prodotti.
- `clearCart(?int $userId): void` - pulisce carrello DB o sessione.
- `completeOrder(int $orderId): void` - marca ordine pagato, riduce stock, manda email, pulisce carrello.

### `app/services/payment/StripeService.php`
Funzioni:
- `__construct()` - imposta API Key Stripe.
- `createOrderSession(int $orderId, float $amount, string $customerEmail, string $productName, string $appUrl): \\Stripe\\Checkout\\Session` - crea sessione checkout Stripe.
- `createWalletSession(int $userId, float $amount, string $customerEmail, string $appUrl): \\Stripe\\Checkout\\Session` - crea checkout wallet.
- `retrieveSession(string $sessionId): \\Stripe\\Checkout\\Session` - recupera sessione Stripe.
- `constructWebhookEvent(string $payload, string $signature): \\Stripe\\Event` - valida webhook.

### `app/services/email/MailService.php`
Funzioni:
- `buildMailer(): PHPMailer` - configura PHPMailer con SMTP.
- `renderTemplate(string $template, array $vars): string` - rende template email.
- `sendTwoFactorCode(string $toEmail, string $fullName, string $code): void` - invia email 2FA.
- `sendOrderConfirmation(string $toEmail, string $fullName, int $orderId, float $amount): void` - invia conferma ordine.

### `app/services/ProductImportService.php`
Funzioni:
- `__construct(PDO $pdo)` - salva PDO.
- `importFromCsv(string $csvContent, ?string $zipPath = null): array` - importa prodotti da CSV e ZIP immagini.
- `validateProductData(string $name, string $description, string $price, string $stock, string $categoryName, array $categories): array` - validazione riga CSV.
- `insertProduct(string $name, string $description, float $price, int $stock, int $categoryId, ?string $imageFilename): int` - inserisce prodotto.
- `insertSpecs(int $productId, array $row, array $specHeaders): void` - inserisce specifiche prodotto.
- `saveImageFromZip(string $sourcePath, string $originalName): ?string` - salva immagine da ZIP.
- `deleteDirectory(string $dir): void` - elimina directory temporanea.

---

## Controller API e webhook

### `app/controllers/api/StripeWebhookController.php`
Funzioni:
- `__construct(PDO $pdo)` - salva PDO.
- `handle(): void` - legge il payload webhook, verifica firma, gestisce l'evento Stripe.
- `handleCheckoutCompleted(object $session): void` - recupera `metadata.order_id` e marca ordine pagato.
- `markOrderPaid(int $orderId): void` - transazione sicura con lock `FOR UPDATE`, aggiorna stato ordine, decrementa stock, svuota carrello e invia email.

---

## Views importanti

### Layout
- `app/views/layouts/header.php` - header HTML, menu, include CSS/JS e basi layout.
- `app/views/layouts/footer.php` - footer HTML e chiusura body.

### Auth
- `app/views/auth/login.php` - form login.
- `app/views/auth/register.php` - form registrazione.
- `app/views/auth/verify2fa.php` - form inserimento codice 2FA.

### Account
- `app/views/account/dashboard.php` - pagine statistiche e ordini utente.
- `app/views/account/profile.php` - profilo utente e cambio password.

### Cart e checkout
- `app/views/cart/index.php` - pagina carrello.
- `app/views/cart/_mini_cart.php` - mini carrello usato in sidebar/AJAX.
- `app/views/checkout/index.php` - pagina checkout.
- `app/views/checkout/success.php` - pagina successo ordine.

### Prodotti
- `app/views/products/index.php` - catalogo prodotti.
- `app/views/products/show.php` - scheda prodotto.
- `app/views/products/search.php` - pagina ricerca prodotto.

### Admin
- `app/views/admin/dashboard.php` - dashboard admin.
- `app/views/admin/orders.php` - elenco ordini.
- `app/views/admin/products.php` - gestione prodotti e upload.
- `app/views/admin/users.php` - gestione utenti e wallet.

### Email
- `app/views/emails/order_confirmation.php` - template email ordine.
- `app/views/emails/two_factor.php` - template email 2FA.

### Errori
- `app/views/errors/403.php`, `404.php`, `500.php` - pagine di errore.

---

## Database rilevante

### Tabelle principali
- `users` - account, password hash, Google OAuth, saldo wallet, ruolo, remember token.
- `products` - catalogo prodotti e campo `image_path`.
- `categories` - categorie prodotto.
- `cart` - carrello persistente per utente.
- `orders` - ordini e metodi di pagamento.
- `order_items` - prodotti acquistati in un ordine.
- `two_factor_codes` - codici 2FA.
- `user_sessions` - token remember-me.
- `wallet_logs` - storico ricariche wallet.

### Note importanti
- Il carrello loggato usa la tabella `cart`; il carrello guest usa `$_SESSION['cart']`.
- Stripe salva l'ID sessione in `orders.stripe_session_id`.
- Il webhook Stripe abbatte stock e invia conferma ordine.

---

## Come usare questa documentazione

1. Apri il file nel tuo editor.
2. Cerca il nome del file o della funzione con `Ctrl+F` / `Cmd+F`.
3. Usa la sezione `Controller` o `Service` per capire dove si trova la logica.
4. Se vuoi un metodo veloce per trovare una funzione, usa:
   - `Ctrl+Shift+F` e cerca `function nomeFunzione`.
   - `Go to Symbol` in VS Code per vedere le funzioni presenti in un file.

---

## Consiglio per la presentazione
- Parti da `public/index.php` per spiegare il routing.
- Spiega il modello MVC: `controllers` -> `models` -> `views`.
- Descrivi il flusso login/2FA in `AuthController`.
- Mostra il carrello con `CartController` e la protezione stock in `Cart.php`.
- Evidenzia Stripe in `CheckoutController`, `CheckoutService` e `StripeService`.
- Concludi con admin e sicurezza (`CsrfHelper`, `RateLimitHelper`, `ErrorHandler`).

---

## Suggerimento rapido per vedere subito cosa fa una funzione

Se vuoi un altro modo più veloce durante la presentazione:
- Cerca `function` in un file specifico.
- Oppure apri il file e usa lo schema "Nome funzione + breve descrizione" come mappa mentale.
- Questo file ti aiuta a trovare subito il ruolo di ogni file senza leggere tutto il codice.
