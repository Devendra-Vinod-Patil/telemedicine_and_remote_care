<?php
// Pharmacy store configuration.
// If you want online payments via Razorpay, set your key id below.
// If left blank, the store will fall back to Cash on Delivery (COD).

if (!defined('RAZORPAY_KEY_ID')) {
    define('RAZORPAY_KEY_ID', 'rzp_test_SjLx60TgrMWEua');
}

// Keep this secret out of your public repo. It is not used by the current flow
// (front-end checkout only), but saved here for future server-side verification.
if (!defined('RAZORPAY_KEY_SECRET')) {
    define('RAZORPAY_KEY_SECRET', '4MdHmbKAyB7RPWtFCJO5mQNx');
}
