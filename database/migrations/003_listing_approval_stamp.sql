-- ---------------------------------------------------------------------------
-- 003 — listing approval stamp
--
-- New or substantively edited listings are held as pending for an
-- administrator to approve. approved_at records when that happened, which is
-- what lets a listing an employer closed go live again without a second
-- review.
--
-- schema.sql already includes this column, so a fresh install needs none of
-- this. Run it against an existing database.
-- ---------------------------------------------------------------------------

SET @column_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'jobs'
      AND COLUMN_NAME  = 'approved_at'
);

SET @sql = IF(
    @column_exists > 0,
    'SELECT ''jobs.approved_at already exists, nothing to do'' AS message',
    'ALTER TABLE `jobs` ADD COLUMN `approved_at` DATETIME NULL AFTER `status`'
);

PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

-- Backfill: a listing that is live now was approved at some point, so give it
-- a stamp rather than leaving it indistinguishable from a fresh install.
UPDATE `jobs` SET `approved_at` = `created_at` WHERE `status` = 'open' AND `approved_at` IS NULL;

-- A closed listing that was never reviewed has nothing to fall back on, so
-- leave its stamp NULL. That is what makes it re-openable only through review.
