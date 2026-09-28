<?php
/**
 * Account management, with role, status and keyword filters.
 *
 * Expects: $results, $activeRole, $activeStatus, $keyword, $roles
 * The signed in admin arrives as the shared $user from the dashboard layout.
 */
$rows = $results['rows'];
$baseQuery = [];

if ($activeRole !== '') {
    $baseQuery['role'] = $activeRole;
}
if ($activeStatus !== '') {
    $baseQuery['status'] = $activeStatus;
}
if ($keyword !== '') {
    $baseQuery['q'] = $keyword;
}
?>
<div class="page-head">
    <div>
        <h1 class="page-head__title">Users</h1>
        <p class="page-head__subtitle"><?= e($results['total']) ?> account<?= $results['total'] === 1 ? '' : 's' ?> match your filters.</p>
    </div>
</div>

<form method="get" action="<?= url('admin/users') ?>" class="filter-form">
    <div class="field">
        <label class="field__label" for="q">Search</label>
        <input class="input" type="search" id="q" name="q" value="<?= e($keyword) ?>" placeholder="Name or email">
    </div>

    <div class="field">
        <label class="field__label" for="role">Role</label>
        <select class="input" id="role" name="role">
            <option value="">All roles</option>
            <?php foreach ($roles as $role): ?>
                <option value="<?= e($role) ?>"<?= $activeRole === $role ? ' selected' : '' ?>><?= e(humanize($role)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label class="field__label" for="status">Status</label>
        <select class="input" id="status" name="status">
            <option value="">Any status</option>
            <option value="active"<?= $activeStatus === 'active' ? ' selected' : '' ?>>Active</option>
            <option value="inactive"<?= $activeStatus === 'inactive' ? ' selected' : '' ?>>Inactive</option>
        </select>
    </div>

    <div class="form-actions">
        <button class="btn btn--primary" type="submit">Filter</button>
        <a class="btn btn--ghost" href="<?= url('admin/users') ?>">Clear</a>
    </div>
</form>

<?php if (empty($rows)): ?>
    <div class="empty-state">
        <h2 class="empty-state__title">No accounts match</h2>
        <p class="empty-state__text">Try a different search or clear the filters.</p>
        <a class="btn btn--primary" href="<?= url('admin/users') ?>">Clear filters</a>
    </div>
<?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Role</th>
                    <th scope="col">Status</th>
                    <th scope="col">Joined</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $user): ?>
                    <?php
                    $id = (int) $user['id'];
                    $isSelf = $id === (int) $currentUserId;
                    $returnTo = url('admin/users', $baseQuery);
                    ?>
                    <tr>
                        <td><strong><?= e($user['name']) ?></strong><?php if ($isSelf): ?><span class="badge badge--muted">You</span><?php endif; ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><?= e(humanize($user['role'])) ?></td>
                        <td>
                            <span class="badge badge--<?= $user['status'] === 'active' ? 'success' : 'neutral' ?>">
                                <?= e(humanize($user['status'])) ?>
                            </span>
                        </td>
                        <td class="num"><?= e(format_date($user['created_at'])) ?></td>
                        <td class="table__actions">
                            <?php if ($isSelf): ?>
                                <span class="text-subtle text-sm">Your own account</span>
                            <?php elseif ($user['status'] === 'active'): ?>
                                <form method="post" action="<?= url('admin/users/' . $id . '/status') ?>"
                                      data-confirm="Deactivate this account? They will not be able to sign in.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="inactive">
                                    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                                    <button class="btn btn--danger btn--small" type="submit">Deactivate</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?= url('admin/users/' . $id . '/status') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="active">
                                    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                                    <button class="btn btn--secondary btn--small" type="submit">Reactivate</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($results['last_page'] > 1): ?>
        <nav class="pagination" aria-label="Pagination">
            <?php if ($results['page'] > 1): ?>
                <a class="pagination__link" href="<?= url('admin/users', array_merge($baseQuery, ['page' => $results['page'] - 1])) ?>">Previous</a>
            <?php endif; ?>

            <span class="pagination__status">Page <?= e($results['page']) ?> of <?= e($results['last_page']) ?></span>

            <?php if ($results['page'] < $results['last_page']): ?>
                <a class="pagination__link" href="<?= url('admin/users', array_merge($baseQuery, ['page' => $results['page'] + 1])) ?>">Next</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
