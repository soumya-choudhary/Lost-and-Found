<?php
// api/claims.php - AJAX Ownership Claim Submission

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST request required.']);
    exit;
}

$itemId = (int)($_POST['item_id'] ?? 0);
$name   = trim($_POST['claimant_name'] ?? '');
$email  = trim($_POST['claimant_email'] ?? '');
$phone  = trim($_POST['claimant_phone'] ?? '');
$proof  = trim($_POST['proof_description'] ?? '');

if ($itemId <= 0 || empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($proof) < 5) {
    echo json_encode(['success' => false, 'message' => 'Please provide your name, valid email, and proof details.']);
    exit;
}

$itemRepo = new ItemRepository();
$item = $itemRepo->findById($itemId);
if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found.']);
    exit;
}

// Prevent item owner from claiming their own item
if (isItemOwner($item) || strtolower($email) === strtolower($item['contact_email'])) {
    echo json_encode(['success' => false, 'message' => 'You cannot claim an item you reported.']);
    exit;
}

// Optional proof file upload
$proofImage = null;
if (!empty($_FILES['proof_image']['tmp_name']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        $dir = dirname(__DIR__) . '/uploads/claims';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $file = 'proof_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($_FILES['proof_image']['tmp_name'], $dir . '/' . $file)) $proofImage = 'uploads/claims/' . $file;
    }
}

$claimRepo = new ClaimRepository();
$claimId = $claimRepo->create(['item_id' => $itemId, 'claimant_name' => $name, 'claimant_email' => $email, 'claimant_phone' => $phone, 'proof_description' => $proof, 'proof_image_url' => $proofImage]);

echo json_encode([
    'success'    => (bool)$claimId,
    'claim_id'   => $claimId,
    'message'    => $claimId ? 'Claim submitted successfully!' : 'Failed to submit claim.'
]);
