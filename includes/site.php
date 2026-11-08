<?php
// The shop's own details. The About, Contact and legal pages all read them from here, so this is the one place to
// edit them. (The return and refund periods and the "last updated" date of the legal pages are in legal_pages.php.)

return [
    'name' => 'KITAB',
    'email' => 'hello@kitab.example',  // CHANGE: the address customers should write to (contact messages are mailed here too)
    'phone' => '',                     // optional: shown on the Contact page when filled in, e.g. '+977 98XXXXXXXX'
    'address' => 'Kathmandu, Nepal',   // CHANGE: where the shop is based
    'law' => 'Nepal',                  // CHANGE: country whose law applies
];
