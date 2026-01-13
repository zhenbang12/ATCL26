<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Container;
use App\Core\Auth;

class FormController
{
    public function index(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Form Management';
        $db = Container::get('db');

        $stmt = $db->query('SELECT f.*, COUNT(fs.id) as submission_count 
            FROM forms f 
            LEFT JOIN form_submissions fs ON f.id = fs.form_id 
            GROUP BY f.id 
            ORDER BY f.created_at DESC');
        $forms = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/forms/index.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function create(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $title = 'Create Form';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/forms/create.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function store(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $user = Auth::user();

        $db->beginTransaction();
        try {
            // Insert form
            $stmt = $db->prepare('INSERT INTO forms (title, description, target_audience, created_by) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                $_POST['title'] ?? '',
                $_POST['description'] ?? '',
                $_POST['target_audience'] ?? 'all',
                $user['username'] ?? 'Unknown',
            ]);

            $formId = (int)$db->lastInsertId();

            // Insert form fields
            if (isset($_POST['fields']) && is_array($_POST['fields'])) {
                $fieldStmt = $db->prepare('INSERT INTO form_fields (form_id, field_label, field_type, field_options, is_required, field_order, placeholder) VALUES (?, ?, ?, ?, ?, ?, ?)');
                
                foreach ($_POST['fields'] as $order => $field) {
                    if (empty($field['label'])) continue;
                    
                    $options = '';
                    if (in_array($field['type'], ['select', 'radio', 'checkbox']) && !empty($field['options'])) {
                        $options = json_encode(explode("\n", trim($field['options'])));
                    }
                    
                    $fieldStmt->execute([
                        $formId,
                        $field['label'],
                        $field['type'] ?? 'text',
                        $options,
                        isset($field['required']) ? 1 : 0,
                        $order,
                        $field['placeholder'] ?? '',
                    ]);
                }
            }

            $db->commit();
            $_SESSION['form_message'] = 'Form created successfully!';
            $_SESSION['form_message_type'] = 'success';
            header('Location: /forms');
            exit;
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['form_message'] = 'Error creating form: ' . $e->getMessage();
            $_SESSION['form_message_type'] = 'danger';
            header('Location: /forms/create');
            exit;
        }
    }

    public function edit(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $formId = (int)($_GET['id'] ?? 0);

        $stmt = $db->prepare('SELECT * FROM forms WHERE id = ?');
        $stmt->execute([$formId]);
        $form = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$form) {
            header('Location: /forms');
            exit;
        }

        $stmt = $db->prepare('SELECT * FROM form_fields WHERE form_id = ? ORDER BY field_order');
        $stmt->execute([$formId]);
        $fields = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $title = 'Edit Form';
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/forms/edit.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function update(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $formId = (int)($_POST['form_id'] ?? 0);

        $db->beginTransaction();
        try {
            // Update form
            $stmt = $db->prepare('UPDATE forms SET title = ?, description = ?, target_audience = ?, is_active = ? WHERE id = ?');
            $stmt->execute([
                $_POST['title'] ?? '',
                $_POST['description'] ?? '',
                $_POST['target_audience'] ?? 'all',
                isset($_POST['is_active']) ? 1 : 0,
                $formId,
            ]);

            // Delete existing fields
            $db->prepare('DELETE FROM form_fields WHERE form_id = ?')->execute([$formId]);

            // Insert updated fields
            if (isset($_POST['fields']) && is_array($_POST['fields'])) {
                $fieldStmt = $db->prepare('INSERT INTO form_fields (form_id, field_label, field_type, field_options, is_required, field_order, placeholder) VALUES (?, ?, ?, ?, ?, ?, ?)');
                
                foreach ($_POST['fields'] as $order => $field) {
                    if (empty($field['label'])) continue;
                    
                    $options = '';
                    if (in_array($field['type'], ['select', 'radio', 'checkbox']) && !empty($field['options'])) {
                        $options = json_encode(explode("\n", trim($field['options'])));
                    }
                    
                    $fieldStmt->execute([
                        $formId,
                        $field['label'],
                        $field['type'] ?? 'text',
                        $options,
                        isset($field['required']) ? 1 : 0,
                        $order,
                        $field['placeholder'] ?? '',
                    ]);
                }
            }

            $db->commit();
            $_SESSION['form_message'] = 'Form updated successfully!';
            $_SESSION['form_message_type'] = 'success';
            header('Location: /forms');
            exit;
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['form_message'] = 'Error updating form: ' . $e->getMessage();
            $_SESSION['form_message_type'] = 'danger';
            header('Location: /forms/edit?id=' . $formId);
            exit;
        }
    }

    public function view(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $formId = (int)($_GET['id'] ?? 0);

        $stmt = $db->prepare('SELECT * FROM forms WHERE id = ?');
        $stmt->execute([$formId]);
        $form = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$form) {
            header('Location: /forms');
            exit;
        }

        $stmt = $db->prepare('SELECT * FROM form_fields WHERE form_id = ? ORDER BY field_order');
        $stmt->execute([$formId]);
        $fields = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $title = 'View Form: ' . htmlspecialchars($form['title']);
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/forms/view.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function submissions(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $formId = (int)($_GET['id'] ?? 0);

        $stmt = $db->prepare('SELECT * FROM forms WHERE id = ?');
        $stmt->execute([$formId]);
        $form = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$form) {
            header('Location: /forms');
            exit;
        }

        $stmt = $db->prepare('SELECT * FROM form_submissions WHERE form_id = ? ORDER BY submitted_at DESC');
        $stmt->execute([$formId]);
        $submissions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stmt = $db->prepare('SELECT * FROM form_fields WHERE form_id = ? ORDER BY field_order');
        $stmt->execute([$formId]);
        $fields = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $title = 'Form Submissions: ' . htmlspecialchars($form['title']);
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/forms/submissions.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    public function delete(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $formId = (int)($_POST['id'] ?? 0);

        try {
            $stmt = $db->prepare('DELETE FROM forms WHERE id = ?');
            $stmt->execute([$formId]);
            $_SESSION['form_message'] = 'Form deleted successfully!';
            $_SESSION['form_message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['form_message'] = 'Error deleting form: ' . $e->getMessage();
            $_SESSION['form_message_type'] = 'danger';
        }

        header('Location: /forms');
        exit;
    }

    /**
     * Show summary/analytics of form responses
     */
    public function summary(): void
    {
        Auth::requireRole(['advisor', 'committee']);

        $db = Container::get('db');
        $formId = (int)($_GET['id'] ?? 0);

        $stmt = $db->prepare('SELECT * FROM forms WHERE id = ?');
        $stmt->execute([$formId]);
        $form = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$form) {
            header('Location: /forms');
            exit;
        }

        // Get all submissions
        $stmt = $db->prepare('SELECT * FROM form_submissions WHERE form_id = ? ORDER BY submitted_at DESC');
        $stmt->execute([$formId]);
        $submissions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Get form fields
        $stmt = $db->prepare('SELECT * FROM form_fields WHERE form_id = ? ORDER BY field_order');
        $stmt->execute([$formId]);
        $fields = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Analyze submissions
        $summary = [];
        $totalSubmissions = count($submissions);

        foreach ($fields as $field) {
            $fieldLabel = $field['field_label'];
            $fieldType = $field['field_type'];
            $fieldData = [
                'label' => $fieldLabel,
                'type' => $fieldType,
                'total_responses' => 0,
                'response_rate' => 0,
            ];

            $responses = [];
            $values = [];

            foreach ($submissions as $submission) {
                $data = json_decode($submission['submission_data'], true);
                $value = $data[$fieldLabel] ?? null;

                if ($value !== null && $value !== '') {
                    $fieldData['total_responses']++;
                    
                    if ($fieldType === 'rating') {
                        $values[] = (int)$value;
                    } elseif ($fieldType === 'checkbox') {
                        if (is_array($value)) {
                            foreach ($value as $v) {
                                $responses[$v] = ($responses[$v] ?? 0) + 1;
                            }
                        }
                    } elseif (in_array($fieldType, ['select', 'radio'])) {
                        $responses[$value] = ($responses[$value] ?? 0) + 1;
                    } else {
                        $values[] = $value;
                    }
                }
            }

            if ($totalSubmissions > 0) {
                $fieldData['response_rate'] = round(($fieldData['total_responses'] / $totalSubmissions) * 100, 1);
            }

            // Calculate statistics based on field type
            if ($fieldType === 'rating' && !empty($values)) {
                $fieldData['average'] = round(array_sum($values) / count($values), 2);
                $fieldData['min'] = min($values);
                $fieldData['max'] = max($values);
                $fieldData['distribution'] = array_count_values($values);
            } elseif (!empty($responses)) {
                $fieldData['responses'] = $responses;
                arsort($fieldData['responses']); // Sort by count descending
            } elseif (!empty($values)) {
                $fieldData['sample_responses'] = array_slice($values, 0, 10); // Show first 10 text responses
            }

            $summary[] = $fieldData;
        }

        $title = 'Form Summary: ' . htmlspecialchars($form['title']);
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/forms/summary.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }

    /**
     * Public form submission endpoint (for participants/crew)
     */
    public function submit(): void
    {
        $db = Container::get('db');
        $formId = (int)($_POST['form_id'] ?? 0);

        // Get form and check if active
        $stmt = $db->prepare('SELECT * FROM forms WHERE id = ? AND is_active = 1');
        $stmt->execute([$formId]);
        $form = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$form) {
            $_SESSION['submit_message'] = 'Form not found or inactive.';
            $_SESSION['submit_message_type'] = 'danger';
            header('Location: /forms/public?id=' . $formId);
            exit;
        }

        // Collect submission data
        $submissionData = [];
        $stmt = $db->prepare('SELECT * FROM form_fields WHERE form_id = ? ORDER BY field_order');
        $stmt->execute([$formId]);
        $fields = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($fields as $field) {
            $fieldName = 'field_' . $field['id'];
            $value = $_POST[$fieldName] ?? '';
            
            // Handle checkbox arrays
            if ($field['field_type'] === 'checkbox') {
                $value = $_POST[$fieldName] ?? [];
            }
            
            $submissionData[$field['field_label']] = $value;
        }

        // Determine submitter type
        $submitterType = 'participant';
        $submitterName = $_POST['submitter_name'] ?? 'Anonymous';
        $submitterId = null;

        // Try to identify if logged in
        if (Auth::check()) {
            $user = Auth::user();
            $submitterType = $user['role'] ?? 'participant';
            $submitterName = $user['username'] ?? $submitterName;
        }

        try {
            $stmt = $db->prepare('INSERT INTO form_submissions (form_id, submitted_by, submitted_by_type, submitted_by_id, submission_data) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                $formId,
                $submitterName,
                $submitterType,
                $submitterId,
                json_encode($submissionData),
            ]);

            $_SESSION['submit_message'] = 'Form submitted successfully! Thank you for your submission.';
            $_SESSION['submit_message_type'] = 'success';
            
            // Update submission count
            $stmt = $db->prepare('UPDATE forms SET submission_count = submission_count + 1 WHERE id = ?');
            $stmt->execute([$formId]);
        } catch (\Exception $e) {
            $_SESSION['submit_message'] = 'Error submitting form: ' . $e->getMessage();
            $_SESSION['submit_message_type'] = 'danger';
        }

        header('Location: /forms/public?id=' . $formId);
        exit;
    }

