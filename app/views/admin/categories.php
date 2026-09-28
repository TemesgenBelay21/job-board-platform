<?php
/**
 * Category management: add, rename and delete.
 *
 * Expects: $categories
 */
?>
<div class="page-head">
    <div>
        <h1 class="page-head__title">Categories</h1>
        <p class="page-head__subtitle">Used to group listings in the public search.</p>
    </div>
</div>

<form method="post" action="<?= url('admin/categories') ?>" class="form-card">
    <?= csrf_field() ?>

    <div class="field">
        <label class="field__label" for="name">New category</label>
        <input class="input" type="text" id="name" name="name" maxlength="80" required placeholder="Data engineering">
    </div>

    <div class="form-actions">
        <button class="btn btn--primary" type="submit">Add category</button>
    </div>
</form>

<?php if (empty($categories)): ?>
    <div class="empty-state">
        <h2 class="empty-state__title">No categories yet</h2>
        <p class="empty-state__text">Add one above so employers can classify their listings.</p>
    </div>
<?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Listings</th>
                    <th scope="col">Live</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <?php
                    $id = (int) $category['id'];
                    $inUse = (int) $category['job_count'] > 0;
                    $returnTo = url('admin/categories');
                    ?>
                    <tr>
                        <td>
                            <form method="post" action="<?= url('admin/categories/' . $id) ?>" class="status-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                                <label class="visually-hidden" for="name-<?= $id ?>">Name of category <?= e($category['name']) ?></label>
                                <input class="input input--sm" type="text" id="name-<?= $id ?>" name="name"
                                       maxlength="80" value="<?= e($category['name']) ?>">
                                <button class="btn btn--secondary btn--small" type="submit">Rename</button>
                            </form>
                        </td>
                        <td class="num"><?= e($category['job_count']) ?></td>
                        <td class="num"><?= e($category['open_job_count']) ?></td>
                        <td class="table__actions">
                            <?php if ($inUse): ?>
                                <span class="text-subtle text-sm" title="Move its listings to another category first">
                                    In use
                                </span>
                            <?php else: ?>
                                <form method="post" action="<?= url('admin/categories/' . $id . '/delete') ?>"
                                      data-confirm="Delete this category?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                                    <button class="btn btn--danger btn--small" type="submit">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
