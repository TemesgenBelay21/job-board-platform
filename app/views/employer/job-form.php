<?php
/**
 * The create and edit form for a job listing.
 *
 * A null $job means "post a new listing". On edit the current values are used
 * and the live listing notice explains that saving sends it back for approval.
 *
 * Expects: $company, $job (array|null), $categories, $jobTypes
 */
$editing = !empty($job);
$typeLabels = [
    'full_time' => 'Full time',
    'part_time' => 'Part time',
    'contract' => 'Contract',
    'internship' => 'Internship',
    'remote' => 'Remote',
];
?>
<div class="page-head">
    <div>
        <h1 class="page-head__title"><?= $editing ? 'Edit listing' : 'Post a job' ?></h1>
        <p class="page-head__subtitle">Posting as <?= e($company['company_name']) ?>.</p>
    </div>
</div>

<?php if ($editing && $job['status'] === 'open'): ?>
    <div class="alert alert--info">
        This listing is live. Saving your changes sends it for approval again, so
        nothing edited reaches the job search without a review.
    </div>
<?php endif; ?>

<?php if ($editing): ?>
    <div class="alert alert--warning">
        Only an admin can publish a listing. Yours goes to the review queue and
        appears in the job search once it is approved.
    </div>
<?php endif; ?>

<form method="post"
      action="<?= $editing ? url('employer/jobs/' . (int) $job['id']) : url('employer/jobs') ?>"
      class="form-card">
    <?= csrf_field() ?>

    <div class="field">
        <label class="field__label" for="title">Job title <span class="field__required" aria-hidden="true">*</span></label>
        <input class="input" type="text" id="title" name="title" maxlength="160" required
               value="<?= e(old('title', $editing ? $job['title'] : '')) ?>"
               placeholder="Senior backend developer">
    </div>

    <div class="field-row">
        <div class="field">
            <label class="field__label" for="category_id">Category</label>
            <select class="input" id="category_id" name="category_id">
                <option value="">No category</option>
                <?php foreach ($categories as $category): ?>
                    <?php $selected = (int) old('category_id', $editing ? $job['category_id'] : 0) === (int) $category['id']; ?>
                    <option value="<?= (int) $category['id'] ?>"<?= $selected ? ' selected' : '' ?>>
                        <?= e($category['name']) ?> (<?= e($category['job_count']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label class="field__label" for="job_type">Job type <span class="field__required" aria-hidden="true">*</span></label>
            <select class="input" id="job_type" name="job_type" required>
                <?php foreach ($jobTypes as $type): ?>
                    <?php
                    $current = (string) old('job_type', $editing ? $job['job_type'] : 'full_time');
                    $selected = $current === $type;
                    ?>
                    <option value="<?= e($type) ?>"<?= $selected ? ' selected' : '' ?>>
                        <?= e(isset($typeLabels[$type]) ? $typeLabels[$type] : humanize($type)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="field">
        <label class="field__label" for="location">Location <span class="field__required" aria-hidden="true">*</span></label>
        <input class="input" type="text" id="location" name="location" maxlength="150" required
               value="<?= e(old('location', $editing ? $job['location'] : $company['location'])) ?>"
               placeholder="Addis Ababa, Ethiopia">
    </div>

    <div class="field-row">
        <div class="field">
            <label class="field__label" for="salary_min">Salary from</label>
            <input class="input" type="number" id="salary_min" name="salary_min" min="0" step="1000"
                   value="<?= e(old('salary_min', $editing && $job['salary_min'] !== null ? $job['salary_min'] : '')) ?>">
            <p class="field__hint">Leave both blank if you would rather discuss pay.</p>
        </div>

        <div class="field">
            <label class="field__label" for="salary_max">Salary to</label>
            <input class="input" type="number" id="salary_max" name="salary_max" min="0" step="1000"
                   value="<?= e(old('salary_max', $editing && $job['salary_max'] !== null ? $job['salary_max'] : '')) ?>">
        </div>
    </div>

    <div class="field">
        <label class="field__label" for="deadline">Closing date</label>
        <input class="input" type="date" id="deadline" name="deadline"
               value="<?= e(old('deadline', $editing && $job['deadline'] !== null ? $job['deadline'] : '')) ?>">
        <p class="field__hint">The listing leaves the search by itself once this date passes.</p>
    </div>

    <div class="field">
        <label class="field__label" for="description">Description <span class="field__required" aria-hidden="true">*</span></label>
        <textarea class="input" id="description" name="description" rows="10" maxlength="8000" required
                  placeholder="What the role involves, who you are looking for and how the team works."><?= e(old('description', $editing ? $job['description'] : '')) ?></textarea>
    </div>

    <div class="field">
        <label class="field__label" for="requirements">Requirements</label>
        <textarea class="input" id="requirements" name="requirements" rows="6" maxlength="4000"
                  placeholder="One per line, or leave blank."><?= e(old('requirements', $editing && $job['requirements'] !== null ? $job['requirements'] : '')) ?></textarea>
    </div>

    <div class="form-actions">
        <a class="btn btn--ghost" href="<?= url('employer/jobs') ?>">Cancel</a>
        <button class="btn btn--primary" type="submit" data-once>
            <?= $editing ? 'Save changes' : 'Submit listing' ?>
        </button>
    </div>
</form>
