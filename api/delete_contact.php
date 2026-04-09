<?php

declare(strict_types=1);

header('Content-Type: application/json');

$basePath = dirname(__DIR__);
require $basePath . '/app/Bootstrap.php';
\App\Bootstrap::init($basePath);
require $basePath . '/config/auth.php';
require $basePath . '/config/db.php';

// Check if user is logged in and is admin
if (!is_logged_in() || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

verify_csrf();

$contactId = (int) ($_POST['contact_id'] ?? 0);

if ($contactId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid contact ID']);
    exit;
}

try {
    // Check if contact exists
    $stmt = db()->prepare('SELECT id FROM contacts WHERE id = ?');
    $stmt->execute([$contactId]);
    $contact = $stmt->fetch();
    
    if (!$contact) {
        echo json_encode(['success' => false, 'error' => 'Contact not found']);
        exit;
    }
    
    // Check if contact has any visits
    $stmt = db()->prepare('SELECT COUNT(*) FROM visits WHERE contact_id = ?');
    $stmt->execute([$contactId]);
    $visitCount = (int) $stmt->fetchColumn();
    
    if ($visitCount > 0) {
        echo json_encode(['success' => false, 'error' => 'Cannot delete contact with visits']);
        exit;
    }
    
    // Delete the contact
    $stmt = db()->prepare('DELETE FROM contacts WHERE id = ?');
    $stmt->execute([$contactId]);
    
    echo json_encode(['success' => true]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to delete contact: ' . $e->getMessage()]);
}
