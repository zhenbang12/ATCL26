<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Container;
use App\Core\Auth;

class FinanceController
{
    public function index(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Finance & Procurement';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/finance/index.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function claims(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Claims';
        $db = Container::get('db');
        $stmt = $db->query('SELECT id, claimant_name, department, description, status, amount_total, receipt_image, items_image FROM claims ORDER BY created_at DESC');
        $claims = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/finance/claims.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function storeClaim(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');

        // Handle file uploads
        $receiptImage = null;
        $itemsImage = null;

        // Create uploads directory if it doesn't exist
        $uploadDir = __DIR__ . '/../../public/uploads/claims/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Handle receipt image upload
        if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['receipt_image'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'receipt_' . time() . '_' . uniqid() . '.' . $extension;
                $filepath = $uploadDir . $filename;

                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $receiptImage = '/uploads/claims/' . $filename;
                }
            }
        }

        // Handle items image upload
        if (isset($_FILES['items_image']) && $_FILES['items_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['items_image'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'items_' . time() . '_' . uniqid() . '.' . $extension;
                $filepath = $uploadDir . $filename;

                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $itemsImage = '/uploads/claims/' . $filename;
                }
            }
        }

        $stmt = $db->prepare('INSERT INTO claims (claimant_name, department, description, receipt_image, items_image, amount_total, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $_POST['claimant_name'] ?? '',
            $_POST['department'] ?? '',
            $_POST['description'] ?? '',
            $receiptImage,
            $itemsImage,
            (float)($_POST['amount_total'] ?? 0),
            'submitted',
        ]);

        header('Location: /finance/claims');
        exit;
    }

    public function budgetDashboard(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Budget Dashboard';
        $db = Container::get('db');

        $stmt = $db->query('SELECT department, allocated_amount, spent_amount FROM budgets ORDER BY department');
        $budgets = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/finance/budget.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }
}

