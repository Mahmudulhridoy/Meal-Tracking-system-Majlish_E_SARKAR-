<?php
/**
 * Logout System- Majlish_E_sarkar
 * this files handles logout:
 * ends the user session
 * clears all stored session variables
 * desstroys session cookies
 * redirects user back to login page
 * This ensures the user cannot return to any protected page
 *  by pressing browser back button.
 */



// Start the session so we can destroy it
//required even during logout
session_start();

// Remove all session variables
// This clears: user_id, name, email, role, member_id, etc.
session_unset();



// Destroy the session completely
// This removes the session from server memory
session_destroy();



// Additionally delete the session cookie from the user's browser.
// This prevents session fixation attacks.
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(),  //cookie name
    '', //empty value
     time() - 3600, '/' //available accross the entire site
    ); 
}




// Redirect user to login page after logout
header("Location: login.php");
exit;
?>
