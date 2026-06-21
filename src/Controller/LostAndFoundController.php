<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Container;
use App\Core\SessionHelper;

class LostAndFoundController
{
    private function sid(): int
    {
        return SessionHelper::currentSessionId();
    }

    // Admin: List all lost and found items with filter/sort
    public function index(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Lost & Found';
        $db = Container::get('db');
        $sid = $this->sid();

        // Filter params
        $filter = trim((string)($_GET['filter'] ?? 'all'));
        $sort = trim((string)($_GET['sort'] ?? 'newest'));

        $where = "WHERE session_id = ?";
        $params = [$sid];

        if ($filter === 'unclaimed') {
            $where .= " AND status = 'unclaimed'";
        } elseif ($filter === 'claimed') {
            $where .= " AND status = 'claimed'";
        }

        $orderBy = match ($sort) {
            'oldest' => 'created_at ASC',
            'caption' => 'caption ASC',
            default => 'created_at DESC',
        };

        $stmt = $db->prepare(
            "SELECT * FROM lost_and_found_items $where ORDER BY status ASC, $orderBy"
        );
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Count totals for badges
        $countStmt = $db->prepare(
            "SELECT status, COUNT(*) as cnt FROM lost_and_found_items WHERE session_id = ? GROUP BY status"
        );
        $countStmt->execute([$sid]);
        $counts = ['all' => 0, 'unclaimed' => 0, 'claimed' => 0];
        while ($row = $countStmt->fetch(\PDO::FETCH_ASSOC)) {
            $counts[$row['status']] = (int)$row['cnt'];
            $counts['all'] += (int)$row['cnt'];
        }

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/lost_and_found/index.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    // Admin: Show create form
    public function create(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Add Lost & Found Item';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/lost_and_found/create.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    // Admin: Store a new lost and found item
    public function store(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $sid = $this->sid();
        $caption = trim((string)($_POST['caption'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));

        if ($caption === '') {
            $_SESSION['lf_message'] = 'Caption is required.';
            $_SESSION['lf_message_type'] = 'danger';
            header('Location: /lost-and-found/create');
            exit;
        }

        // Handle photo upload
        $photoFilename = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/lost_and_found/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $mimeType = mime_content_type($_FILES['photo']['tmp_name']);

            if (!in_array($mimeType, $allowedTypes)) {
                $_SESSION['lf_message'] = 'Invalid image type. Allowed: JPG, PNG, GIF, WEBP.';
                $_SESSION['lf_message_type'] = 'danger';
                header('Location: /lost-and-found/create');
                exit;
            }

            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photoFilename = 'lf_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $targetPath = $uploadDir . $photoFilename;

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                $_SESSION['lf_message'] = 'Failed to upload photo.';
                $_SESSION['lf_message_type'] = 'danger';
                header('Location: /lost-and-found/create');
                exit;
            }
        }

        try {
            $stmt = $db->prepare(
                "INSERT INTO lost_and_found_items (session_id, photo_filename, caption, description) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$sid, $photoFilename, $caption, $description]);

            $_SESSION['lf_message'] = 'Item added to Lost & Found.';
            $_SESSION['lf_message_type'] = 'success';
            header('Location: /lost-and-found');
            exit;
        } catch (\Exception $e) {
            $_SESSION['lf_message'] = 'Failed to add item: ' . $e->getMessage();
            $_SESSION['lf_message_type'] = 'danger';
            header('Location: /lost-and-found/create');
            exit;
        }
    }

    // Admin: Show edit form
    public function edit(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['lf_message'] = 'Invalid item.';
            $_SESSION['lf_message_type'] = 'danger';
            header('Location: /lost-and-found');
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM lost_and_found_items WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$item) {
            $_SESSION['lf_message'] = 'Item not found.';
            $_SESSION['lf_message_type'] = 'danger';
            header('Location: /lost-and-found');
            exit;
        }

        $title = 'Edit Lost & Found Item';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/lost_and_found/edit.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    // Admin: Update a lost and found item
    public function update(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $id = (int)($_POST['id'] ?? 0);
        $caption = trim((string)($_POST['caption'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $removePhoto = isset($_POST['remove_photo']);

        if ($id <= 0 || $caption === '') {
            $_SESSION['lf_message'] = 'Caption is required.';
            $_SESSION['lf_message_type'] = 'danger';
            header('Location: /lost-and-found/edit?id=' . $id);
            exit;
        }

        // Get current item
        $stmt = $db->prepare("SELECT * FROM lost_and_found_items WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$item) {
            $_SESSION['lf_message'] = 'Item not found.';
            $_SESSION['lf_message_type'] = 'danger';
            header('Location: /lost-and-found');
            exit;
        }

        $uploadDir = __DIR__ . '/../../uploads/lost_and_found/';
        $photoFilename = $item['photo_filename'];

        // Handle photo removal
        if ($removePhoto && $photoFilename) {
            $oldPath = $uploadDir . $photoFilename;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
            $photoFilename = null;
        }

        // Handle new photo upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $mimeType = mime_content_type($_FILES['photo']['tmp_name']);

            if (!in_array($mimeType, $allowedTypes)) {
                $_SESSION['lf_message'] = 'Invalid image type. Allowed: JPG, PNG, GIF, WEBP.';
                $_SESSION['lf_message_type'] = 'danger';
                header('Location: /lost-and-found/edit?id=' . $id);
                exit;
            }

            // Delete old photo
            if ($photoFilename) {
                $oldPath = $uploadDir . $photoFilename;
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photoFilename = 'lf_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $targetPath = $uploadDir . $photoFilename;

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                $_SESSION['lf_message'] = 'Failed to upload photo.';
                $_SESSION['lf_message_type'] = 'danger';
                header('Location: /lost-and-found/edit?id=' . $id);
                exit;
            }
        }

        try {
            $stmt = $db->prepare(
                "UPDATE lost_and_found_items SET photo_filename = ?, caption = ?, description = ? WHERE id = ?"
            );
            $stmt->execute([$photoFilename, $caption, $description, $id]);

            $_SESSION['lf_message'] = 'Item updated successfully.';
            $_SESSION['lf_message_type'] = 'success';
            header('Location: /lost-and-found');
            exit;
        } catch (\Exception $e) {
            $_SESSION['lf_message'] = 'Failed to update item: ' . $e->getMessage();
            $_SESSION['lf_message_type'] = 'danger';
            header('Location: /lost-and-found/edit?id=' . $id);
            exit;
        }
    }

    // Admin: Delete a lost and found item
    public function delete(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['lf_message'] = 'Invalid item.';
            $_SESSION['lf_message_type'] = 'danger';
            header('Location: /lost-and-found');
            exit;
        }

        // Get item to delete photo file
        $stmt = $db->prepare("SELECT photo_filename FROM lost_and_found_items WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($item && $item['photo_filename']) {
            $photoPath = __DIR__ . '/../../uploads/lost_and_found/' . $item['photo_filename'];
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }
        }

        $stmt = $db->prepare("DELETE FROM lost_and_found_items WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['lf_message'] = 'Item removed.';
        $_SESSION['lf_message_type'] = 'success';
        header('Location: /lost-and-found');
        exit;
    }

    // Admin: Mark item as returned / reset to unclaimed
    public function markReturned(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['lf_message'] = 'Invalid item.';
            $_SESSION['lf_message_type'] = 'danger';
            header('Location: /lost-and-found');
            exit;
        }

        $stmt = $db->prepare(
            "UPDATE lost_and_found_items SET status = 'unclaimed', claimed_by_name = NULL, claimed_by_phone = NULL, claimed_at = NULL WHERE id = ?"
        );
        $stmt->execute([$id]);

        $_SESSION['lf_message'] = 'Item marked as unclaimed.';
        $_SESSION['lf_message_type'] = 'success';
        header('Location: /lost-and-found');
        exit;
    }

    // Public: Show all unclaimed lost and found items
    public function publicView(): void
    {
        $title = 'Lost & Found';
        $db = Container::get('db');
        $sid = $this->sid();

        $stmt = $db->prepare(
            "SELECT * FROM lost_and_found_items WHERE session_id = ? AND status = 'unclaimed' ORDER BY created_at DESC"
        );
        $stmt->execute([$sid]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/lost_and_found/public.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    // Public: Show claim form for a specific item
    public function claimForm(): void
    {
        $title = 'Claim Item';
        $db = Container::get('db');
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['lf_message'] = 'Invalid item.';
            $_SESSION['lf_message_type'] = 'danger';
            header('Location: /lost-and-found/public');
            exit;
        }

        $stmt = $db->prepare(
            "SELECT * FROM lost_and_found_items WHERE id = ? AND status = 'unclaimed'"
        );
        $stmt->execute([$id]);
        $item = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$item) {
            $_SESSION['lf_message'] = 'Item not found or already claimed.';
            $_SESSION['lf_message_type'] = 'warning';
            header('Location: /lost-and-found/public');
            exit;
        }

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/lost_and_found/claim.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    // Public: Process claim submission
    public function submitClaim(): void
    {
        $db = Container::get('db');
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['claimant_name'] ?? ''));
        $phone = trim((string)($_POST['claimant_phone'] ?? ''));

        if ($id <= 0 || $name === '' || $phone === '') {
            $_SESSION['lf_message'] = 'Please fill in all required fields.';
            $_SESSION['lf_message_type'] = 'danger';
            header('Location: /lost-and-found/claim?id=' . $id);
            exit;
        }

        // Verify item is still unclaimed
        $stmt = $db->prepare("SELECT id, status FROM lost_and_found_items WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$item || $item['status'] !== 'unclaimed') {
            $_SESSION['lf_message'] = 'This item has already been claimed.';
            $_SESSION['lf_message_type'] = 'warning';
            header('Location: /lost-and-found/public');
            exit;
        }

        try {
            $stmt = $db->prepare(
                "UPDATE lost_and_found_items SET status = 'claimed', claimed_by_name = ?, claimed_by_phone = ?, claimed_at = NOW() WHERE id = ? AND status = 'unclaimed'"
            );
            $stmt->execute([$name, $phone, $id]);

            $_SESSION['lf_message'] = 'Your claim has been submitted! Please see the committee to collect your item.';
            $_SESSION['lf_message_type'] = 'success';
            header('Location: /lost-and-found/public');
            exit;
        } catch (\Exception $e) {
            $_SESSION['lf_message'] = 'Failed to submit claim: ' . $e->getMessage();
            $_SESSION['lf_message_type'] = 'danger';
            header('Location: /lost-and-found/claim?id=' . $id);
            exit;
        }
    }
}