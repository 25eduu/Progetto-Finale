# TechShop

## Descrizione
TechShop è un'applicazione e-commerce sviluppata in PHP per la vendita di prodotti tecnologici. Il progetto implementa un'architettura MVC personalizzata e integra servizi come Stripe per i pagamenti, PHPMailer per l'invio di email, e Google OAuth per l'autenticazione. Include funzionalità complete per utenti e amministratori, con enfasi sulla sicurezza e l'usabilità.

## Tecnologie Utilizzate
- **Backend**: PHP 8+ con strict types
- **Database**: MySQL
- **Pagamenti**: Stripe API
- **Email**: PHPMailer
- **Frontend**: Bootstrap 5, HTML5, CSS3, JavaScript
- **Autenticazione**: Google OAuth 2.0, 2FA (Two-Factor Authentication)
- **Dipendenze**: Gestite con Composer
- **Ambiente**: XAMPP (Apache + MySQL)

## Requisiti di Sistema
- PHP 8.0 o superiore
- MySQL 5.7+
- Composer
- XAMPP (consigliato per sviluppo locale)
- Chiavi API di Stripe (per pagamenti)
- Credenziali Google OAuth (per login con Google)

## Installazione e Configurazione

### 1. Clonare il Repository
```bash
git clone <URL_DEL_REPOSITORY>
cd Progetto-Finale
```

### 2. Installare le Dipendenze
```bash
composer install
```

### 3. Configurare il Database
- Avvia XAMPP e assicurati che MySQL sia attivo.
- Crea un database MySQL, ad esempio `techshop`.
- Importa lo schema del database:
```bash
mysql -u root -p techshop < database/schema.sql
```

### 4. Creare il file di ambiente
- Crea un file `.env` nella root del progetto.
- Inserisci queste variabili:
```
DB_HOST=localhost
DB_NAME=techshop
DB_USER=root
DB_PASS=your_password

STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret

SMTP_HOST=smtp.gmail.com
SMTP_USER=your_email@gmail.com
SMTP_PASS=your_app_password
SMTP_PORT=587

APP_URL=http://localhost/Progetto-Finale/public
SESSION_SECRET=your_random_secret
```
- Il progetto carica le variabili da `config/config.php` tramite `parse_ini_file(__DIR__ . '/../.env')`.
- Assicurati che `.env` sia escluso da Git. Il file `.gitignore` contiene già questa regola.

### 5. Avviare l'Applicazione
- Avvia Apache e MySQL in XAMPP.
- Apri nel browser: `http://localhost/Progetto-Finale/public/`

## Utilizzo

### Per gli Utenti
1. **Registrazione/Login**: Crea un account o accedi con Google OAuth. Abilita la 2FA per maggiore sicurezza.
2. **Navigazione Prodotti**: Sfoglia i prodotti nella sezione "Prodotti".
3. **Carrello**: Aggiungi prodotti al carrello, aggiorna quantità e procedi al checkout.
4. **Checkout**: Completa l'ordine con Stripe. Riceverai un'email di conferma.
5. **Dashboard Account**: Gestisci il profilo, visualizza ordini e ricarica il wallet.

### Per gli Amministratori
- Accedi come utente con ruolo "admin".
- Dashboard: Gestisci prodotti, ordini, utenti e visualizza statistiche.
- Ricarica Wallet: Usa Stripe per ricariche sicure degli utenti.

## Funzionalità Principali
- **Autenticazione Sicura**: Registrazione, login, logout, Google OAuth, 2FA, e opzione "Ricordami" per login automatico per 30 giorni.
- **Gestione Carrello**: Aggiunta, rimozione, aggiornamento quantità con verifica stock in tempo reale.
- **Checkout e Pagamenti**: Integrazione Stripe per pagamenti sicuri, riduzione stock automatica, svuotamento carrello post-ordine.
- **Email Automatiche**: Conferme ordini e codici 2FA via PHPMailer.
- **Pannello Admin**: Gestione completa di prodotti, ordini e utenti.
- **Sicurezza**: Protezione CSRF, sanitizzazione input, sessioni sicure, cookie sicuri per "remember me".
- **Responsive Design**: UI adattiva grazie a Bootstrap 5.

## Struttura del Progetto
```
Progetto-Finale/
├── app/
│   ├── controllers/     # Controller MVC
│   ├── models/          # Modelli per database
│   ├── views/           # Template HTML
│   ├── services/        # Servizi (Email, ecc.)
│   ├── helpers/         # Utility (CSRF, Flash)
│   └── middleware/      # Middleware (Auth)
├── config/              # Configurazioni
├── database/            # Schema SQL
├── public/              # Entry point e assets
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── index.php
├── vendor/              # Dipendenze Composer
├── .env                 # Variabili ambiente (non committare)
├── composer.json        # Dipendenze PHP
└── README.md            # Questo file
```

## Sicurezza
- **Sessioni**: Utilizzo di sessioni PHP native per la gestione dell'autenticazione.
- **CSRF Protection**: Token CSRF per prevenire attacchi cross-site request forgery.
- **Sanitizzazione**: Tutti gli input utente sono sanitizzati.
- **Cookies**: Cookie sicuri (HttpOnly, SameSite) per la funzionalità "remember me", con token hashed nel database.
- **Webhook Stripe**: Verifica firma per webhooks (opzionale in produzione).

## Test e Debug
- Per testare i pagamenti, usa le chiavi di test di Stripe.
- Controlla i log di errore in `app/services/MailService.php` per problemi email.
- Usa il pannello admin per verificare ordini e stock.

## Import rapido di prodotti
- Per aggiungere singoli prodotti, usa il pannello admin `Prodotti` e il modulo `+ Nuovo prodotto`.
- Se vuoi importare molti prodotti in una volta, usa lo script CLI `scripts/import_products.php`.
- Prepara un CSV con intestazioni:
  `category,name,description,price,stock,image_filename`
- Metti i file immagine in una cartella locale e richiamali con il nome esatto.
- Esegui:
```bash
php scripts/import_products.php products.csv import_images
```
- Lo script copierà le immagini in `public/assets/images/` e inserirà i prodotti nel database.

## Contributi
1. Fork il repository.
2. Crea un branch per le tue modifiche: `git checkout -b feature/nome-feature`.
3. Committa le modifiche: `git commit -am 'Aggiungi nuova feature'`.
4. Pusha il branch: `git push origin feature/nome-feature`.
5. Apri una Pull Request.

## Licenza
Questo progetto è distribuito sotto licenza MIT. Vedi il file `LICENSE` per dettagli.

## Note Finali
- **Versione**: Questa è la versione aggiornata al 1 aprile 2026.
- **Supporto**: Per problemi, apri un issue nel repository.
- **Produzione**: In produzione, configura webhook Stripe e usa HTTPS. Rimuovi `.env` dal repository e usa variabili d'ambiente del server.

Sviluppato con ❤️ per il progetto finale.
