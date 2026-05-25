<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Container;
use App\Core\Auth;
use App\Core\SessionHelper;

class OperationsController
{
    /** Return active session_id shortcut */
    private function sid(): int
    {
        return SessionHelper::currentSessionId();
    }

    public function index(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Event Operations & Crew';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/operations/index.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function crew(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Crew Management';
        $db = Container::get('db');
        $sid = $this->sid();
        $stmt = $db->prepare('SELECT id, full_name, role, assigned_group_code, is_facilitator FROM crew WHERE session_id = ? ORDER BY full_name');
        $stmt->execute([$sid]);
        $crew = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/operations/crew.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function createCrew(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Add Crew';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/operations/crew_create.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function storeCrew(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $role = trim((string)($_POST['role'] ?? ''));
        $isFacilitator = isset($_POST['is_facilitator']) ? 1 : 0;

        if ($fullName === '') {
            $_SESSION['crew_message'] = 'Crew name is required.';
            $_SESSION['crew_message_type'] = 'danger';
            header('Location: /operations/crew/create');
            exit;
        }

        try {
            $sid = $this->sid();
            $stmt = $db->prepare('
                INSERT INTO crew (session_id, full_name, email, role, assigned_group_code, is_medic, is_facilitator)
                VALUES (?, ?, ?, ?, NULL, 0, ?)
            ');
            $stmt->execute([$sid, $fullName, $email, $role, $isFacilitator]);

            $_SESSION['crew_message'] = 'Crew added successfully.';
            $_SESSION['crew_message_type'] = 'success';
            header('Location: /operations/crew');
            exit;
        } catch (\Exception $e) {
            $_SESSION['crew_message'] = 'Failed to add crew: ' . $e->getMessage();
            $_SESSION['crew_message_type'] = 'danger';
            header('Location: /operations/crew/create');
            exit;
        }
    }

    public function editCrew(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db  = Container::get('db');
        $id  = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['crew_message'] = 'Invalid crew member.';
            $_SESSION['crew_message_type'] = 'danger';
            header('Location: /operations/crew');
            exit;
        }

        $stmt = $db->prepare('SELECT id, full_name, email, role, is_facilitator, assigned_group_code FROM crew WHERE id = ?');
        $stmt->execute([$id]);
        $crewMember = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$crewMember) {
            $_SESSION['crew_message'] = 'Crew member not found.';
            $_SESSION['crew_message_type'] = 'danger';
            header('Location: /operations/crew');
            exit;
        }

        $title = 'Edit Crew Member';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/operations/crew_edit.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function updateCrew(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db           = Container::get('db');
        $id           = (int)($_POST['id'] ?? 0);
        $fullName     = trim((string)($_POST['full_name'] ?? ''));
        $email        = trim((string)($_POST['email'] ?? ''));
        $role         = trim((string)($_POST['role'] ?? ''));
        $isFacilitator = isset($_POST['is_facilitator']) ? 1 : 0;

        if ($id <= 0 || $fullName === '') {
            $_SESSION['crew_message'] = 'Name is required.';
            $_SESSION['crew_message_type'] = 'danger';
            header('Location: /operations/crew/edit?id=' . $id);
            exit;
        }

        try {
            if ($isFacilitator === 0) {
                // Removing facilitator role clears the group assignment too
                $stmt = $db->prepare('UPDATE crew SET full_name = ?, email = ?, role = ?, is_facilitator = 0, assigned_group_code = NULL WHERE id = ?');
                $stmt->execute([$fullName, $email, $role, $id]);
            } else {
                $stmt = $db->prepare('UPDATE crew SET full_name = ?, email = ?, role = ?, is_facilitator = 1 WHERE id = ?');
                $stmt->execute([$fullName, $email, $role, $id]);
            }

            if ($stmt->rowCount() === 0) {
                $_SESSION['crew_message'] = 'Crew member not found.';
                $_SESSION['crew_message_type'] = 'danger';
            } else {
                $_SESSION['crew_message'] = 'Crew member updated successfully.';
                $_SESSION['crew_message_type'] = 'success';
            }
        } catch (\Exception $e) {
            $_SESSION['crew_message'] = 'Failed to update crew: ' . $e->getMessage();
            $_SESSION['crew_message_type'] = 'danger';
        }

        header('Location: /operations/crew');
        exit;
    }


    public function updateFacilitator(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $crewId = (int)($_POST['crew_id'] ?? 0);
        $isFacilitator = isset($_POST['is_facilitator']) ? 1 : 0;

        if ($crewId <= 0) {
            $_SESSION['crew_message'] = 'Invalid crew selected.';
            $_SESSION['crew_message_type'] = 'danger';
            header('Location: /operations/crew');
            exit;
        }

        try {
            if ($isFacilitator === 0) {
                // If removing facilitator role, clear group assignment too.
                $stmt = $db->prepare('UPDATE crew SET is_facilitator = 0, assigned_group_code = NULL WHERE id = ?');
                $stmt->execute([$crewId]);
            } else {
                $stmt = $db->prepare('UPDATE crew SET is_facilitator = 1 WHERE id = ?');
                $stmt->execute([$crewId]);
            }

            $_SESSION['crew_message'] = 'Facilitator status updated.';
            $_SESSION['crew_message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['crew_message'] = 'Failed to update facilitator status: ' . $e->getMessage();
            $_SESSION['crew_message_type'] = 'danger';
        }

        header('Location: /operations/crew');
        exit;
    }

    public function deleteCrew(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $crewId = (int)($_POST['crew_id'] ?? 0);

        if ($crewId <= 0) {
            $_SESSION['crew_message'] = 'Invalid crew selected.';
            $_SESSION['crew_message_type'] = 'danger';
            header('Location: /operations/crew');
            exit;
        }

        try {
            $db->beginTransaction();

            $delAttendance = $db->prepare('DELETE FROM crew_attendance WHERE crew_id = ?');
            $delAttendance->execute([$crewId]);

            $delCrew = $db->prepare('DELETE FROM crew WHERE id = ?');
            $delCrew->execute([$crewId]);

            if ($delCrew->rowCount() === 0) {
                $db->rollBack();
                $_SESSION['crew_message'] = 'Crew member not found.';
                $_SESSION['crew_message_type'] = 'danger';
            } else {
                $db->commit();
                $_SESSION['crew_message'] = 'Crew member removed.';
                $_SESSION['crew_message_type'] = 'success';
            }
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['crew_message'] = 'Failed to delete crew: ' . $e->getMessage();
            $_SESSION['crew_message_type'] = 'danger';
        }

        header('Location: /operations/crew');
        exit;
    }

    public function games(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Games & Scores';
        $db = Container::get('db');
        $stmt = $db->query('SELECT g.id, g.name, s.group_code, s.score FROM games g LEFT JOIN scores s ON g.id = s.game_id ORDER BY g.name, s.group_code');
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/operations/games.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }
}

