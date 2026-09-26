<?php

/**
 * Auth + roles (3 only):
 * Super Admin  - all pages, can edit/delete/unlock paid earnings
 * Admin        - all pages, paid earnings locked
 * Rate Manager - delivery_settings only
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

function isSuperAdmin()
{
    return getUserRole() === 'Super Admin';
}

function isRateManager()
{
    return getUserRole() === 'Rate Manager';
}

function getHomePage()
{
    return isRateManager() ? 'delivery_settings' : 'index';
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
    return in_array(basename($_SERVER['SCRIPT_NAME']), ['login.php', 'logout.php'], true);
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
        echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
        exit;
    }

    header('Location: ' . $GLOBALS['base_url'] . 'login');
    exit;
}

function requirePageAccess()
{
    if (isPublicPage()) {
        return;
    }

    $page = basename($_SERVER['SCRIPT_NAME'], '.php');

    // Rate Manager: only rate settings page (+ ajax it needs)
    if (isRateManager() && !in_array($page, ['delivery_settings', 'get_employee_rates'], true)) {
        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }

        header('Location: ' . $GLOBALS['base_url'] . 'delivery_settings');
        exit;
    }
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
