<?php
/**
 * VOTER LOGIN PAGE
 * 
 * This page handles voter authentication:
 * - Validates voter ID and password
 * - Checks if voter account is active
 * - Prevents already-voted users from logging in again
 * - Creates session for authenticated voters
 */

include 'db.php';
session_start(); // Start session to store voter login state
$msg=''; // Message variable for error/success messages

// ============================================
// HANDLE LOGIN FORM SUBMISSION
// ============================================
if(isset($_POST['login'])){
    // Sanitize input to prevent SQL injection
    $id = $conn->real_escape_string($_POST['voterID']);
    $pass = $conn->real_escape_string($_POST['voterPass']);
    
    // Query to find voter with matching ID, password, and active status
    // BINARY ensures case-sensitive comparison for voterID
    $q = $conn->query("SELECT * FROM voters WHERE BINARY voterID='$id' AND voterPass='$pass' AND voterStat='active'");
    
    if($q->num_rows==1){
        // Login credentials are valid
        $v = $q->fetch_assoc();
        
        // Check if voter has already voted
        if($v['voted']=='Y') {
            $msg = "You have already voted. Voting is not allowed again.";
        } else {
            // Create session and redirect to voting page
            $_SESSION['voterID'] = $id;
            header("Location: vote.php");
            exit; // Stop script execution after redirect
        }
    } else {
        // Invalid credentials or inactive account
        $msg = "Invalid login or inactive account.";
    }
}
?>
<!DOCTYPE html><html>
<head><title>Voter Login</title><link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"></head>
<body class="w3-container">
  <h3>Voter Login</h3>
  
  <!-- Display error messages (if any) -->
  <?php if($msg) echo "<div class='w3-panel w3-pale-red'>$msg</div>"; ?>
  
  <!-- ============================================
       LOGIN FORM
       ============================================ -->
  <form method="post" class="w3-container w3-card-4 w3-padding" style="width:400px">
    <label>Voter ID</label><input class="w3-input" name="voterID" required>
    <label>Password</label><input class="w3-input" name="voterPass" type="password" required>
    <button class="w3-button w3-green w3-margin-top" name="login">Login to Vote</button>
  </form>
  
  <p><a class="w3-button" href="index.php">Back</a></p>
</body></html>
