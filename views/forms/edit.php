<?php
$message = $_SESSION['form_message'] ?? null;
$messageType = $_SESSION['form_message_type'] ?? 'info';
if (isset($_SESSION['form_message'])) {
    unset($_SESSION['form_message'], $_SESSION['form_message_type']);
}
?>
<h2>Edit Form</h2>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="post" action="/forms/update" id="form-builder">
    <input type="hidden" name="form_id" value="<?= $form['id'] ?>">
    
    <div class="card mb-3">
        <div class="card-header">Form Details</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Form Title *</label>
                <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($form['title']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($form['description']) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Target Audience</label>
                <select name="target_audience" class="form-select">
                    <option value="all" <?= $form['target_audience'] === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="participant" <?= $form['target_audience'] === 'participant' ? 'selected' : '' ?>>Participants Only</option>
                    <option value="crew" <?= $form['target_audience'] === 'crew' ? 'selected' : '' ?>>Crew Only</option>
                    <option value="committee" <?= $form['target_audience'] === 'committee' ? 'selected' : '' ?>>Committee Only</option>
                </select>
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" id="is_active" <?= $form['is_active'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Form is active (can be submitted)</label>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Form Fields</span>
            <button type="button" class="btn btn-sm btn-primary" onclick="addField()">+ Add Field</button>
        </div>
        <div class="card-body">
            <div id="fields-container">
                <?php foreach ($fields as $field): ?>
                    <div class="card mb-3 field-item" data-field-id="<?= $field['id'] ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">Field</h6>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeField(<?= $field['id'] ?>)">Remove</button>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Field Label *</label>
                                    <input type="text" name="fields[<?= $field['id'] ?>][label]" class="form-control" required value="<?= htmlspecialchars($field['field_label']) ?>">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Field Type</label>
                                    <select name="fields[<?= $field['id'] ?>][type]" class="form-select" onchange="toggleOptions(<?= $field['id'] ?>)">
                                        <?php
                                        $types = ['text', 'textarea', 'number', 'email', 'date', 'select', 'radio', 'checkbox', 'rating'];
                                        foreach ($types as $type):
                                        ?>
                                            <option value="<?= $type ?>" <?= $field['field_type'] === $type ? 'selected' : '' ?>><?= ucfirst($type) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Placeholder</label>
                                    <input type="text" name="fields[<?= $field['id'] ?>][placeholder]" class="form-control" value="<?= htmlspecialchars($field['placeholder']) ?>">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check mt-4">
                                        <input type="checkbox" name="fields[<?= $field['id'] ?>][required]" class="form-check-input" id="required_<?= $field['id'] ?>" <?= $field['is_required'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="required_<?= $field['id'] ?>">Required</label>
                                    </div>
                                </div>
                            </div>
                            <?php
                            $options = json_decode($field['field_options'] ?? '[]', true);
                            $showOptions = in_array($field['field_type'], ['select', 'radio', 'checkbox']);
                            ?>
                            <div class="mb-2" id="options_<?= $field['id'] ?>" style="display: <?= $showOptions ? 'block' : 'none' ?>;">
                                <label class="form-label">Options (one per line)</label>
                                <textarea name="fields[<?= $field['id'] ?>][options]" class="form-control" rows="3"><?= htmlspecialchars(implode("\n", $options)) ?></textarea>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Update Form</button>
    <a href="/forms" class="btn btn-secondary">Cancel</a>
</form>

<script>
let fieldCount = <?= !empty($fields) ? max(array_column($fields, 'id')) : 0 ?>;

function addField() {
    fieldCount++;
    const container = document.getElementById('fields-container');
    const fieldHtml = `
        <div class="card mb-3 field-item" data-field-id="${fieldCount}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0">New Field</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeField(${fieldCount})">Remove</button>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Field Label *</label>
                        <input type="text" name="fields[${fieldCount}][label]" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Field Type</label>
                        <select name="fields[${fieldCount}][type]" class="form-select" onchange="toggleOptions(${fieldCount})">
                            <option value="text">Text</option>
                            <option value="textarea">Textarea</option>
                            <option value="number">Number</option>
                            <option value="email">Email</option>
                            <option value="date">Date</option>
                            <option value="select">Dropdown</option>
                            <option value="radio">Radio Buttons</option>
                            <option value="checkbox">Checkboxes</option>
                            <option value="rating">Rating (1-5)</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Placeholder</label>
                        <input type="text" name="fields[${fieldCount}][placeholder]" class="form-control">
                    </div>
                    <div class="col-md-6 mb-2">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="fields[${fieldCount}][required]" class="form-check-input" id="required_${fieldCount}">
                            <label class="form-check-label" for="required_${fieldCount}">Required</label>
                        </div>
                    </div>
                </div>
                <div class="mb-2" id="options_${fieldCount}" style="display: none;">
                    <label class="form-label">Options (one per line)</label>
                    <textarea name="fields[${fieldCount}][options]" class="form-control" rows="3"></textarea>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', fieldHtml);
}

function removeField(id) {
    document.querySelector(`[data-field-id="${id}"]`).remove();
}

function toggleOptions(id) {
    const select = document.querySelector(`[name="fields[${id}][type]"]`);
    const optionsDiv = document.getElementById(`options_${id}`);
    if (['select', 'radio', 'checkbox'].includes(select.value)) {
        optionsDiv.style.display = 'block';
    } else {
        optionsDiv.style.display = 'none';
    }
}
</script>
