<?php
$message = $_SESSION['form_message'] ?? null;
$messageType = $_SESSION['form_message_type'] ?? 'info';
if (isset($_SESSION['form_message'])) {
    unset($_SESSION['form_message'], $_SESSION['form_message_type']);
}
?>
<h2>Create New Form</h2>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="post" action="/forms/store" id="form-builder">
    <div class="card mb-3">
        <div class="card-header">Form Details</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Form Title *</label>
                <input type="text" name="title" class="form-control" required placeholder="e.g., Event Evaluation Form">
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Brief description of the form"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Target Audience</label>
                <select name="target_audience" class="form-select">
                    <option value="all">All (Participants, Crew, Committee)</option>
                    <option value="participant">Participants Only</option>
                    <option value="crew">Crew Only</option>
                    <option value="committee">Committee Only</option>
                </select>
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
                <!-- Fields will be added here dynamically -->
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Create Form</button>
    <a href="/forms" class="btn btn-secondary">Cancel</a>
</form>

<script>
let fieldCount = 0;

function addField() {
    fieldCount++;
    const container = document.getElementById('fields-container');
    const fieldHtml = `
        <div class="card mb-3 field-item" data-field-id="${fieldCount}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0">Field ${fieldCount}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeField(${fieldCount})">Remove</button>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Field Label *</label>
                        <input type="text" name="fields[${fieldCount}][label]" class="form-control" required placeholder="e.g., Overall Rating">
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
                        <input type="text" name="fields[${fieldCount}][placeholder]" class="form-control" placeholder="Optional placeholder text">
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
                    <textarea name="fields[${fieldCount}][options]" class="form-control" rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                    <small class="text-muted">For dropdown, radio, or checkbox fields</small>
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

// Add first field on page load
document.addEventListener('DOMContentLoaded', function() {
    addField();
});
</script>
