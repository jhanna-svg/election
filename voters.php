<?php
/**
 * VOTERS MANAGEMENT PAGE
 * 
 * This page handles the management of voter accounts:
 * - Register new voters
 * - Edit existing voter information
 * - Activate/deactivate voter accounts
 * - Search and filter voters
 * - Display all voters with their status and voting status
 */

include 'db.php';
$msg=''; // Message variable to display success/error messages

// Check if we're editing a voter (voter ID passed via GET parameter)
$editId = isset($_GET['edit']) ? $conn->real_escape_string($_GET['edit']) : '';
$editData = null;
if($editId !== '') {
    // Fetch voter data for editing (BINARY ensures case-sensitive match)
    $editData = $conn->query("SELECT * FROM voters WHERE BINARY voterID='$editId'")->fetch_assoc();
}

// ============================================
// HANDLE FORM SUBMISSION (Add or Update)
// ============================================
if(isset($_POST['save'])) {
    // Sanitize input data to prevent SQL injection
    $id = $conn->real_escape_string($_POST['voterID']);
    $fname = $conn->real_escape_string($_POST['voterFName']);
    $mname = $conn->real_escape_string($_POST['voterMName']);
    $lname = $conn->real_escape_string($_POST['voterLName']);
    $currentId = isset($_POST['currentVoterID']) ? $conn->real_escape_string($_POST['currentVoterID']) : '';
    
    if($currentId !== '') {
        // ===== UPDATE EXISTING VOTER =====
        if($id !== $currentId) {
            // Voter ID is being changed - check if new ID already exists
            $exists = $conn->query("SELECT voterID FROM voters WHERE BINARY voterID='$id' LIMIT 1");
            if($exists && $exists->num_rows > 0) {
                $msg = "Voter ID already exists.";
            } else {
                // Password is optional when updating (only update if provided)
                $pass = isset($_POST['voterPass']) && trim($_POST['voterPass']) !== '' ? $conn->real_escape_string($_POST['voterPass']) : '';
                $set = "voterID='$id', voterFName='$fname', voterMName='$mname', voterLName='$lname'";
                if($pass !== '') {
                    $set .= ", voterPass='$pass'";
                }
                
                // Temporarily disable foreign key checks to update related votes table
                $conn->query("SET FOREIGN_KEY_CHECKS=0");
                // Update voterID in votes table (cascade update)
                $conn->query("UPDATE votes SET voterID='$id' WHERE BINARY voterID='$currentId'");
                // Update voter information
                $result = $conn->query("UPDATE voters SET $set WHERE BINARY voterID='$currentId'");
                // Re-enable foreign key checks
                $conn->query("SET FOREIGN_KEY_CHECKS=1");
                
                if($result) {
                    $msg="Voter updated.";
                    $editId = '';
                    $editData = null;
                } else {
                    $msg = "Error updating voter: " . $conn->error;
                }
            }
        } else {
            // Voter ID is not being changed - just update other fields
            $pass = isset($_POST['voterPass']) && trim($_POST['voterPass']) !== '' ? $conn->real_escape_string($_POST['voterPass']) : '';
            $set = "voterFName='$fname', voterMName='$mname', voterLName='$lname'";
            if($pass !== '') {
                $set .= ", voterPass='$pass'";
            }
            $result = $conn->query("UPDATE voters SET $set WHERE BINARY voterID='$currentId'");
            if($result) {
                $msg="Voter updated.";
                $editId = '';
                $editData = null;
            } else {
                $msg = "Error updating voter: " . $conn->error;
            }
        }
    } else {
        // ===== ADD NEW VOTER =====
        $pass = $conn->real_escape_string($_POST['voterPass']);
        // Check if voter ID already exists
        $exists = $conn->query("SELECT voterID FROM voters WHERE BINARY voterID='$id' LIMIT 1");
        if($exists && $exists->num_rows > 0) {
            $msg = "Voter ID already exists.";
        } else {
            // Insert new voter (default status is 'active', voted='N')
            $conn->query("INSERT INTO voters (voterID,voterPass,voterFName,voterMName,voterLName) VALUES ('$id','$pass','$fname','$mname','$lname')");
            $msg="Voter registered.";
        }
    }
}

// ============================================
// HANDLE DEACTIVATE ACTION
// ============================================
if(isset($_GET['deact'])) {
    $id = $conn->real_escape_string($_GET['deact']);
    // Set voter status to inactive (cannot login or vote)
    $conn->query("UPDATE voters SET voterStat='inactive' WHERE BINARY voterID='$id'");
    $msg="Voter deactivated.";
}

// ============================================
// HANDLE ACTIVATE ACTION
// ============================================
if(isset($_GET['activate'])) {
    $id = $conn->real_escape_string($_GET['activate']);
    // Set voter status to active (can login and vote)
    $conn->query("UPDATE voters SET voterStat='active' WHERE BINARY voterID='$id'");
    $msg="Voter activated.";
}

// ============================================
// HANDLE SEARCH FUNCTIONALITY
// ============================================
$searchTerm = '';
$filter = '';
if(isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searchTerm = trim($_GET['search']);
    $safeSearch = $conn->real_escape_string($searchTerm); // Sanitize search term
    // Build WHERE clause to search in voter ID and all name fields
    $filter = "WHERE voterID LIKE '%$safeSearch%' OR voterFName LIKE '%$safeSearch%' OR voterMName LIKE '%$safeSearch%' OR voterLName LIKE '%$safeSearch%'";
}

