<?php
// exam/includes/functions.php

// Example helper function (add more as needed)
function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
} 