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

-- ---------------------------------------------------------------------------
-- categories
-- ---------------------------------------------------------------------------
-- The job categories employers pick from when posting a listing and seekers
-- filter by. Kept deliberately small and managed by admins.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(80)  NOT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- jobs
-- ---------------------------------------------------------------------------
-- Listings belong to a company profile rather than a user, so an employer's
-- history stays intact even if their account is deleted, and job browsing never
-- has to join through users to reach the company name.
--
-- status lifecycle:
--   pending  just posted by an employer, awaiting admin approval
--   open     approved by an admin and visible in public search
--   closed   filled, expired or withdrawn by the employer
--
-- salary_min / salary_max are nullable because plenty of listings would rather
-- discuss pay. When only one bound is given it is used for both ends of the
-- range comparison in the salary filter.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jobs` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`   INT UNSIGNED NOT NULL,
    `category_id`  INT UNSIGNED NULL,
    `title`        VARCHAR(160)  NOT NULL,
    `description`  TEXT          NOT NULL,
    `requirements` TEXT          NULL,
    `location`     VARCHAR(150)  NOT NULL,
    `job_type`     ENUM('full_time', 'part_time', 'contract', 'internship', 'remote')
                   NOT NULL DEFAULT 'full_time',
    `salary_min`   INT UNSIGNED NULL,
    `salary_max`   INT UNSIGNED NULL,
    `deadline`     DATE          NULL,
    `status`       ENUM('open', 'closed', 'pending') NOT NULL DEFAULT 'pending',
    `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    -- Public browsing filters and sorts on these, so they are indexed.
    KEY `idx_jobs_title` (`title`),
    KEY `idx_jobs_location` (`location`),
    KEY `idx_jobs_status` (`status`),
    KEY `idx_jobs_job_type` (`job_type`),
    KEY `idx_jobs_created_at` (`created_at`),
    -- Cover the two common access paths: "latest open jobs in a category" and
    -- "this company's listings by status".
    KEY `idx_jobs_status_category` (`status`, `category_id`),
    KEY `idx_jobs_company_status` (`company_id`, `status`),
    CONSTRAINT `fk_jobs_company`
        FOREIGN KEY (`company_id`) REFERENCES `company_profiles` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_jobs_category`
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- applications
-- ---------------------------------------------------------------------------
-- A seeker applying to a job. `resume_path` is copied from the seeker's
-- profile resume at the moment of applying (or replaced with a per application
-- upload) so a later profile resume change never rewrites history.
--
-- The unique key on (seeker_id, job_id) is the authoritative duplicate guard:
-- even if two requests race past the controller's own check, the second insert
-- fails instead of creating a duplicate row.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `applications` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `job_id`        INT UNSIGNED NOT NULL,
    `seeker_id`     INT UNSIGNED NOT NULL,
    `cover_letter`  TEXT         NULL,
    `resume_path`   VARCHAR(255) NULL,
    `status`        ENUM('submitted', 'under_review', 'shortlisted', 'rejected', 'hired')
                    NOT NULL DEFAULT 'submitted',
    `applied_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_applications_seeker_job` (`seeker_id`, `job_id`),
    KEY `idx_applications_job_status` (`job_id`, `status`),
    KEY `idx_applications_seeker` (`seeker_id`),
    KEY `idx_applications_status` (`status`),
    CONSTRAINT `fk_applications_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_applications_seeker`
        FOREIGN KEY (`seeker_id`) REFERENCES `seeker_profiles` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- saved_jobs
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `saved_jobs` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `seeker_id`  INT UNSIGNED NOT NULL,
    `job_id`     INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_saved_jobs_seeker_job` (`seeker_id`, `job_id`),
    KEY `idx_saved_jobs_job` (`job_id`),
    CONSTRAINT `fk_saved_jobs_seeker`
        FOREIGN KEY (`seeker_id`) REFERENCES `seeker_profiles` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_saved_jobs_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- notifications
-- ---------------------------------------------------------------------------
-- One row per message shown in the header bell. Kept as plain rows rather than
-- a separate read table so the unread count is a single indexed COUNT.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NOT NULL,
    `message`    VARCHAR(255) NOT NULL,
    `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notifications_user_read` (`user_id`, `is_read`),
    CONSTRAINT `fk_notifications_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- password_resets
-- ---------------------------------------------------------------------------
-- Forgot-password tokens. The raw token only ever exists in the email/link the
-- user receives: what we store is its SHA-256 hash, so a leaked database dump
-- cannot be turned into working reset links. `expires_at` bounds the window and
-- `used_at` records redemption so a link works exactly once.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NOT NULL,
    `token_hash` CHAR(64)     NOT NULL,
    `expires_at` DATETIME     NOT NULL,
    `used_at`    DATETIME     NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_password_resets_token` (`token_hash`),
    KEY `idx_password_resets_user` (`user_id`),
    KEY `idx_password_resets_expires` (`expires_at`),
    CONSTRAINT `fk_password_resets_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