    /**
     * Public form view (for participants/crew to fill out)
     */
    public function publicForm(): void
    {
        $db = Container::get('db');
        $formId = (int)($_GET['id'] ?? 0);

        $stmt = $db->prepare('SELECT * FROM forms WHERE id = ? AND is_active = 1');
        $stmt->execute([$formId]);
        $form = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$form) {
            $title = 'Form Not Found';
            include __DIR__ . '/../../views/layout/header.php';
            echo '<div class="alert alert-danger">Form not found or inactive.</div>';
            include __DIR__ . '/../../views/layout/footer.php';
            return;
        }

        $stmt = $db->prepare('SELECT * FROM form_fields WHERE form_id = ? ORDER BY field_order');
        $stmt->execute([$formId]);
        $fields = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Get message from session and clear it
        $submitMessage = $_SESSION['submit_message'] ?? null;
        $submitMessageType = $_SESSION['submit_message_type'] ?? 'info';
        if (isset($_SESSION['submit_message'])) {
            unset($_SESSION['submit_message'], $_SESSION['submit_message_type']);
        }

        $title = htmlspecialchars($form['title']);
        include __DIR__ . '/../../views/layout/header.php';
        include __DIR__ . '/../../views/forms/public.php';
        include __DIR__ . '/../../views/layout/footer.php';
    }
}
