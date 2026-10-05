<!DOCTYPE html>
<html lang="it">
<head>
    <?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>
    <style>
        .login-box { max-width: 430px; margin: 140px auto 80px; padding: 35px; background: #fff; box-shadow: 0 12px 35px rgba(0,0,0,.12); }
        .login-box label { display:block; margin-top:15px; font-weight:600; }
        .login-box input { width:100%; padding:12px; border:1px solid #ddd; }
        .login-box button { margin-top:20px; width:100%; padding:12px; border:0; background:#222; color:#fff; cursor:pointer; }
        .login-error { background:#f8d7da; color:#842029; padding:12px; margin-bottom:15px; }
        .login-remember { display:flex!important; align-items:center; gap:10px; margin-top:18px!important; cursor:pointer; font-weight:500!important; }
        .login-remember input { width:18px; height:18px; padding:0; margin:0; flex:0 0 auto; }
        .login-remember span { line-height:1.35; }
        .login-remember small { display:block; margin-top:2px; color:#777; font-weight:400; }
    </style>
</head>
<body>
    <div id="myloader">
        <span class="loader"><div class="inner-loader"></div></span>
    </div>

    <div id="main-wrap" class="full-width">
        <?php require LAUCO_VIEW_PATH . '/partials/menu.php'; ?>

        <div id="page-content" class="header-static footer-fixed">
            <div class="container">
                <div class="login-box">
                    <h1 class="margin-bottom-small">Login backoffice</h1>

                    <?php if ($error): ?>
                        <div class="login-error"><?= e($error) ?></div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">

                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required autocomplete="username">

                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required autocomplete="current-password">

                        <label class="login-remember" for="remember">
                            <input type="checkbox" id="remember" name="remember" value="1" <?= !empty($remember) ? 'checked' : '' ?>>
                            <span>
                                Resta collegato
                                <small>Mantiene l’accesso su questo dispositivo per 30 giorni.</small>
                            </span>
                        </label>

                        <button type="submit">Entra</button>
                    </form>
                </div>
            </div>
        </div>

        <?php require LAUCO_VIEW_PATH . '/partials/footerf.php'; ?>
    </div>

    <?php require LAUCO_VIEW_PATH . '/partials/scripts.php'; ?>
</body>
</html>
