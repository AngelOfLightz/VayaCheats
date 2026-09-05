<?php
require_once 'config.php';
require_once 'auth_check.php';

header('Content-Type: application/json');

// CSRF validation
validateCsrfToken($_POST['csrf_token'] ?? '');

// Check if user is admin
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$action = $_POST['action'] ?? '';

// Add new product
if ($action === 'add_product') {
    $hile_adi = sanitizeInput($_POST['hile_adi'] ?? '');
    $durum = sanitizeInput($_POST['durum'] ?? 'UNDETECTED');
    $koruma = sanitizeInput($_POST['koruma'] ?? '');
    $aranacak_kelime = sanitizeInput($_POST['aranacak_kelime'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $version = sanitizeInput($_POST['version'] ?? '1.0.0');
    $features_json = $_POST['features'] ?? '[]';
    $images_json = $_POST['images'] ?? '[]';
    
    if (empty($hile_adi) || empty($durum)) {
        echo json_encode(['success' => false, 'message' => 'Product name and status are required']);
        exit;
    }
    
    // Handle file upload
    $dosya_yolu = '';
    if (isset($_FILES['product_file']) && $_FILES['product_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['product_file'];
        
        // Validate file size (max 50MB)
        $max_size = 50 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            echo json_encode(['success' => false, 'message' => 'File too large. Maximum 50MB allowed.']);
            exit;
        }
        
        // Validate file type
        $allowed_extensions = ['zip', 'rar', '7z', 'exe', 'dll'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: .zip, .rar, .7z, .exe, .dll']);
            exit;
        }
        
        // Create downloads directory if it doesn't exist
        $upload_dir = __DIR__ . '/downloads';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $unique_filename = uniqid('product_', true) . '.' . $file_extension;
        $upload_path = $upload_dir . '/' . $unique_filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload file.']);
            exit;
        }
        
        $dosya_yolu = 'downloads/' . $unique_filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Product file is required.']);
        exit;
    }
    
    try {
        // Insert main product
        $insertProduct = $db->prepare("INSERT INTO hileler (hile_adi, durum, koruma, aranacak_kelime, dosya_yolu) VALUES (?, ?, ?, ?, ?)");
        $insertProduct->execute([$hile_adi, $durum, $koruma, $aranacak_kelime, $dosya_yolu]);
        $product_id = $db->lastInsertId();
        
        // Insert product details
        $insertDetails = $db->prepare("INSERT INTO product_details (product_id, description, features, version, images) VALUES (?, ?, ?, ?, ?)");
        $insertDetails->execute([$product_id, $description, $features_json, $version, $images_json]);
        
        echo json_encode(['success' => true, 'message' => 'Product added successfully', 'product_id' => $product_id]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error adding product: ' . $e->getMessage()]);
        exit;
    }
}

// Update product
if ($action === 'update_product') {
    $product_id = validateId($_POST['product_id'] ?? 0);
    $hile_adi = sanitizeInput($_POST['hile_adi'] ?? '');
    $durum = sanitizeInput($_POST['durum'] ?? 'UNDETECTED');
    $koruma = sanitizeInput($_POST['koruma'] ?? '');
    $aranacak_kelime = sanitizeInput($_POST['aranacak_kelime'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $version = sanitizeInput($_POST['version'] ?? '1.0.0');
    $features_json = $_POST['features'] ?? '[]';
    $images_json = $_POST['images'] ?? '[]';
    
    if ($product_id <= 0 || empty($hile_adi)) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID or name']);
        exit;
    }
    
    // Get current file path
    $currentProduct = $db->prepare("SELECT dosya_yolu FROM hileler WHERE id = ?");
    $currentProduct->execute([$product_id]);
    $productData = $currentProduct->fetch(PDO::FETCH_ASSOC);
    $dosya_yolu = $productData['dosya_yolu'] ?? '';
    
    // Handle file upload if new file provided
    if (isset($_FILES['product_file']) && $_FILES['product_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['product_file'];
        
        // Validate file size (max 50MB)
        $max_size = 50 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            echo json_encode(['success' => false, 'message' => 'File too large. Maximum 50MB allowed.']);
            exit;
        }
        
        // Validate file type
        $allowed_extensions = ['zip', 'rar', '7z', 'exe', 'dll'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: .zip, .rar, .7z, .exe, .dll']);
            exit;
        }
        
        // Create downloads directory if it doesn't exist
        $upload_dir = __DIR__ . '/downloads';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $unique_filename = uniqid('product_', true) . '.' . $file_extension;
        $upload_path = $upload_dir . '/' . $unique_filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload file.']);
            exit;
        }
        
        // Delete old file if it exists
        if (!empty($dosya_yolu) && file_exists(__DIR__ . '/' . $dosya_yolu)) {
            unlink(__DIR__ . '/' . $dosya_yolu);
        }
        
        $dosya_yolu = 'downloads/' . $unique_filename;
    }
    
    try {
        // Update main product
        $updateProduct = $db->prepare("UPDATE hileler SET hile_adi = ?, durum = ?, koruma = ?, aranacak_kelime = ?, dosya_yolu = ? WHERE id = ?");
        $updateProduct->execute([$hile_adi, $durum, $koruma, $aranacak_kelime, $dosya_yolu, $product_id]);
        
        // Update or insert product details
        $checkDetails = $db->prepare("SELECT id FROM product_details WHERE product_id = ?");
        $checkDetails->execute([$product_id]);
        
        if ($checkDetails->fetch()) {
            $updateDetails = $db->prepare("UPDATE product_details SET description = ?, features = ?, version = ?, images = ? WHERE product_id = ?");
            $updateDetails->execute([$description, $features_json, $version, $images_json, $product_id]);
        } else {
            $insertDetails = $db->prepare("INSERT INTO product_details (product_id, description, features, version, images) VALUES (?, ?, ?, ?, ?)");
            $insertDetails->execute([$product_id, $description, $features_json, $version, $images_json]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating product: ' . $e->getMessage()]);
        exit;
    }
}

// Delete product
if ($action === 'delete_product') {
    $product_id = validateId($_POST['product_id'] ?? 0);
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    try {
        // Delete product (cascade will handle related records)
        $deleteProduct = $db->prepare("DELETE FROM hileler WHERE id = ?");
        $deleteProduct->execute([$product_id]);
        
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error deleting product: ' . $e->getMessage()]);
        exit;
    }
}

// Add changelog entry
if ($action === 'add_changelog') {
    $product_id = validateId($_POST['product_id'] ?? 0);
    $version = sanitizeInput($_POST['version'] ?? '');
    $changes = sanitizeInput($_POST['changes'] ?? '');
    $release_date = $_POST['release_date'] ?? date('Y-m-d');
    
    if ($product_id <= 0 || empty($version) || empty($changes)) {
        echo json_encode(['success' => false, 'message' => 'Product ID, version, and changes are required']);
        exit;
    }
    
    try {
        $insertChangelog = $db->prepare("INSERT INTO changelog (product_id, version, changes, release_date) VALUES (?, ?, ?, ?)");
        $insertChangelog->execute([$product_id, $version, $changes, $release_date]);
        
        // Update product_details version
        $updateVersion = $db->prepare("UPDATE product_details SET version = ?, last_update = ? WHERE product_id = ?");
        $updateVersion->execute([$version, $release_date, $product_id]);
        
        echo json_encode(['success' => true, 'message' => 'Changelog added successfully']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error adding changelog: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
