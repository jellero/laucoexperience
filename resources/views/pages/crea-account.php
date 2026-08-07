<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Crea account admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            color: #222;
            padding: 30px;
            box-sizing: border-box;
        }

        .wrap {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
        }

        .box {
            background: #fff;
            padding: 34px;
            box-shadow: 0 14px 40px rgba(0,0,0,.12);
            margin-bottom: 24px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        p {
            line-height: 1.5;
        }

        label {
            display: block;
            margin-top: 16px;
            margin-bottom: 6px;
            font-weight: 700;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d7d7d7;
            box-sizing: border-box;
            font-size: 15px;
        }

        button,
        .btn {
            display: inline-block;
            margin-top: 22px;
            padding: 12px 18px;
            background: #222;
            color: #fff;
            border: 0;
            text-decoration: none;
            cursor: pointer;
            font-size: 15px;
        }

        .btn.secondary {
            background: #666;
            margin-left: 8px;
        }

        .alert {
            padding: 12px 14px;
            margin: 18px 0;
        }

        .alert-error {
            background: #f8d7da;
            color: #842029;
        }

        .alert-success {
            background: #d1e7dd;
            color: #0f5132;
        }

        .alert-warning {
            background: #fff3cd;
            color: #664d03;
        }

        .small {
            font-size: 13px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        th,
        td {
            text-align: left;
            border-bottom: 1px solid #eee;
            padding: 10px;
        }

        th {
            background: #fafafa;
        }

        @media (max-width: 600px) {
            body {
                padding: 15px;
            }

            .box {
                padding: 22px;
            }

            .btn.secondary {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <main class="wrap">
        <section class="box">
            <h1>Crea account admin</h1>
            <p class="small">Questa pagina permette di creare più account per il backoffice.</p>

            <div class="alert alert-warning">
                Attenzione: chiunque possa aprire questa pagina può creare un account admin.
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <form method="post" action="">
                <input type="hidden" name="_setup_csrf_token" value="<?= e($setupToken) ?>">

                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required autocomplete="name">

                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autocomplete="username">

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="new-password" minlength="12">

                <label for="password_confirm">Ripeti password</label>
                <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password" minlength="12">

                <button type="submit">Crea account</button>
                <a class="btn secondary" href="/login">Vai al login</a>
            </form>
        </section>

        <section class="box">
            <h2>Account admin esistenti</h2>

            <?php if (!$utenti): ?>
                <p>Nessun account presente.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Creato il</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utenti as $utente): ?>
                            <tr>
                                <td><?= e($utente['nome']) ?></td>
                                <td><?= e($utente['email']) ?></td>
                                <td><?= e($utente['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
