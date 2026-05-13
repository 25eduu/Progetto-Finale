<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Errore del Server</title>
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .error-container {
            text-align: center;
            background: white;
            padding: 3rem;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
        }
        .error-code {
            font-size: 5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 1rem;
        }
        .error-message {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #333;
        }
        .error-description {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .btn-home {
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">500</div>
        <div class="error-message">Errore del Server</div>
        <div class="error-description">
            Si è verificato un errore imprevisto nel server. 
            I nostri tecnici sono stati notificati e stanno lavorando per risolvere il problema.
            <br><br>
            Prova a ricaricare la pagina o torna alla home.
        </div>
        <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary btn-lg btn-home">Torna alla Home</a>
    </div>
</body>
</html>
