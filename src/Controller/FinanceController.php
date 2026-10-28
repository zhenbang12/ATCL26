<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Container;
use App\Core\Auth;

class FinanceController
{
    private const ALLOWED_IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    private function storeFinanceUpload(array $file, string $subDir, string $prefix): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) <= 0) {
            return null;
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file((string)$file['tmp_name']);
        if ($mime === false || !isset(self::ALLOWED_IMAGE_MIMES[$mime])) {
            return null;
        }

        $ext = self::ALLOWED_IMAGE_MIMES[$mime];
        $uploadDir = __DIR__ . '/../../uploads/' . trim($subDir, '/') . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $target = $uploadDir . $filename;

        if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
            return null;
        }

        return '/uploads/' . trim($subDir, '/') . '/' . $filename;
    }

    public function index(): void
    {
        Auth::requireRole(['advisor', 'committee', 'treasurer']);

        $title = 'Finance & Procurement';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/finance/index.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function claims(): void
    {
        Auth::requireRole(['advisor', 'committee', 'treasurer']);

        $title = 'Claims';
        $db = Container::get('db');
        
        $filter = $_GET['filter'] ?? 'all';
        $query = 'SELECT id, claimant_name, department, description, status, amount_total, receipt_image, items_image, created_at FROM claims';
        
        if ($filter === 'approved') {
            $query .= " WHERE status = 'approved'";
        } elseif ($filter === 'rejected') {
            $query .= " WHERE status = 'rejected'";
        }
        
        $query .= ' ORDER BY created_at DESC';
        $stmt = $db->query($query);
        $claims = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/finance/claims.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function storeClaim(): void
    {
        Auth::requireRole(['advisor', 'committee', 'treasurer']);

        $db = Container::get('db');

        // Handle file uploads
        $receiptImage = null;
        $itemsImage = null;

        // Handle receipt image upload securely
        if (!empty($_FILES['receipt_image']['tmp_name'])) {
            $receiptImage = $this->storeFinanceUpload($_FILES['receipt_image'], 'claims', 'receipt') ?? $receiptImage;
        }

        // Handle items image upload securely
        if (!empty($_FILES['items_image']['tmp_name'])) {
            $itemsImage = $this->storeFinanceUpload($_FILES['items_image'], 'claims', 'items') ?? $itemsImage;
        }

        // Determine status: draft or submitted
        $status = isset($_POST['save_as_draft']) ? 'draft' : 'submitted';

        $stmt = $db->prepare('INSERT INTO claims (claimant_name, department, description, receipt_image, items_image, amount_total, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $_POST['claimant_name'] ?? '',
            $_POST['department'] ?? '',
            $_POST['description'] ?? '',
            $receiptImage,
            $itemsImage,
            (float)($_POST['amount_total'] ?? 0),
            $status,
        ]);

        header('Location: /finance/claims');
        exit;
    }

    public function budgetDashboard(): void
    {
        Auth::requireRole(['advisor', 'committee', 'treasurer']);

        $title = 'Budget Dashboard';
        $db = Container::get('db');

        $stmt = $db->query('SELECT department, allocated_amount, spent_amount FROM budgets ORDER BY department');
        $budgets = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/finance/budget.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function buyingRequests(): void
    {
        Auth::requireRole(['advisor', 'committee', 'treasurer']);

        $title = 'Buying Requests';
        $db = Container::get('db');
        
        $filter = $_GET['filter'] ?? 'all';
        $query = 'SELECT id, requester_name, department, item_description, quantity, estimated_cost, justification, vendor_preference, reference_image, status, created_at FROM buying_requests';
        
        if ($filter === 'draft') {
            $query .= " WHERE status = 'draft'";
        } elseif ($filter === 'approved') {
            $query .= " WHERE status = 'approved'";
        } elseif ($filter === 'rejected') {
            $query .= " WHERE status = 'rejected'";
        } elseif ($filter === 'pending') {
            $query .= " WHERE status = 'pending'";
        }
        
        $query .= ' ORDER BY created_at DESC';
        $stmt = $db->query($query);
        $buyingRequests = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/finance/buying_requests.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function storeBuyingRequest(): void
    {
        Auth::requireRole(['advisor', 'committee', 'treasurer']);

        $db = Container::get('db');

        // Handle file upload
        $referenceImage = null;

        // Handle reference image upload securely
        if (!empty($_FILES['reference_image']['tmp_name'])) {
            $referenceImage = $this->storeFinanceUpload($_FILES['reference_image'], 'buying_requests', 'reference') ?? $referenceImage;
        }

        // Determine status: draft or pending
        $status = isset($_POST['save_as_draft']) ? 'draft' : 'pending';

        $stmt = $db->prepare('INSERT INTO buying_requests (requester_name, department, item_description, quantity, estimated_cost, justification, vendor_preference, reference_image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $_POST['requester_name'] ?? '',
            $_POST['department'] ?? '',
            $_POST['item_description'] ?? '',
            (int)($_POST['quantity'] ?? 1),
            (float)($_POST['estimated_cost'] ?? 0),
            $_POST['justification'] ?? '',
            $_POST['vendor_preference'] ?? null,
            $referenceImage,
            $status,
        ]);

        header('Location: /finance/buying-requests');
        exit;
    }

    public function approveClaim(): void
    {
        Auth::requireRole(['advisor', 'committee', 'treasurer']);

        $db = Container::get('db');
        $claimId = (int)($_POST['claim_id'] ?? 0);

        if ($claimId > 0) {
            $stmt = $db->prepare('UPDATE claims SET status = ? WHERE id = ?');
            $stmt->execute(['approved', $claimId]);
        }

        header('Location: /finance/claims');
        exit;
    }

    public function rejectClaim(): void
    {
        Auth::requireRole(['advisor', 'committee', 'treasurer']);

        $db = Container::get('db');
        $claimId = (int)($_POST['claim_id'] ?? 0);

        if ($claimId > 0) {
            $stmt = $db->prepare('UPDATE claims SET status = ? WHERE id = ?');
            $stmt->execute(['rejected', $claimId]);
        }

        header('Location: /finance/claims');
        exit;
    }

    public function approveBuyingRequest(): void
    {
        Auth::requireRole(['advisor', 'committee', 'treasurer']);

        $db = Container::get('db');
        $requestId = (int)($_POST['request_id'] ?? 0);

        if ($requestId > 0) {
            $stmt = $db->prepare('UPDATE buying_requests SET status = ? WHERE id = ?');
            $stmt->execute(['approved', $requestId]);
        }

        header('Location: /finance/buying-requests');
        exit;
    }

    public function rejectBuyingRequest(): void
    {
        Auth::requireRole(['advisor', 'committee', 'treasurer']);

        $db = Container::get('db');
        $requestId = (int)($_POST['request_id'] ?? 0);

        if ($requestId > 0) {
            $stmt = $db->prepare('UPDATE buying_requests SET status = ? WHERE id = ?');
            $stmt->execute(['rejected', $requestId]);
        }

        header('Location: /finance/buying-requests');
        exit;
    }

    public function editClaim(): void
    {
        Auth::requireRole(['advisor', 'committee', 'treasurer']);

        $db = Container::get('db');
        $claimId = (int)($_GET['id'] ?? 0);

        if ($claimId <= 0) {
            header('Location: /finance/claims');
            exit;
        }

        $stmt = $db->prepare('SELECT * FROM claims WHERE id = ?');
        $stmt->execute([$claimId]);
        $claim = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$claim) {
            header('Location: /finance/claims');
            exit;
        }

        $title = 'Edit Claim';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/finance/edit_claim.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function updateClaim(): void
    {
        Auth::requireRole(['advisor', 'committee', 'treasurer']);

        $db = Container::get('db');
        $claimId = (int)($_POST['claim_id'] ?? 0);

        if ($claimId <= 0) {
            header('Location: /finance/claims');
            exit;
        }

        // Get existing claim to preserve images if not updated
        $stmt = $db->prepare('SELECT receipt_image, items_image FROM claims WHERE id = ?');
        $stmt->execute([$claimId]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

        $receiptImage = $existing['receipt_image'] ?? null;
        $itemsImage = $existing['items_image'] ?? null;

        // Handle receipt image upload securely
        if (!empty($_FILES['receipt_image']['tmp_name'])) {
            $receiptImage = $this->storeFinanceUpload($_FILES['receipt_image'], 'claims', 'receipt') ?? $receiptImage;
        }

        // Handle items image upload securely
        if (!empty($_FILES['items_image']['tmp_name'])) {
            $itemsImage = $this->storeFinanceUpload($_FILES['items_image'], 'claims', 'items') ?? $itemsImage;
        }

        // Determine new status: if resubmitting, set to 'submitted'; if submitting draft, set to 'submitted'; otherwise keep current status
        $newStatus = null;
        if (isset($_POST['resubmit']) || isset($_POST['submit_draft'])) {
            $newStatus = 'submitted';
        } elseif (isset($_POST['save_as_draft'])) {
            $newStatus = 'draft';
        }

        if ($newStatus) {
            $stmt = $db->prepare('UPDATE claims SET claimant_name = ?, department = ?, description = ?, receipt_image = ?, items_image = ?, amount_total = ?, status = ? WHERE id = ?');
            $stmt->execute([
                $_POST['claimant_name'] ?? '',
                $_POST['department'] ?? '',
                $_POST['description'] ?? '',
                $receiptImage,
                $itemsImage,
                (float)($_POST['amount_total'] ?? 0),
                $newStatus,
                $claimId,
            ]);
        } else {
            $stmt = $db->prepare('UPDATE claims SET claimant_name = ?, department = ?, description = ?, receipt_image = ?, items_image = ?, amount_total = ? WHERE id = ?');
            $stmt->execute([
                $_POST['claimant_name'] ?? '',
                $_POST['department'] ?? '',
                $_POST['description'] ?? '',
                $receiptImage,
                $itemsImage,
                (float)($_POST['amount_total'] ?? 0),
                $claimId,
            ]);
        }

        header('Location: /finance/claims');
        exit;
    }

    public function editBuyingRequest(): void
    {
        Auth::requireRole(['advisor', 'committee', 'treasurer']);

        $db = Container::get('db');
        $requestId = (int)($_GET['id'] ?? 0);

        if ($requestId <= 0) {
            header('Location: /finance/buying-requests');
            exit;
        }

        $stmt = $db->prepare('SELECT * FROM buying_requests WHERE id = ?');
        $stmt->execute([$requestId]);
        $buyingRequest = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$buyingRequest) {
            header('Location: /finance/buying-requests');
            exit;
        }

        $title = 'Edit Buying Request';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/finance/edit_buying_request.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function updateBuyingRequest(): void
    {
        Auth::requireRole(['advisor', 'committee', 'treasurer']);

        $db = Container::get('db');
        $requestId = (int)($_POST['request_id'] ?? 0);

        if ($requestId <= 0) {
            header('Location: /finance/buying-requests');
            exit;
        }

        // Get existing request to preserve image if not updated
        $stmt = $db->prepare('SELECT reference_image FROM buying_requests WHERE id = ?');
        $stmt->execute([$requestId]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

        $referenceImage = $existing['reference_image'] ?? null;

        // Handle reference image upload securely
        if (!empty($_FILES['reference_image']['tmp_name'])) {
            $referenceImage = $this->storeFinanceUpload($_FILES['reference_image'], 'buying_requests', 'reference') ?? $referenceImage;
        }

        // Determine new status: if resubmitting, set to 'pending'; if submitting draft, set to 'pending'; otherwise keep current status
        $newStatus = null;
        if (isset($_POST['resubmit']) || isset($_POST['submit_draft'])) {
            $newStatus = 'pending';
        } elseif (isset($_POST['save_as_draft'])) {
            $newStatus = 'draft';
        }

        if ($newStatus) {
            $stmt = $db->prepare('UPDATE buying_requests SET requester_name = ?, department = ?, item_description = ?, quantity = ?, estimated_cost = ?, justification = ?, vendor_preference = ?, reference_image = ?, status = ? WHERE id = ?');
            $stmt->execute([
                $_POST['requester_name'] ?? '',
                $_POST['department'] ?? '',
                $_POST['item_description'] ?? '',
                (int)($_POST['quantity'] ?? 1),
                (float)($_POST['estimated_cost'] ?? 0),
                $_POST['justification'] ?? '',
                $_POST['vendor_preference'] ?? null,
                $referenceImage,
                $newStatus,
                $requestId,
            ]);
        } else {
            $stmt = $db->prepare('UPDATE buying_requests SET requester_name = ?, department = ?, item_description = ?, quantity = ?, estimated_cost = ?, justification = ?, vendor_preference = ?, reference_image = ? WHERE id = ?');
            $stmt->execute([
                $_POST['requester_name'] ?? '',
                $_POST['department'] ?? '',
                $_POST['item_description'] ?? '',
                (int)($_POST['quantity'] ?? 1),
                (float)($_POST['estimated_cost'] ?? 0),
                $_POST['justification'] ?? '',
                $_POST['vendor_preference'] ?? null,
                $referenceImage,
                $requestId,
            ]);
        }

        header('Location: /finance/buying-requests');
        exit;
    }
}

