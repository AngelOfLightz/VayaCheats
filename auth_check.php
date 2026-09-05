<?php
require_once 'config.php';

function yetkiKontrol($requiredRoles) {
    global $db; // Burası kesinlikle $db olmalı, çünkü projenin kalanı $db kullanıyor.

    if (!isset($_SESSION['user_id'])) {
        header("Location: auth.php");
        exit;
    }

    $user_id = $_SESSION['user_id'];
    
    // Use session role if available, otherwise query database
    if (isset($_SESSION['role'])) {
        $userRole = $_SESSION['role'];
    } else {
        $query = $db->prepare("SELECT role FROM kullanicilar WHERE id = ?");
        $query->execute([$user_id]);
        $kullanici = $query->fetch(PDO::FETCH_ASSOC);
        
        if (!$kullanici) {
            session_unset();
            session_destroy();
            header("Location: index.php");
            exit;
        }
        
        $userRole = $kullanici['role'];
        $_SESSION['role'] = $userRole; // Cache in session
    }

    // Role hierarchy: owner > admin > moderator > user/vip
    // If user has a higher role than required, allow access
    $roleHierarchy = [
        'owner' => 100,
        'admin' => 50,
        'moderator' => 25,
        'vip' => 10,
        'user' => 5
    ];
    
    $userLevel = $roleHierarchy[$userRole] ?? 0;
    
    // Check if user has any of the required roles OR has a higher role
    $hasAccess = false;
    foreach ($requiredRoles as $requiredRole) {
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
        if ($userLevel >= $requiredLevel) {
            $hasAccess = true;
            break;
        }
    }
    
    if (!$hasAccess) {
        header("Location: index.php");
        exit;
    }
}

function isOwner() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'owner';
}

function isAdmin() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'owner');
}

function isModerator() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['moderator', 'admin', 'owner']);
}

function isAdminOnly() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
?>