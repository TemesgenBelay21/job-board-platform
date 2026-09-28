-- ---------------------------------------------------------------------------
-- Job Board Platform - seed data
-- ---------------------------------------------------------------------------
-- Demo content so the platform is browsable straight after a fresh install.
--
-- Credentials created here (also documented in the README):
--   admin@jobboard.test   / Admin@12345    (admin, never registrable)
--   sara@jobboard.test    / Password@123  (job seeker)
--   daniel@jobboard.test  / Password@123  (job seeker)
--   maya@northwind.test   / Password@123  (employer)
--   omar@brightlabs.test  / Password@123  (employer)
--
-- The script is safe to re-run: every insert upserts on its natural key.

USE `job_board_db`;

-- ---------------------------------------------------------------------------
-- Job categories
-- ---------------------------------------------------------------------------
INSERT INTO `categories` (`id`, `name`) VALUES
    (1, 'Software Development'),
    (2, 'Data Science'),
    (3, 'Design'),
    (4, 'Marketing'),
    (5, 'Sales'),
    (6, 'Customer Support'),
    (7, 'Human Resources'),
    (8, 'Project Management')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ---------------------------------------------------------------------------
-- Accounts
-- ---------------------------------------------------------------------------
-- The admin is only ever inserted here: registration rejects the admin role, so
-- this seed is the single way an administrator comes into existence.
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `status`) VALUES
    (1, 'Site Administrator', 'admin@jobboard.test',
     '$2y$10$xQEfyuGAZEjRIhOlbj.soODBkJoARLVbFr.jD5P7KqakYVEzQwGsq', 'admin', 'active'),
    (2, 'Sara Bekele', 'sara@jobboard.test',
     '$2y$10$M.zOx0eHld7lIHzmsDlzjehRvG0dLUMHOblvzj8Bb5vxwRduTfzHK', 'seeker', 'active'),
    (3, 'Daniel Tesfaye', 'daniel@jobboard.test',
     '$2y$10$M.zOx0eHld7lIHzmsDlzjehRvG0dLUMHOblvzj8Bb5vxwRduTfzHK', 'seeker', 'active'),
    (4, 'Maya Solomon', 'maya@northwind.test',
     '$2y$10$M.zOx0eHld7lIHzmsDlzjehRvG0dLUMHOblvzj8Bb5vxwRduTfzHK', 'employer', 'active'),
    (5, 'Omar Girma', 'omar@brightlabs.test',
     '$2y$10$M.zOx0eHld7lIHzmsDlzjehRvG0dLUMHOblvzj8Bb5vxwRduTfzHK', 'employer', 'active'),
    (6, 'Inactive Seeker', 'inactive@jobboard.test',
     '$2y$10$M.zOx0eHld7lIHzmsDlzjehRvG0dLUMHOblvzj8Bb5vxwRduTfzHK', 'seeker', 'inactive')
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `role` = VALUES(`role`),
    `status` = VALUES(`status`);

-- ---------------------------------------------------------------------------
-- Profiles
-- ---------------------------------------------------------------------------
INSERT INTO `seeker_profiles` (`id`, `user_id`, `phone`, `location`, `headline`, `skills`, `bio`) VALUES
    (1, 2, '+251 911 234 567', 'Addis Ababa', 'Backend developer focused on PHP and Laravel',
     'PHP, MySQL, JavaScript, REST APIs',
     'Four years building internal tools and public web products. I care about clean data models, testable code and interfaces that stay out of the way.'),
    (2, 3, '+251 922 887 120', 'Addis Ababa', 'Junior front-end developer',
     'HTML, CSS, JavaScript, Figma',
     'Computer science graduate looking for a first role where I can grow on real production code. I have shipped two small open-source front-end libraries.'),
    (3, 6, NULL, 'Bahir Dar', 'Recently graduated', 'Excel, Word', NULL)
ON DUPLICATE KEY UPDATE
    `headline` = VALUES(`headline`),
    `skills` = VALUES(`skills`);

INSERT INTO `company_profiles` (`id`, `user_id`, `company_name`, `description`, `website`, `location`) VALUES
    (1, 4, 'Northwind Systems',
     'Northwind Systems builds logistics software for East African freight operators. We are a 30-person team working from Addis Ababa and Nairobi, and we ship every week.',
     'https://northwind.example.com', 'Addis Ababa, Ethiopia'),
    (2, 5, 'Bright Labs',
     'Bright Labs is a design and research studio partnering with startups to take products from sketch to shipped. Small team, high trust, lots of client contact.',
     'https://brightlabs.example.com', 'Addis Ababa, Ethiopia')
ON DUPLICATE KEY UPDATE
    `company_name` = VALUES(`company_name`),
    `description` = VALUES(`description`);
