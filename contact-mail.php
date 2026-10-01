<?php
/**
 * Contact form handler (contact.html) - emails the submission, returns JSON.
 */
require __DIR__ . '/form-mailer.php';

handle_form('Contact', [
    // key       => [label,     required, max length]
    'name'    => ['Name',    true, 100],
    'email'   => ['Email',   true, 150],
    'phone'   => ['Phone',   true, 30],
    'subject' => ['Subject', true, 200],
    'message' => ['Message', true, 5000],
]);
