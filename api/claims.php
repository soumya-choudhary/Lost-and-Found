<?php
// api/claims.php - AJAX Claim Submission Endpoint

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/repositories/ClaimRepository.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$itemId = (int)($_POST['item_id'] ?? 0);
$name = trim($_POST['claimant_name'] ?? '');
$email = trim($_POST['claimant_email'] ?? '');
$phone = trim($_POST['claimant_phone'] ?? '');
$proof = trim($_POST['proof_description'] ?? '');

$errors = [];
if ($itemId <= 0) $errors[] = 'Invalid item specified.';
if (empty($name)) $errors[] = 'Your name is required.';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
if (empty($proof) || strlen($proof) < 10) $errors[] = 'Please provide detailed proof of ownership (at least 10 characters).';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// Handle optional proof image upload
$proofImagePath = null;
if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['proof_image'];
    if ($file['error'] === UPLOAD_ERR_OK && $file['size'] <= 5 * 1024 * 1024) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $dir = dirname(__DIR__) . '/uploads/claims';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $fileName = 'proof_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $dir . '/' . $fileName)) {
                $proofImagePath = 'uploads/claims/' . $fileName;
            }
        }
    }
}

$claimRepo = new ClaimRepository();
$claimId = $claimRepo->create([
    'item_id'           => $itemId,
    'claimant_name'     => $name,
    'claimant_email'    => $email,
    'claimant_phone'    => $phone,
    'proof_description' => $proof,
    'proof_image_url'   => $proofImagePath
]);

if ($claimId) {
    echo json_encode([
        'success'     => true,
        'claim_id'    => $claimId,
        'new_status'  => 'Claim Requested',
        'message'     => 'Your ownership claim has been submitted! Item status is now Claim Requested.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to record claim in database.']);
}

