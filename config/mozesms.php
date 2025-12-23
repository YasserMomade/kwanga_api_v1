<?php

return  [

    'base_url' => env('MOZESMS_BASE_URL', 'https://api.mozesms.com'),
    'token' => env('MOZESMS_TOKEN'),
    'sender_id' => env('MOZESMS_SENDER_ID', 'MozeSMS'),
    'timeout'   => env('MOZESMS_TIMEOUT', 15),


];
