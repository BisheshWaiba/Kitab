<?php
// The wording of the legal pages, shown by frontend/legal.php (one clean template for all of them).
//
// This is a DRAFT written from what the site actually does (see the Privacy policy for the data it keeps). It is not
// legal advice: read it through, change anything that is not true for your shop, and have it checked before launch.
//
// The shop's name, email, address and law come from site.php; the periods and the date are set below.
// Texts can use these tokens:
//   {site} {email} {address} {law} {return_days} {refund_days}
//
// A page is: title, lead (opening paragraph) and sections. A section is 'h' (heading) plus any of
// 'p' (paragraphs), 'ul' (bullets), 'ol' (numbered steps) and 'after' (paragraphs below the list).

return [
    'site' => (require __DIR__ . '/site.php') + [
        'return_days' => 7,                // days a customer has to return a book
        'refund_days' => 7,                // days we take to refund a returned book
        'updated' => '2026-11-08',         // CHANGE whenever you edit the wording
    ],

    'pages' => [

        'privacy' => [
            'title' => 'Privacy policy',
            'lead' => 'This policy explains what personal information {site} collects, why, and what we do with it. We keep it to what an online bookstore needs.',
            'sections' => [
                ['h' => 'Who we are', 'p' => [
                    '{site} is an online bookstore based in {address}. We decide how the information described here is used. You can reach us at {email}.',
                ]],
                ['h' => 'What we collect', 'ul' => [
                    'Account details: your name, email address and password. Passwords are stored only in scrambled (hashed) form, so nobody at {site} can read them.',
                    'Profile details you choose to add, such as a profile photo, phone number, address, city, date of birth, gender and your favourite genres. All of these are optional.',
                    'Order details: your name, email address, delivery address, the books you ordered and the order total.',
                    'Books you list for sale: the title, author, price, description, cover image and any other details you enter. These are shown publicly on the site.',
                    'Reviews you write: your rating and comment. They are shown publicly on the book\'s page with your first name and the first letter of your last name.',
                    'Messages you send us through the Contact page: your name, email address and what you write. We keep them to answer you.',
                    'Your wishlist and the books in your shopping cart.',
                    'Technical information your browser sends with every request, such as your IP address and browser type. Our web server may log it to keep the site secure and to fix faults.',
                ]],
                ['h' => 'How we use it', 'ul' => [
                    'To create and run your account and keep you signed in.',
                    'To process and deliver your orders, and to keep a record of them.',
                    'To show your listings, your reviews, your wishlist and your order history.',
                    'To answer the messages you send us.',
                    'To keep the site secure and to prevent misuse.',
                ], 'after' => [
                    'We do not sell your personal information and we do not use it for advertising.',
                ]],
                ['h' => 'Who we share it with', 'p' => [
                    'Only as far as needed to run the store. Delivery partners receive your name and delivery address so they can bring your order.',
                    'Our pages load fonts from Google Fonts and icons from cdnjs (Cloudflare). When your browser fetches them, those services receive your IP address and browser details.',
                    'We may also disclose information when the law requires it.',
                ]],
                ['h' => 'How long we keep it', 'p' => [
                    'We keep your account until you ask us to delete it. Order records are kept for as long as we need them for accounting and legal reasons. Messages you send us are kept for as long as we need them to deal with your request. Server logs are kept only for a short time.',
                ]],
                ['h' => 'Cookies', 'p' => [
                    'We use one essential cookie to keep you signed in and to remember your cart. Everything about it is in our Cookies policy.',
                ]],
                ['h' => 'Your choices', 'ul' => [
                    'You can look at and update your profile at any time from Settings in your account.',
                    'You can ask us for a copy of the information we hold about you, ask us to correct it, or ask us to delete your account. Email {email} and we will reply as soon as we can.',
                ], 'after' => [
                    'Deleting your account removes your profile and your personal details. We may keep order records that we are required to keep.',
                ]],
                ['h' => 'Security', 'p' => [
                    'We protect the site with standard measures, including hashing passwords. No system is perfectly secure, so please choose a strong password and keep it to yourself.',
                ]],
                ['h' => 'Children', 'p' => [
                    '{site} is not meant for children under 16, and we do not knowingly collect their information. If you think a child has given us information, email {email} and we will delete it.',
                ]],
                ['h' => 'Changes to this policy', 'p' => [
                    'If we change this policy, the date at the top of the page changes too. Please check back now and then.',
                ]],
            ],
        ],

        'cookies' => [
            'title' => 'Cookies policy',
            'lead' => 'Cookies are small files a website saves in your browser. This page explains the ones {site} uses.',
            'sections' => [
                ['h' => 'What we use', 'ul' => [
                    'A session cookie (named PHPSESSID). It is essential: it keeps you signed in, remembers the books in your cart and helps protect forms against forgery. Your browser deletes it when you close the browser.',
                ]],
                ['h' => 'What we do not use', 'p' => [
                    'No analytics, advertising or tracking cookies, and no cookies from social networks. Because we only use an essential cookie, we do not show a cookie banner.',
                ]],
                ['h' => 'Fonts and icons from other services', 'p' => [
                    'Our pages load fonts from Google Fonts and icons from cdnjs (Cloudflare). When your browser fetches those files, the services may receive your IP address and browser details. We do not control their logs; their own privacy policies explain what they do with it.',
                ]],
                ['h' => 'Managing cookies', 'p' => [
                    'You can block or delete cookies in your browser settings. If you block the session cookie you will not be able to sign in, use your cart or check out.',
                ]],
                ['h' => 'Changes to this policy', 'p' => [
                    'If we start using other cookies we will update this page first, and the date at the top will change.',
                ]],
            ],
        ],

        'returns' => [
            'title' => 'Return policy',
            'lead' => 'We want you to be happy with every book. If something is not right, this is how returns and refunds work.',
            'sections' => [
                ['h' => 'If you change your mind', 'p' => [
                    'You can return a book within {return_days} days of receiving it, as long as it is unused, undamaged and in the condition it arrived in. You pay the cost of sending it back.',
                ]],
                ['h' => 'Wrong, damaged or faulty books', 'p' => [
                    'If your book arrives damaged, is not the one you ordered or has a printing fault, tell us within {return_days} days of delivery. A photo helps. We will replace it or refund you in full, including delivery charges, at no cost to you.',
                ]],
                ['h' => 'What we cannot take back', 'ul' => [
                    'Books that have been written in, highlighted, water-damaged or otherwise used.',
                    'Books that were damaged after delivery.',
                    'Returns requested after the {return_days}-day window.',
                ]],
                ['h' => 'How to start a return', 'ol' => [
                    'Email {email} with your order number (you can find it under Order History in your account) and the reason for the return.',
                    'We will reply with instructions for sending the book back.',
                    'Pack the book carefully and send it as instructed.',
                ]],
                ['h' => 'Refunds', 'p' => [
                    'Once we have received and checked the returned book, we refund you within {refund_days} days. Orders are paid for on delivery, so refunds are sent by bank transfer or mobile wallet to the account you give us.',
                ]],
                ['h' => 'Cancelling an order', 'p' => [
                    'If your order has not shipped yet you can cancel it: email {email} as soon as possible with your order number. Once an order has shipped, the return rules above apply.',
                ]],
            ],
        ],

        'legal-warning' => [
            'title' => 'Legal warning',
            'lead' => 'This notice says who runs this website and sets out the basic rules for using it.',
            'sections' => [
                ['h' => 'Who runs this site', 'p' => [
                    '{site}, based in {address}. You can contact us at {email}.',
                ]],
                ['h' => 'Using the site', 'p' => [
                    'By using {site} you agree to use it lawfully. Please do not:',
                ], 'ul' => [
                    'try to break into, overload or disrupt the site;',
                    'use another person\'s account;',
                    'copy the site\'s content in bulk, for example with automated tools.',
                ], 'after' => [
                    'Your account is personal to you. Keep your password safe and tell us if you think someone else has used it.',
                ]],
                ['h' => 'Books listed by members', 'p' => [
                    'Members can list books for sale. The member who lists a book is responsible for describing it accurately and for having the right to sell it and to use its cover image. We may remove a listing that is inaccurate, unlawful or breaks these rules.',
                ]],
                ['h' => 'Reviews', 'p' => [
                    'Reviews should be honest and based on your own experience of the book. Please do not post anything abusive, unlawful or misleading, or advertising. You cannot review a book you listed yourself. You can edit or delete your own review at any time, and we may remove a review that breaks these rules.',
                ]],
                ['h' => 'Prices and availability', 'p' => [
                    'We work to keep prices and descriptions correct, but mistakes can happen. If we find a mistake in an order you have placed, we will contact you so you can confirm or cancel it before it ships.',
                ]],
                ['h' => 'Intellectual property', 'p' => [
                    'The {site} name, logo, design and text belong to us. Book covers and descriptions belong to their publishers and authors and are shown to help you recognise books. Please do not reuse any of it without permission.',
                ]],
                ['h' => 'Our liability', 'p' => [
                    'The site is provided as it is. As far as the law allows, we are not responsible for losses caused by the site being unavailable, by mistakes on it, or by other websites we link to. Nothing in this notice limits any right or liability that cannot be limited by law.',
                ]],
                ['h' => 'Governing law', 'p' => [
                    'These rules are governed by the law of {law}, and disputes are for the courts of {law}, unless a law that protects consumers gives you other rights.',
                ]],
                ['h' => 'Changes', 'p' => [
                    'We may change this notice. The date at the top shows when it was last updated.',
                ]],
            ],
        ],

        'giveaway-terms' => [
            'title' => 'Giveaway terms and conditions',
            'lead' => '{site} may run giveaways from time to time. We are not running one at the moment. These are the conditions that apply to any giveaway we run; each giveaway also states its own prize, dates and any extra rules.',
            'sections' => [
                ['h' => 'Who can enter', 'ul' => [
                    'People aged 18 or over who live in {law}.',
                    'People who work for {site}, and their close family, cannot enter.',
                ]],
                ['h' => 'How to enter', 'p' => [
                    'Enter in the way the giveaway describes. It is one entry per person unless the giveaway says otherwise, and no purchase is needed unless the giveaway clearly says so. Entries made with several accounts or with automated tools are disqualified.',
                ]],
                ['h' => 'Prizes', 'p' => [
                    'Prizes are as described in the giveaway. They cannot be exchanged for cash or passed on to someone else. If a prize becomes unavailable we may replace it with one of similar value.',
                ]],
                ['h' => 'Winners', 'p' => [
                    'Winners are picked as explained in the Legal bases of the draws. We tell winners by email. If a winner does not reply within 7 days, we may pick another.',
                ]],
                ['h' => 'Your information', 'p' => [
                    'The details you give to enter are used only to run the giveaway and to send the prize. Our Privacy policy explains how we look after them. We will not publish a winner\'s name without asking first.',
                ]],
                ['h' => 'Changes and cancellation', 'p' => [
                    'We may change, pause or cancel a giveaway if something outside our control makes it unfair or impossible to run. If we do, we will say so on the giveaway\'s page.',
                ]],
                ['h' => 'Governing law', 'p' => [
                    'These conditions are governed by the law of {law}.',
                ]],
            ],
        ],

        'draw-rules' => [
            'title' => 'Legal bases of the draws',
            'lead' => 'These are the official rules for how {site} organises a draw and picks its winners. They apply together with the Giveaway terms and conditions. We are not running a draw at the moment.',
            'sections' => [
                ['h' => 'Organiser', 'p' => [
                    '{site}, {address}. Questions go to {email}.',
                ]],
                ['h' => 'How a draw works', 'p' => [
                    'Every draw has an entry period that is published with it. When the period ends we check that the entries are valid and then pick the winners at random from them, for example with a random number generator or by drawing names. Our decision is final.',
                ]],
                ['h' => 'Checking the winners', 'p' => [
                    'Before a prize is sent, we check that the winner is eligible under the Giveaway terms. If they are not, we pick another winner at random. We keep a record of each draw: its date, the number of valid entries and how the winners were chosen.',
                ]],
                ['h' => 'Announcing the results', 'p' => [
                    'We email the winners within 7 days of the end of the entry period and post the result on the giveaway\'s page.',
                ]],
                ['h' => 'Complaints', 'p' => [
                    'If you think a draw was not run fairly, email {email} within 30 days of the result with the details. We will look into it and reply.',
                ]],
                ['h' => 'Why we use your information', 'p' => [
                    'We use the details of people who enter because they asked to take part, and to meet our legal duties. Once a draw is over and we no longer need them for our records, we delete them.',
                ]],
                ['h' => 'Governing law', 'p' => [
                    'Draws are governed by the law of {law}.',
                ]],
            ],
        ],
    ],
];
