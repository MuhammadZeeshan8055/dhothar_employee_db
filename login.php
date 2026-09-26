<?php

include('session.php');

if (isLoggedIn()) {
    header('Location: ' . $base_url . 'index');
    exit;
}

include('database.php');

$error = '';

if (isset($_POST['login'])) {
    $obj = new Database();
    $result = attemptLogin($_POST['email'] ?? '', $_POST['password'] ?? '', $obj);

    if ($result['success']) {
        header('Location: ' . $base_url . 'index');
        exit;
    }

    $error = $result['message'];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Dhothar International" />
    <meta name="author" content="Laborator.co" />
    <link rel="icon" href="<?= $base_url ?>assets/images/favicon.ico">
    <title>Dhothar International | Login</title>

    <link rel="stylesheet" href="<?= $base_url ?>assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/font-icons/entypo/css/entypo.css">
    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Noto+Sans:400,700,400italic">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/bootstrap.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/neon-core.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/neon-theme.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/neon-forms.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/custom.css">
</head>

<body class="page-body login-page">

    <div class="login-container">

        <div class="login-header login-caret">
            <div class="login-content">
                <a href="#" class="logo">
                    <img src="<?= $base_url ?>dhothar_logo.png" width="100%" alt="Dhothar International" />
                </a>
            </div>
        </div>

        <div class="login-form">
            <div class="login-content">

                <?php if ($error !== ''): ?>
                    <div class="form-login-error" style="display:block; margin-bottom:15px;">
                        <h3>Invalid login</h3>
                        <p><?= htmlspecialchars($error) ?></p>
                    </div>
                <?php endif; ?>

                <form method="post" role="form" autocomplete="off">
                    <div class="form-group">
                        <div class="input-group">
                            <div class="input-group-addon"><i class="entypo-user"></i></div>
                            <input type="text" class="form-control" name="email" id="username"
                                placeholder="email" autocomplete="off" required
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="input-group">
                            <div class="input-group-addon"><i class="entypo-key"></i></div>
                            <input type="password" class="form-control" name="password" id="password"
                                placeholder="Password" autocomplete="off" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="login" class="btn btn-primary btn-block btn-login">
                            <i class="entypo-login"></i>
                            Log In
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>

</body>

</html>
