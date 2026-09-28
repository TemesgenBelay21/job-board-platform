<?php
/**
 * Home page: the four steps of using the platform.
 */
$steps = [
    [
        'title' => 'Create your profile',
        'text' => 'Sign up as a job seeker and add your headline, skills and contact details. Upload your resume once and reuse it for every application.',
    ],
    [
        'title' => 'Search and filter',
        'text' => 'Narrow listings by keyword, category, location, job type and salary range, so you only look at roles worth your time.',
    ],
    [
        'title' => 'Apply in a couple of clicks',
        'text' => 'Pick the resume you want to send, add a short cover letter and submit. You cannot apply twice to the same job, by accident or otherwise.',
    ],
    [
        'title' => 'Track every response',
        'text' => 'See each application move from submitted to under review, shortlisted or hired, with a notification the moment anything changes.',
    ],
];
?>
<div class="section__header">
    <div>
        <h2>How it works</h2>
        <p>Four steps from looking to hired.</p>
    </div>
</div>

<div class="steps">
    <?php foreach ($steps as $step): ?>
        <article class="step">
            <span class="step__number" aria-hidden="true"></span>
            <h3><?= e($step['title']) ?></h3>
            <p><?= e($step['text']) ?></p>
        </article>
    <?php endforeach; ?>
</div>
