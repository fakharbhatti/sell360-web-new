<?php
/**
 * Demo request form handler (getdemo.html) - emails the submission, returns JSON.
 */
require __DIR__ . '/form-mailer.php';

handle_form('Demo Request', [
    // key        => [label,                            required, max length]
    'name'     => ['Name',                             true,  100],
    'phone'    => ['Phone',                            true,  30],
    'email'    => ['Email',                            true,  150],
    'company'  => ['Company Name',                     false, 150],
    'industry' => ['Industry',                         false, 150],
    'sales_no' => ['No. of on-field sales personnel',  false, 20],
    'city'     => ['City',                             true,  100],
    'message'  => ['Message',                          true,  5000],
]);
