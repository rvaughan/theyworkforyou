<?php

// The login form page.

/*
    If the form hasn't been submitted, display_page() is called and the form shown.
    If the form has been submitted we check the input.
    If the input is OK, the user is logged in and taken to wherever they were before.
    If the input is not OK, the form is displayed again with error messages.
*/

$new_style_template = TRUE;

include_once '../../../includes/easyparliament/init.php';
# need to include this as login code uses error_message
include_once '../../../includes/easyparliament/page.php';
$login = new \MySociety\TheyWorkForYou\FacebookLogin();

global $this_page, $DATA;

$this_page = 'topic';

$data = $login->handleFacebookRedirect();

if (isset($data['token'])) {
  $login->loginUser($data['token']);
} else {
    \MySociety\TheyWorkForYou\Renderer::output('login/facebook', $data);
}
