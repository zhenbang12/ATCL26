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
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadDir = __DIR__ . '/../../uploads/lost_and_found/';

            // Ensure upload directory exists
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    $_SESSION['lf_message'] = 'Could not create uploads directory at: ' . $uploadDir;
                    $_SESSION['lf_message_type'] = 'danger';
                    header('Location: /lost-and-found/create');
                    exit;
                }
            }

            // Make sure directory is writable
            if (!is_writable($uploadDir)) {
                $_SESSION['lf_message'] = 'Uploads directory is not writable: ' . $uploadDir . ' (chmod needed)';
                $_SESSION['lf_message_type'] = 'danger';
                header('Location: /lost-and-found/create');
                exit;
            }

            // Check upload error code
            if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    1 => 'File exceeds upload_max_filesize',
                    2 => 'File exceeds MAX_FILE_SIZE',
                    3 => 'File only partially uploaded',
                    4 => 'No file was uploaded',
                    6 => 'Missing temp folder',
                    7 => 'Failed to write to disk',
                    8 => 'Upload stopped by extension',
                ];
                $errCode = $_FILES['photo']['error'];
                $errMsg = $uploadErrors[$errCode] ?? "Upload error code: $errCode";
                $_SESSION['lf_message'] = 'Photo upload failed: ' . $errMsg;
                $_SESSION['lf_message_type'] = 'danger';
                header('Location: /lost-and-found/create');
                exit;
            }

            // Validate file size
            if ($_FILES['photo']['size'] <= 0) {
                $_SESSION['lf_message'] = 'Uploaded file is empty (0 bytes).';
                $_SESSION['lf_message_type'] = 'danger';
                header('Location: /lost-and-found/create');
                exit;
            }

            // Validate extension
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExts, true)) {
                $_SESSION['lf_message'] = 'Invalid image type "' . $ext . '". Allowed: JPG, PNG, GIF, WEBP.';
                $_SESSION['lf_message_type'] = 'danger';
                header('Location: /lost-and-found/create');
                exit;
            }

            $photoFilename = 'lf_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $targetPath = $uploadDir . $photoFilename;

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                $_SESSION['lf_message'] = 'Failed to save photo. tmp_name=' . ($_FILES['photo']['tmp_name'] ?? 'null') . ' target=' . $targetPath . ' writable=' . (is_writable($uploadDir) ? 'yes' : 'no');
                $_SESSION['lf_message_type'] = 'danger';
                header('Location: /lost-and-found/create');
                exit;
            }

            // Verify file was actually saved
            if (!file_exists($targetPath)) {
                $photoFilename = null;
                $_SESSION['lf_message'] = 'Photo was uploaded but file does not exist on disk at: ' . $targetPath;
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
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK && $_FILES['photo']['size'] > 0) {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Validate image type with fallback
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExts, true)) {
                $_SESSION['lf_message'] = 'Invalid image type. Allowed: JPG, PNG, GIF, WEBP.';
                $_SESSION['lf_message_type'] = 'danger';
                header('Location: /lost-and-found/edit?id=' . $id);
                exit;
            }

            // Verify it's actually an image
            $imageInfo = @getimagesize($_FILES['photo']['tmp_name']);
            if ($imageInfo === false) {
                $_SESSION['lf_message'] = 'Uploaded file is not a valid image.';
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

            $photoFilename = 'lf_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $targetPath = $uploadDir . $photoFilename;

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                $_SESSION['lf_message'] = 'Failed to save photo. Check that the uploads directory is writable.';
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

    // Admin: Bulk delete selected items
    public function bulkDelete(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $ids = $_POST['ids'] ?? [];

        if (empty($ids)) {
            $_SESSION['lf_message'] = 'No items selected.';
            $_SESSION['lf_message_type'] = 'warning';
            header('Location: /lost-and-found');
            exit;
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, fn($id) => $id > 0);

        if (empty($ids)) {
            $_SESSION['lf_message'] = 'Invalid selection.';
            $_SESSION['lf_message_type'] = 'danger';
            header('Location: /lost-and-found');
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // Get photo files to delete
        $stmt = $db->prepare("SELECT photo_filename FROM lost_and_found_items WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $uploadDir = __DIR__ . '/../../uploads/lost_and_found/';

        foreach ($items as $item) {
            if ($item['photo_filename']) {
                $photoPath = $uploadDir . $item['photo_filename'];
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }
        }

        $stmt = $db->prepare("DELETE FROM lost_and_found_items WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $count = $stmt->rowCount();

        $_SESSION['lf_message'] = "$count item(s) deleted.";
        $_SESSION['lf_message_type'] = 'success';
        header('Location: /lost-and-found');
        exit;
    }

    // Admin: Bulk mark selected items as unclaimed
    public function bulkMarkUnclaimed(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $ids = $_POST['ids'] ?? [];

        if (empty($ids)) {
            $_SESSION['lf_message'] = 'No items selected.';
            $_SESSION['lf_message_type'] = 'warning';
            header('Location: /lost-and-found');
            exit;
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, fn($id) => $id > 0);

        if (empty($ids)) {
            $_SESSION['lf_message'] = 'Invalid selection.';
            $_SESSION['lf_message_type'] = 'danger';
            header('Location: /lost-and-found');
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare(
            "UPDATE lost_and_found_items SET status = 'unclaimed', claimed_by_name = NULL, claimed_by_phone = NULL, claimed_at = NULL WHERE id IN ($placeholders)"
        );
        $stmt->execute($ids);
        $count = $stmt->rowCount();

        $_SESSION['lf_message'] = "$count item(s) marked as unclaimed.";
        $_SESSION['lf_message_type'] = 'success';
        header('Location: /lost-and-found');
        exit;
    }

    // Admin: Bulk upload multiple photos at once
    public function bulkUpload(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $sid = $this->sid();
        $defaultCaption = trim((string)($_POST['default_caption'] ?? ''));
        $uploadDir = __DIR__ . '/../../uploads/lost_and_found/';

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                $_SESSION['lf_message'] = 'Could not create uploads directory.';
                $_SESSION['lf_message_type'] = 'danger';
                header('Location: /lost-and-found/create');
                exit;
            }
        }

        if (!is_writable($uploadDir)) {
            $_SESSION['lf_message'] = 'Uploads directory is not writable. Please chmod the uploads directory.';
            $_SESSION['lf_message_type'] = 'danger';
            header('Location: /lost-and-found/create');
            exit;
        }

        $files = $_FILES['photos'] ?? null;
        if (!$files || empty($files['name'][0])) {
            $_SESSION['lf_message'] = 'No photos selected.';
            $_SESSION['lf_message_type'] = 'warning';
            header('Location: /lost-and-found/create');
            exit;
        }

        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $uploaded = 0;
        $errors = 0;
        $insertStmt = $db->prepare(
            "INSERT INTO lost_and_found_items (session_id, photo_filename, caption, description) VALUES (?, ?, ?, ?)"
        );

        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK || $files['size'][$i] <= 0) {
                $errors++;
                continue;
            }

            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts, true)) {
                $errors++;
                continue;
            }

            $filename = 'lf_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $targetPath = $uploadDir . $filename;

            if (!move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                $errors++;
                continue;
            }

            $caption = $defaultCaption !== '' ? $defaultCaption : pathinfo($files['name'][$i], PATHINFO_FILENAME);
            $insertStmt->execute([$sid, $filename, $caption, '']);
            $uploaded++;
        }

        $msg = "$uploaded photo(s) uploaded.";
        if ($errors > 0) {
            $msg .= " $errors file(s) failed.";
        }
        $_SESSION['lf_message'] = $msg;
        $_SESSION['lf_message_type'] = $uploaded > 0 ? 'success' : 'danger';
        header('Location: /lost-and-found');
        exit;
    }
}
