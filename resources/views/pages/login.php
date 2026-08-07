<!DOCTYPE html>
<html lang="it">
<head>
    <?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>
    <style>
        .login-box { max-width: 430px; margin: 140px auto 80px; padding: 35px; background: #fff; box-shadow: 0 12px 35px rgba(0,0,0,.12); }
        .login-box label { display:block; margin-top:15px; font-weight:600; }
        .login-box input { width:100%; padding:12px; border:1px solid #ddd; }
        .login-box button { margin-top:20px; width:100%; padding:12px; border:0; background:#222; color:#fff; }
        .login-error { background:#f8d7da; color:#842029; padding:12px; margin-bottom:15px; }
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
