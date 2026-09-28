-- ---------------------------------------------------------------------------
-- Job Board Platform - core accounts and profiles
-- ---------------------------------------------------------------------------
-- Creates the database and the tables that represent who uses the platform:
-- a single users table plus one profile table per non-admin role.

CREATE DATABASE IF NOT EXISTS `job_board_db`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `job_board_db`;

-- ---------------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------------
-- One row per person on the platform. Admins are created here directly and
-- are never registrable, so `role` is stored as an enum rather than a free
-- string. `status` lets an admin deactivate an account without deleting the
-- rows that foreign keys depend on.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(120)  NOT NULL,
    `email`         VARCHAR(190)  NOT NULL,
    `password_hash` VARCHAR(255)  NOT NULL,
    `role`          ENUM('seeker', 'employer', 'admin') NOT NULL,
    `status`        ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    -- Case-insensitive uniqueness: MySQL's default collation already treats
    -- 'Test@Example.com' and 'test@example.com' as the same address.
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role_status` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- company_profiles
-- ---------------------------------------------------------------------------
-- Employer-facing details. `logo` stores a filename relative to
-- public/uploads/logos, never a full path, so the folder can move freely.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `company_profiles` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NOT NULL,
    `company_name` VARCHAR(150) NOT NULL,
    `description`  TEXT         NULL,
    `website`      VARCHAR(255) NULL,
    `location`     VARCHAR(150) NULL,
    `logo`         VARCHAR(255) NULL,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    -- A company account owns exactly one profile.
    UNIQUE KEY `uq_company_profiles_user` (`user_id`),
    KEY `idx_company_profiles_name` (`company_name`),
    CONSTRAINT `fk_company_profiles_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- seeker_profiles
-- ---------------------------------------------------------------------------
-- Job seeker details. `resume_path` stores a filename relative to
-- storage/resumes, which lives outside the public web root and is only ever
-- read through a permission-checked controller.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `seeker_profiles` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED NOT NULL,
    `phone`       VARCHAR(40)  NULL,
    `location`    VARCHAR(150) NULL,
    `headline`    VARCHAR(150) NULL,
    `skills`      VARCHAR(255) NULL,
    `bio`         TEXT         NULL,
    `resume_path` VARCHAR(255) NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_seeker_profiles_user` (`user_id`),
    KEY `idx_seeker_profiles_location` (`location`),
    CONSTRAINT `fk_seeker_profiles_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
