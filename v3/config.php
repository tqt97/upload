<?php
return [
    'columns' => [
        'required' => ['name', 'email'], // Cột bắt buộc
        'optional' => ['phone', 'address'], // Cột tùy chọn
    ],
    'validation_rules' => [
        'name' => 'required|max_length[255]', // Quy tắc cho cột name
        'email' => 'required|valid_email',   // Quy tắc cho cột email
        'phone' => 'valid_phone',            // Quy tắc cho cột phone (tùy chọn)
        'address' => 'max_length[500]',      // Quy tắc cho cột address (tùy chọn)
    ],
];
