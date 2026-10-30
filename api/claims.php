<?php
// api/claims.php - AJAX Ownership Claim Submission Endpoint
//
// WHAT THIS ENDPOINT DOES:
// 1. Receives POST form data: item_id, claimant_name, claimant_email, claimant_phone, proof_description.
// 2. Validates that the claimant provided a valid campus email and detailed proof (min 5 chars).
// 3. Security Check: Prevents the item reporter from submitting a claim on their own reported item.
// 4. Processes optional supporting photo/document proof upload (validates JPG/PNG/WebP format).
// 5. Calls ClaimRepository::create() which transactionally:
//    - Saves the claim as 'Pending'.
//    - Automatically changes the item status to 'Claim Requested'.
//    - Adds an audit trail entry in activity_logs.
// 6. Returns JSON response: ['success' => bool, 'claim_id' => int, 'message' => string].
//
// HOW TO DEBUG & MODIFY FOR PRESENTATION:
// - TO ALLOW OWNERS TO SUBMIT CLAIMS (e.g. for testing): Comment out the `if (isItemOwner($item)...)` check.
// - TO CHANGE MINIMUM PROOF LENGTH: Edit `strlen($proof) < 5` below.
// - TO CHANGE UPLOAD PATH: Edit `$dir = dirname(__DIR__) . '/uploads/claims'`.

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method. POST required.']);
    exit;
}

$itemId = (int)($_POST['item_id'] ?? 0);
$name   = trim($_POST['claimant_name'] ?? '');
$email  = trim($_POST['claimant_email'] ?? '');
$phone  = trim($_POST['claimant_phone'] ?? '');
$proof  = trim($_POST['proof_description'] ?? '');

// Input Validation
// TO CHANGE PROOF LENGTH: Edit 5 below
if ($itemId <= 0 || empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($proof) < 5) {
    echo json_encode(['success' => false, 'message' => 'Please provide your name, valid email, and proof details (minimum 5 characters).']);
    exit;
}

$itemRepo = new ItemRepository();
$item = $itemRepo->findById($itemId);
if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found in database.']);
    exit;
}

// Security Check: Prevent the item reporter from filing a claim on their own reported item
// Compares user ID and contact email against the item's reporter info.
// TO DISABLE THIS CHECK FOR PRESENTATION: Comment out the 4 lines below
if (isItemOwner($item) || strtolower($email) === strtolower($item['contact_email'])) {
    echo json_encode(['success' => false, 'message' => 'You cannot submit an ownership claim on an item you reported.']);
    exit;
}

// Optional Proof File Upload Processing
$proofImage = null;
if (!empty($_FILES['proof_image']['tmp_name']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (in_array($ext, $allowed)) {
        $dir = dirname(__DIR__) . '/uploads/claims';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $file = 'proof_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($_FILES['proof_image']['tmp_name'], $dir . '/' . $file)) {
            $proofImage = 'uploads/claims/' . $file;
        }
    }
}

// Save claim via ClaimRepository (auto-updates item status to 'Claim Requested')
$claimRepo = new ClaimRepository();
$claimId = $claimRepo->create([
    'item_id'           => $itemId,
    'claimant_name'     => $name,
    'claimant_email'    => $email,
    'claimant_phone'    => $phone,
    'proof_description' => $proof,
    'proof_image_url'   => $proofImage
]);

echo json_encode([
    'success'    => (bool)$claimId,
    'claim_id'   => $claimId,
    'message'    => $claimId ? 'Ownership claim submitted! Item status is now Claim Requested.' : 'Failed to record claim in database.'
]);
