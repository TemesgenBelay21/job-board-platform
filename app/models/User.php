<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Accounts of every role.
 *
 * Passwords are hashed on the way in with password_hash() and checked with
 * password_verify(); the plain password is never stored, logged or returned.
 */
class User extends Model
{
    protected $table = 'users';

    /** Only these columns can be written by create()/update(). */
    protected $fillable = [
        'name',
        'email',
        'password_hash',
        'role',
        'status',
    ];

    /** Roles that may be chosen on the public registration form. */
    const REGISTRABLE_ROLES = ['seeker', 'employer'];

    const ROLES = ['seeker', 'employer', 'admin'];

    public function findByEmail($email)
    {
        return Database::fetch(
            'SELECT * FROM `users` WHERE `email` = ? LIMIT 1',
            [strtolower(trim($email))]
        );
    }

    public function emailExists($email, $exceptId = null)
    {
        $sql = 'SELECT COUNT(*) FROM `users` WHERE `email` = ?';
        $params = [strtolower(trim($email))];

        if ($exceptId !== null) {
            $sql .= ' AND `id` <> ?';
            $params[] = (int) $exceptId;
        }

        return (int) Database::fetchColumn($sql, $params) > 0;
    }

    /**
     * Create an account, hashing the password.
     *
     * @return int The new user id
     */
    public function register($name, $email, $password, $role, $status = 'active')
    {
        return $this->create([
            'name' => trim($name),
            'email' => strtolower(trim($email)),
            'password_hash' => self::hashPassword($password),
            'role' => $role,
            'status' => $status,
        ]);
    }

    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Check a set of credentials.
     *
     * The caller must report a single generic message either way, so this
     * returns false for an unknown email, a wrong password and a deactivated
     * account alike.
     *
     * @return array|null The user row on success, null on failure
     */
    public function attempt($email, $password)
    {
        $user = $this->findByEmail($email);

        if ($user === null) {
            // Spend roughly the same time as a real verification so response
            // timing does not reveal which emails are registered.
            password_verify($password, '$2y$10$usesomesillystringforsalttoavoidtimingleaksxx');

            return null;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        if ($user['status'] !== 'active') {
            return null;
        }

        return $user;
    }

    /**
     * A password reset requires a fresh hash, so the session that performed the
     * reset cannot survive it.
     */
    public function updatePassword($userId, $password)
    {
        return $this->update($userId, [
            'password_hash' => self::hashPassword($password),
        ]);
    }

    public function setStatus($userId, $status)
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new \InvalidArgumentException('Unknown user status: ' . $status);
        }

        return $this->update($userId, ['status' => $status]);
    }

    public function countByRole($role = null)
    {
        if ($role === null) {
            return $this->count();
        }

        return $this->count('`role` = ?', [$role]);
    }

    public function countByStatus($status)
    {
        return $this->count('`status` = ?', [$status]);
    }

    /**
     * List accounts for the admin table, with an optional role filter and a
     * keyword search over name and email.
     */
    public function search($options = [])
    {
        $where = [];
        $params = [];

        if (!empty($options['role']) && in_array($options['role'], self::ROLES, true)) {
            $where[] = '`role` = ?';
            $params[] = $options['role'];
        }

        if (!empty($options['status'])) {
            $where[] = '`status` = ?';
            $params[] = $options['status'];
        }

        if (!empty($options['q'])) {
            $where[] = '(`name` LIKE ? OR `email` LIKE ?)';
            $like = '%' . $options['q'] . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $page = isset($options['page']) ? (int) $options['page'] : 1;
        $perPage = isset($options['per_page']) ? (int) $options['per_page'] : 15;

        return $this->paginate(implode(' AND ', $where), $params, $page, $perPage, '`created_at` DESC, `id` DESC');
    }

    /** Recently registered accounts, for the admin dashboard. */
    public function recent($limit = 5)
    {
        return $this->findAll('`created_at` DESC, `id` DESC', $limit);
    }
}
