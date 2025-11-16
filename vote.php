<?php
/**
 * VOTING PAGE
 * 
 * This page allows authenticated voters to cast their votes:
 * - Displays all open positions with their candidates
 * - Allows voters to select candidates (single or multiple based on position)
 * - Validates vote limits (e.g., 1 for President, up to 12 for Senators)
 * - Records votes in the database
 * - Prevents double voting
 */

include 'db.php';
session_start(); // Start/resume session

// Check if voter is logged in (session must exist)
if(!isset($_SESSION['voterID'])) {
    // Redirect to login if not authenticated
    header("Location: login.php");
    exit;
}
$voter = $_SESSION['voterID']; // Get voter ID from session
$msg=''; // Message variable for success/error messages

// Get all open positions (only active positions appear in voting)
$posRes = $conn->query("SELECT * FROM positions WHERE posStat='open' ORDER BY posID");

// ============================================
// HANDLE VOTE SUBMISSION
// ============================================
if(isset($_POST['submit_votes'])) {
    // Verify voter status before processing votes
    $q = $conn->query("SELECT voted, voterStat FROM voters WHERE BINARY voterID='$voter'")->fetch_assoc();
    
    if(!$q || $q['voterStat']!='active') { 
        // Voter account is not active
        $msg = "Voter not active."; 
    } else if($q['voted']=='Y') { 
        // Voter has already voted (double voting prevention)
        $msg = "You already voted."; 
    } else {
        // Process votes
        $ok = true; // Flag to track if all validations pass
        
        // Loop through each position's votes
        foreach($_POST['votes'] as $posID => $candArray) {
            $posID = (int)$posID;
            
            // Get the maximum number of candidates allowed for this position
            $r = $conn->query("SELECT numOfPositions FROM positions WHERE posID=$posID")->fetch_assoc();
            $limit = (int)$r['numOfPositions'];
            
            // Count how many candidates were selected for this position
            $count = is_array($candArray) ? count($candArray) : 0;
            
            // Validate: Check if voter selected more candidates than allowed
            if($count > $limit) { 
                $ok = false; 
                $msg = "You selected too many candidates for a position."; 
                break; // Stop processing if validation fails
            }
            
            // Record each vote in the database
            if(is_array($candArray)){
                foreach($candArray as $candID){
                    $candID = (int)$candID;
                    // Insert vote record (one record per candidate selected)
                    $conn->query("INSERT INTO votes (posID, voterID, candID) VALUES ($posID, '".$conn->real_escape_string($voter)."', $candID)");
                }
            }
        }
        
        // If all validations passed, mark voter as having voted
        if($ok){
            $conn->query("UPDATE voters SET voted='Y' WHERE BINARY voterID='".$conn->real_escape_string($voter)."'");
            $msg = "Thank you. Your votes were recorded.";
        }
    }
}
?>
<!DOCTYPE html><html>
<head><title>Voting</title><link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"></head>
<body class="w3-container">
  <h3>Voting Page</h3>
  <!-- Display logged-in voter ID and logout option -->
  <p>Logged in as: <?php echo htmlentities($voter); ?> <a href="logout.php">(logout)</a></p>
  
  <!-- Display success/error messages -->
  <?php if($msg) echo "<div class='w3-panel w3-pale-green'>$msg</div>"; ?>

  <!-- ============================================
       VOTING FORM
       ============================================ -->
  <form method="post">
    <!-- Loop through each open position -->
    <?php while($pos = $posRes->fetch_assoc()): 
       $posID = $pos['posID'];
       $limit = (int)$pos['numOfPositions']; // Maximum number of candidates voter can select
       
       // Get all active candidates for this position
       $cands = $conn->query("SELECT * FROM candidates WHERE posID=$posID AND candStat='active'");
    ?>
      <!-- Position voting section -->
      <div class="w3-card-2 w3-padding w3-margin-bottom">
        <h4><?php echo htmlentities($pos['posName']); ?> (choose up to <?php echo $limit; ?>)</h4>
        
        <!-- Show message if no candidates available -->
        <?php if($cands->num_rows==0) echo "<p>No candidates.</p>"; ?>
        
        <!-- Display each candidate as a selectable option -->
        <?php while($c = $cands->fetch_assoc()): ?>
           <label class="w3-block">
            <?php if($limit>1): ?>
              <!-- Use checkboxes for positions with multiple slots (e.g., Senators) -->
              <input type="checkbox" name="votes[<?php echo $posID; ?>][]" value="<?php echo $c['candID']; ?>">
            <?php else: ?>
              <!-- Use radio buttons for positions with single slot (e.g., President) -->
              <input type="radio" name="votes[<?php echo $posID; ?>][]" value="<?php echo $c['candID']; ?>">
            <?php endif; ?>
            <!-- Display candidate full name -->
            <?php echo htmlentities($c['candFName'].' '.$c['candMName'].' '.$c['candLName']); ?>
          </label>
        <?php endwhile; ?>
      </div>
    <?php endwhile; ?>
    
    <!-- Submit button to cast votes -->
    <button class="w3-button w3-blue" name="submit_votes">Submit Votes</button>
  </form>

  <p><a class="w3-button" href="index.php">Back to menu</a></p>
</body></html>