<?php
// api/claims.php - AJAX Ownership Claim Submission
//
// WHAT THIS FILE DOES:
// Receives an ownership claim submitted from the claim modal on item-details.php.
// Enforces a critical integrity constraint: THE REPORTER CANNOT CLAIM THEIR OWN ITEM.
// Handles optional proof receipt/photo upload into uploads/proofs/ and invokes ClaimRepository::create().
//
// ACCESS: Signed in (Any user EXCEPT the item's reporter)
// METHOD: POST
// PARAMETERS: item_id, claimant_name, claimant_email, claimant_phone, proof_description, proof_image (file)

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';
require_once __DIR__ . '/../app/repositories/ItemRepository.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Guard: Check request method and authentication
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please sign in to submit a claim.']);
    exit;
}

// 2. Read inputs
$itemId = (int)($_POST['item_id'] ?? 0);
$name   = trim($_POST['claimant_name'] ?? '');
$email  = trim($_POST['claimant_email'] ?? '');
$phone  = trim($_POST['claimant_phone'] ?? '');
$proof  = trim($_POST['proof_description'] ?? '');

// 3. Validate required fields
if ($itemId <= 0 || empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($proof) < 5) {
    echo json_encode(['success' => false, 'message' => 'Please provide your name, valid email, and proof details (at least 5 characters).']);
    exit;
}

$itemRepo = new ItemRepository();
$item = $itemRepo->findById($itemId);

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found.']);
    exit;
}

// 4. Critical Business Logic Rule: Prevent owner from claiming their own item!
if (isItemOwner($item)) {
    echo json_encode(['success' => false, 'message' => 'You cannot submit an ownership claim on an item you reported.']);
    exit;
}

// 5. Handle optional proof image upload
$proofImg = null;
if (!empty($_FILES['proof_image']['tmp_name']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

    if (in_array($ext, $allowedExts)) {
        $dir = dirname(__DIR__) . '/uploads/proofs';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file = 'proof_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($_FILES['proof_image']['tmp_name'], $dir . '/' . $file)) {
            $proofImg = 'uploads/proofs/' . $file;
        }
    }
}

// 6. Save claim via repository transaction
$claimRepo = new ClaimRepository();
$claimId = $claimRepo->create([
    'item_id'           => $itemId,
    'claimant_name'     => $name,
    'claimant_email'    => $email,
    'claimant_phone'    => $phone,
    'proof_description' => $proof,
    'proof_image_url'   => $proofImg
]);

// 7. Return JSON response
echo json_encode([
    'success'  => (bool)$claimId,
    'claim_id' => $claimId,
    'message'  => $claimId ? 'Claim submitted successfully! Status updated to Claim Requested.' : 'Failed to save claim to database.'
]);
