<h2><?= htmlspecialchars($form['title']) ?></h2>

<?php if (isset($submitMessage) && $submitMessage): ?>
    <div class="alert alert-<?= $submitMessageType ?> alert-dismissible fade show" role="alert">
        <?php if ($submitMessageType === 'success'): ?>
            <h5 class="alert-heading mb-2">Success!</h5>
            <p class="mb-0"><strong><?= htmlspecialchars($submitMessage) ?></strong></p>
        <?php else: ?>
            <strong><?= htmlspecialchars($submitMessage) ?></strong>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($form['description'])): ?>
    <p class="text-muted mb-4"><?= nl2br(htmlspecialchars($form['description'])) ?></p>
<?php endif; ?>

<form method="post" action="/forms/submit" class="mb-4">
    <input type="hidden" name="form_id" value="<?= $form['id'] ?>">
    
    <?php if (!\App\Core\Auth::check()): ?>
        <div class="card mb-3">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Your Name</label>
                    <input type="text" name="submitter_name" class="form-control" placeholder="Enter your name">
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php foreach ($fields as $field): ?>
        <div class="card mb-3">
            <div class="card-body">
                <label class="form-label">
                    <?= htmlspecialchars($field['field_label']) ?>
                    <?php if ($field['is_required']): ?>
                        <span class="text-danger">*</span>
                    <?php endif; ?>
                </label>

                <?php
                $fieldName = 'field_' . $field['id'];
                $options = json_decode($field['field_options'] ?? '[]', true);
                ?>

                <?php if ($field['field_type'] === 'text'): ?>
                    <input type="text" name="<?= $fieldName ?>" class="form-control" 
                           placeholder="<?= htmlspecialchars($field['placeholder']) ?>" 
                           <?= $field['is_required'] ? 'required' : '' ?>>

                <?php elseif ($field['field_type'] === 'textarea'): ?>
                    <textarea name="<?= $fieldName ?>" class="form-control" rows="4" 
                              placeholder="<?= htmlspecialchars($field['placeholder']) ?>" 
                              <?= $field['is_required'] ? 'required' : '' ?>></textarea>

                <?php elseif ($field['field_type'] === 'number'): ?>
                    <input type="number" name="<?= $fieldName ?>" class="form-control" 
                           placeholder="<?= htmlspecialchars($field['placeholder']) ?>" 
                           <?= $field['is_required'] ? 'required' : '' ?>>

                <?php elseif ($field['field_type'] === 'email'): ?>
                    <input type="email" name="<?= $fieldName ?>" class="form-control" 
                           placeholder="<?= htmlspecialchars($field['placeholder']) ?>" 
                           <?= $field['is_required'] ? 'required' : '' ?>>

                <?php elseif ($field['field_type'] === 'date'): ?>
                    <input type="date" name="<?= $fieldName ?>" class="form-control" 
                           <?= $field['is_required'] ? 'required' : '' ?>>

                <?php elseif ($field['field_type'] === 'select'): ?>
                    <select name="<?= $fieldName ?>" class="form-select" <?= $field['is_required'] ? 'required' : '' ?>>
                        <option value="">-- Select --</option>
                        <?php foreach ($options as $option): ?>
                            <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars($option) ?></option>
                        <?php endforeach; ?>
                    </select>

                <?php elseif ($field['field_type'] === 'radio'): ?>
                    <?php foreach ($options as $option): ?>
                        <div class="form-check">
                            <input type="radio" name="<?= $fieldName ?>" id="<?= $fieldName ?>_<?= md5($option) ?>" 
                                   value="<?= htmlspecialchars($option) ?>" class="form-check-input" 
                                   <?= $field['is_required'] ? 'required' : '' ?>>
                            <label class="form-check-label" for="<?= $fieldName ?>_<?= md5($option) ?>">
                                <?= htmlspecialchars($option) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>

                <?php elseif ($field['field_type'] === 'checkbox'): ?>
                    <?php foreach ($options as $option): ?>
                        <div class="form-check">
                            <input type="checkbox" name="<?= $fieldName ?>[]" id="<?= $fieldName ?>_<?= md5($option) ?>" 
                                   value="<?= htmlspecialchars($option) ?>" class="form-check-input">
                            <label class="form-check-label" for="<?= $fieldName ?>_<?= md5($option) ?>">
                                <?= htmlspecialchars($option) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>

                <?php elseif ($field['field_type'] === 'rating'): ?>
                    <div class="btn-group" role="group">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <input type="radio" class="btn-check" name="<?= $fieldName ?>" id="<?= $fieldName ?>_<?= $i ?>" 
                                   value="<?= $i ?>" <?= $field['is_required'] ? 'required' : '' ?>>
                            <label class="btn btn-outline-primary" for="<?= $fieldName ?>_<?= $i ?>"><?= $i ?></label>
                        <?php endfor; ?>
                    </div>
                    <small class="text-muted d-block mt-2">1 = Poor, 5 = Excellent</small>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary btn-lg">Submit Form</button>
</form>
