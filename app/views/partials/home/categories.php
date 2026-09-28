<?php
/**
 * Home page: category shortcuts into filtered search.
 *
 * Expects: $categories
 */
?>
<div class="section__header">
    <div>
        <h2>Browse by category</h2>
        <p>Jump straight into the field you work in.</p>
    </div>
    <a class="btn btn--ghost" href="<?= url('jobs') ?>">All categories</a>
</div>

<div class="category-grid">
    <?php foreach ($categories as $category): ?>
        <a class="category-tile" href="<?= url('jobs', ['category' => (int) $category['id']]) ?>">
            <span class="category-tile__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                </svg>
            </span>
            <span><?= e($category['name']) ?></span>
            <span class="category-tile__count"><?= e(number_format((int) $category['open_job_count'])) ?></span>
        </a>
    <?php endforeach; ?>
</div>
