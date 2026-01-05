<?php

return [
    'token'  => env('MOZESMS_TOKEN'),
    'sender' => env('MOZESMS_SENDER', 'ESHOP'),
    'url' => env('MOZESMS_URL', 'https://api.mozesms.com/sms/send'),
];
