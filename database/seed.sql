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

-- ---------------------------------------------------------------------------
-- Job listings
-- ---------------------------------------------------------------------------
-- A realistic spread of statuses so every view has something to show: open
-- listings for public search, a pending one that admin moderation can approve,
-- and a closed one that proves closed roles disappear from public results.
--
-- Deadlines are relative to the seed date rather than hard coded, so the demo
-- never fills up with listings that expired years ago.
-- ---------------------------------------------------------------------------
INSERT INTO `jobs`
    (`id`, `company_id`, `category_id`, `title`, `description`, `requirements`, `location`, `job_type`, `salary_min`, `salary_max`, `deadline`, `status`, `created_at`)
VALUES
    (1, 1, 1, 'Senior PHP Developer',
     'You will own the dispatch and billing features of our freight platform, the system our customers run their day-to-day operations on. This is a product engineering role, not a maintenance role: you will talk to dispatchers, design the data model and write the code.',
     'At least 5 years of professional PHP experience, solid MySQL including query tuning, comfortable with modern JavaScript and REST APIs, experience with automated tests.',
     'Addis Ababa, Ethiopia', 'full_time', 85000, 120000, DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'open', DATE_SUB(NOW(), INTERVAL 6 DAY)),

    (2, 1, 2, 'Data Analyst',
     'Join a two-person data team embedded with the product group. You will turn raw delivery and pricing data into the dashboards our managers actually read, and you will be the person who notices when a number looks wrong.',
     'Strong SQL, working knowledge of Python or R, comfort with incomplete data and a habit of checking your own results.',
     'Addis Ababa, Ethiopia', 'full_time', NULL, NULL, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'open', DATE_SUB(NOW(), INTERVAL 4 DAY)),

    (3, 2, 3, 'Product Designer',
     'We are looking for a designer who is comfortable in the middle of ambiguity. You will take early concepts from client workshops all the way through to shipped interfaces, and you will defend your decisions with reasoning rather than taste.',
     'A portfolio showing shipped product work, fluency in Figma, experience running usability sessions, working knowledge of HTML and CSS.',
     'Addis Ababa, Ethiopia', 'full_time', 60000, 85000, DATE_ADD(CURDATE(), INTERVAL 21 DAY), 'open', DATE_SUB(NOW(), INTERVAL 3 DAY)),

    (4, 2, 4, 'Marketing Lead',
     'Own acquisition for the studio: campaigns, landing pages and the content calendar. You will report on what actually worked rather than what was planned.',
     '4+ years in performance marketing, hands-on with paid social and email, able to write and edit copy without a copywriter.',
     'Addis Ababa, Ethiopia', 'full_time', 50000, 70000, DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'open', DATE_SUB(NOW(), INTERVAL 2 DAY)),

    (5, 1, 6, 'Customer Support Specialist',
     'Be the first human our customers meet. You will handle email and phone support for freight operators, log recurring issues for the product team, and write the help articles that keep the same questions from arriving twice.',
     'Clear written English, patience, previous support experience welcome but not required.',
     'Addis Ababa, Ethiopia', 'full_time', 25000, 35000, DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'open', DATE_SUB(NOW(), INTERVAL 1 DAY)),

    (6, 2, 3, 'Junior UX Researcher',
     'A six month contract with a real possibility of extension. You will run discovery interviews and usability studies for two of our fintech clients.',
     '1+ year of research experience or a strong portfolio from study projects, interview and synthesis skills.',
     'Addis Ababa, Ethiopia', 'contract', 30000, 40000, DATE_ADD(CURDATE(), INTERVAL 28 DAY), 'open', DATE_SUB(NOW(), INTERVAL 1 DAY)),

    (7, 1, 1, 'DevOps Engineer',
     'Set up and own our deployment pipeline, monitoring and backup strategy. You will be the person the rest of the engineering team pages when something breaks at 2am, so we value calm over heroics.',
     'Strong Linux administration, CI/CD experience, comfortable reading and writing code in at least one application language.',
     'Remote', 'remote', 70000, 95000, DATE_ADD(CURDATE(), INTERVAL 35 DAY), 'open', DATE_SUB(NOW(), INTERVAL 5 DAY)),

    (8, 1, 8, 'Project Manager',
     'Coordinate delivery across two client projects. You will run the schedule, remove blockers and keep clients informed of anything that moves a date.',
     '3+ years managing software projects, formal PM training or equivalent experience, fluent English.',
     'Addis Ababa, Ethiopia', 'full_time', 55000, 75000, DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'pending', DATE_SUB(NOW(), INTERVAL 12 HOUR)),

    (9, 2, 5, 'Sales Development Representative',
     'Generate qualified pipeline for the studio. You will work closely with the founders on positioning and pricing, and you will be measured on pipeline created rather than revenue closed.',
     'Excellent spoken and written English, comfortable with outbound outreach, previous BDR or sales experience preferred.',
     'Addis Ababa, Ethiopia', 'full_time', 30000, 45000, DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'closed', DATE_SUB(NOW(), INTERVAL 20 DAY))
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `status` = VALUES(`status`);

-- ---------------------------------------------------------------------------
-- Applications
-- ---------------------------------------------------------------------------
-- `resume_path` is NULL in the seed because the sample files are not shipped;
-- the applicant views handle a missing resume gracefully.
-- ---------------------------------------------------------------------------
INSERT INTO `applications` (`id`, `job_id`, `seeker_id`, `cover_letter`, `status`, `applied_at`, `updated_at`) VALUES
    (1, 1, 1,
     'I have spent four years on PHP and MySQL in a similar domain and would like to bring that to your dispatch tooling. The billing model you describe is close to a problem I solved last year, and I would be glad to walk through how.',
     'under_review', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (2, 3, 2,
     'My Figma portfolio covers two shipped mobile products, and I have run usability sessions in my last internship. I would like to start somewhere I can learn from senior designers on live client work.',
     'shortlisted', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
    (3, 5, 2,
     'I am looking for my first role and support is where I can learn the product quickly. I have handled customer email for a university helpdesk for two years.',
     'submitted', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY))
ON DUPLICATE KEY UPDATE
    `status` = VALUES(`status`);

-- ---------------------------------------------------------------------------
-- Saved jobs
-- ---------------------------------------------------------------------------
INSERT INTO `saved_jobs` (`seeker_id`, `job_id`, `created_at`) VALUES
    (1, 2, DATE_SUB(NOW(), INTERVAL 4 DAY)),
    (1, 5, DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (2, 1, DATE_SUB(NOW(), INTERVAL 2 DAY))
ON DUPLICATE KEY UPDATE `created_at` = VALUES(`created_at`);

-- ---------------------------------------------------------------------------
-- Notifications
-- ---------------------------------------------------------------------------
-- Matches the application statuses above so the notification bell has content
-- on a fresh install.
INSERT INTO `notifications` (`user_id`, `message`, `is_read`, `created_at`) VALUES
    (2, 'Your application for Senior PHP Developer is now under review.', 0, DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (2, 'Your application for Product Designer has been shortlisted.', 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),
    (2, 'We saved a new job matching your profile.', 1, DATE_SUB(NOW(), INTERVAL 5 DAY))
ON DUPLICATE KEY UPDATE `message` = VALUES(`message`);
