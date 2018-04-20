<?php

function recaptca_is_valid() {
    global $recaptcha_secret;
    $context = stream_context_create(array(
        'http' => array (
            'method' => 'POST',
            'content' => http_build_query(array(
                'secret'   => $recaptcha_secret,
                'response' => $_POST["g-recaptcha-response"]
            ))
        )
    ));
    $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    return json_decode($verify)->success == true;
}