// Fetch all voters (with optional search filter)
$voters = $conn->query("SELECT * FROM voters $filter ORDER BY voterID");

?>
<!DOCTYPE html><html>
<head><title>Voters</title><link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"></head>
<body class="w3-container">
  <p><a class="w3-button" href="index.php">Back</a></p>
  <h3>Voters Management</h3>
  
  <!-- Display messages with appropriate styling (red for errors, green for success) -->
  <?php if($msg) {
      $msgClass = (strpos($msg, 'Error') !== false || strpos($msg, 'already exists') !== false) ? 'w3-pale-red' : 'w3-pale-green';
      echo "<div class='w3-panel $msgClass'>$msg</div>";
  } ?>

  <!-- ============================================
       ADD/EDIT VOTER FORM
       ============================================ -->
  <form method="post" class="w3-container w3-card-4 w3-padding">
    <?php if($editId !== ''): ?>
      <!-- Hidden field to store current voter ID (for update operations) -->
      <input type="hidden" name="currentVoterID" value="<?php echo htmlentities($editId); ?>">
      <h4>Edit Voter</h4>
    <?php else: ?>
      <h4>Add New Voter</h4>
    <?php endif; ?>
    
    <!-- Voter ID input (username for login) -->
    <label>Voter ID (username)</label><input class="w3-input" name="voterID" value="<?php echo $editData ? htmlentities($editData['voterID']) : ''; ?>" required>
    
    <!-- Password input (optional when editing, required when adding) -->
    <label>Password <?php echo $editId !== '' ? '(leave blank to keep current)' : ''; ?></label><input class="w3-input" name="voterPass" type="password" <?php echo $editId === '' ? 'required' : ''; ?>>
    
    <!-- Voter name fields -->
    <label>First name</label><input class="w3-input" name="voterFName" value="<?php echo $editData ? htmlentities($editData['voterFName']) : ''; ?>" required>
    <label>Middle name</label><input class="w3-input" name="voterMName" value="<?php echo $editData ? htmlentities($editData['voterMName']) : ''; ?>">
    <label>Last name</label><input class="w3-input" name="voterLName" value="<?php echo $editData ? htmlentities($editData['voterLName']) : ''; ?>" required>
    
    <!-- Submit button (changes text based on edit/add mode) -->
    <button class="w3-button w3-green w3-margin-top" name="save"><?php echo $editId !== '' ? 'Update Voter' : 'Register Voter'; ?></button>
    <?php if($editId !== ''): ?>
      <!-- Cancel button to exit edit mode (preserves search term) -->
      <a class="w3-button w3-gray w3-margin-top" href="voters.php<?php echo $searchTerm ? '?search='.urlencode($searchTerm) : ''; ?>">Cancel</a>
    <?php endif; ?>
  </form>

  <!-- ============================================
       SEARCH FORM
       ============================================ -->
  <form method="get" class="w3-container w3-card-2 w3-padding w3-margin-top" style="max-width:400px">
    <label>Search voters</label>
    <input class="w3-input" name="search" value="<?php echo htmlentities($searchTerm); ?>" placeholder="Search by ID or name">
    <button class="w3-button w3-blue w3-margin-top">Search</button>
    <?php if($searchTerm !== ''): ?>
      <!-- Clear search link (only shown when search is active) -->
      <a class="w3-button w3-tiny w3-margin-top" href="voters.php">Clear</a>
    <?php endif; ?>
  </form>

  <!-- ============================================
       VOTERS LIST TABLE
       ============================================ -->
  <h4>All Voters</h4>
  <table class="w3-table w3-striped w3-bordered">
    <tr><th>ID</th><th>Name</th><th>Status</th><th>Voted?</th><th>Actions</th></tr>
    <?php while($r = $voters->fetch_assoc()): ?>
      <tr>
        <td><?php echo $r['voterID']; ?></td>
        <!-- Display full name (first + middle + last) -->
        <td><?php echo htmlentities($r['voterFName'].' '.$r['voterMName'].' '.$r['voterLName']); ?></td>
        <td><?php echo $r['voterStat']; ?></td> <!-- active or inactive -->
        <td><?php echo $r['voted']; ?></td> <!-- Y or N -->
        <td>
          <!-- Edit link (preserves search term in URL) -->
          <a class="w3-button w3-blue w3-tiny" href="?edit=<?php echo urlencode($r['voterID']); ?><?php echo $searchTerm ? '&search='.urlencode($searchTerm) : ''; ?>">Edit</a>
          <?php if($r['voterStat'] === 'active'): ?>
            <!-- Deactivate button (only shown for active voters) -->
            <a class="w3-button w3-red w3-tiny" href="?deact=<?php echo urlencode($r['voterID']); ?><?php echo $searchTerm ? '&search='.urlencode($searchTerm) : ''; ?>">Deactivate</a>
          <?php else: ?>
            <!-- Activate button (only shown for inactive voters) -->
            <a class="w3-button w3-green w3-tiny" href="?activate=<?php echo urlencode($r['voterID']); ?><?php echo $searchTerm ? '&search='.urlencode($searchTerm) : ''; ?>">Activate</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>

</body></html>
