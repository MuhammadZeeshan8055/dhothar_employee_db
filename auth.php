<?php

/**
 * Authentication helpers.
 * Session keys: logged_in, user (id, name, email, role)
 */

function isLoggedIn()
{
    return !empty($_SESSION['logged_in']) && !empty($_SESSION['user']['id']);
}

function getCurrentUser()
{
    return isLoggedIn() ? $_SESSION['user'] : null;
}

function getUserRole()
{
    $user = getCurrentUser();
    return $user ? $user['role'] : '';
}

function setAuthUser(array $user)
{
    session_regenerate_id(true);

    $_SESSION['logged_in'] = true;
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
    $_SESSION['login_time'] = time();
}

function clearAuthUser()
{
    unset($_SESSION['logged_in'], $_SESSION['user'], $_SESSION['login_time']);
}

function isPublicPage()
{
    $publicPages = ['login.php', 'logout.php'];
    $currentScript = basename($_SERVER['SCRIPT_NAME']);

    return in_array($currentScript, $publicPages, true);
}

function isAjaxRequest()
{
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }

    return strpos($_SERVER['SCRIPT_NAME'], '/ajax/') !== false;
}

function requireAuth()
{
    if (isLoggedIn()) {
        return;
    }

    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Session expired. Please login again.',
        ]);
        exit;
    }

    header('Location: ' . $GLOBALS['base_url'] . 'login');
    exit;
}

function attemptLogin($email, $password, Database $db)
{
    $email = trim($email);

    if ($email === '' || $password === '') {
        return ['success' => false, 'message' => 'Email and password are required.'];
    }

    $user = $db->getUserByEmail($email);

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    $db->updateLastLogin((int) $user['id']);
    setAuthUser($user);

    return ['success' => true, 'message' => 'Login successful.'];
}

?>
