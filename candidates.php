<?php
/**
 * CANDIDATES MANAGEMENT PAGE
 * 
 * This page handles the management of election candidates:
 * - Add new candidates
 * - Edit existing candidates
 * - Activate/deactivate candidates
 * - Search and filter candidates
 * - Display all candidates with their positions
 */

include 'db.php';
$msg=''; // Message variable to display success/error messages

// Check if we're editing a candidate (edit ID passed via GET parameter)
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editData = null;
if($editId > 0) {
    // Fetch candidate data for editing
    $editData = $conn->query("SELECT * FROM candidates WHERE candID=$editId")->fetch_assoc();
}

// Get all open positions for the dropdown (only active positions can have new candidates)
$positions = $conn->query("SELECT * FROM positions WHERE posStat='open'");

// ============================================
// HANDLE FORM SUBMISSION (Add or Update)
// ============================================
if(isset($_POST['save'])) {
    // Sanitize input data to prevent SQL injection
    $fname = $conn->real_escape_string($_POST['candFName']);
    $mname = $conn->real_escape_string($_POST['candMName']);
    $lname = $conn->real_escape_string($_POST['candLName']);
    $pos = (int)$_POST['posID']; // Position ID
    $id = isset($_POST['candID']) ? (int)$_POST['candID'] : 0; // Candidate ID (0 = new, >0 = update)
    
    if($id > 0) {
        // ===== UPDATE EXISTING CANDIDATE =====
        // Check if another candidate with same name exists for this position (excluding current candidate)
        $exists = $conn->query("SELECT candID FROM candidates WHERE candFName='$fname' AND candMName='$mname' AND candLName='$lname' AND posID=$pos AND candID!=$id LIMIT 1");
        if($exists && $exists->num_rows > 0) {
            $msg = "Another candidate with the same name already exists for this position.";
        } else {
            // Update candidate information
            $conn->query("UPDATE candidates SET candFName='$fname', candMName='$mname', candLName='$lname', posID=$pos WHERE candID=$id");
            $msg = "Candidate updated.";
            // Reset edit mode
            $editId = 0;
            $editData = null;
        }
    } else {
        // ===== ADD NEW CANDIDATE =====
        // Check if candidate with same name already exists for this position
        $exists = $conn->query("SELECT candID FROM candidates WHERE candFName='$fname' AND candMName='$mname' AND candLName='$lname' AND posID=$pos LIMIT 1");
        if($exists && $exists->num_rows > 0) {
            $msg = "Candidate already exists for this position.";
        } else {
            // Insert new candidate (default status is 'active')
            $conn->query("INSERT INTO candidates (candFName,candMName,candLName,posID) VALUES ('$fname','$mname','$lname',$pos)");
            $msg = "Candidate added.";
        }
    }
}

// ============================================
// HANDLE DEACTIVATE ACTION
// ============================================
if(isset($_GET['deact'])) {
    $id = (int)$_GET['deact'];
    // Set candidate status to inactive (won't appear in voting)
    $conn->query("UPDATE candidates SET candStat='inactive' WHERE candID=$id");
    $msg = "Candidate deactivated.";
}

// ============================================
// HANDLE ACTIVATE ACTION
// ============================================
if(isset($_GET['activate'])) {
    $id = (int)$_GET['activate'];
    // Set candidate status to active (will appear in voting)
    $conn->query("UPDATE candidates SET candStat='active' WHERE candID=$id");
    $msg = "Candidate activated.";
}

// ============================================
// HANDLE SEARCH FUNCTIONALITY
// ============================================
$searchTerm = '';
$filter = '';
if(isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searchTerm = trim($_GET['search']);
    $safe = $conn->real_escape_string($searchTerm); // Sanitize search term
    // Build WHERE clause to search in candidate names and position names
    $filter = "WHERE c.candFName LIKE '%$safe%' OR c.candMName LIKE '%$safe%' OR c.candLName LIKE '%$safe%' OR p.posName LIKE '%$safe%'";
}

// Fetch all candidates with their position names (join with positions table)
$cands = $conn->query("SELECT c.*, p.posName FROM candidates c LEFT JOIN positions p ON c.posID=p.posID $filter ORDER BY c.candID");
?>
<!DOCTYPE html><html>
<head><title>Candidates</title><link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"></head>
<body class="w3-container">
  <p><a class="w3-button" href="index.php">Back</a></p>
  <h3>Candidates Management</h3>
  
  <!-- Display success/error messages -->
  <?php if($msg) echo "<div class='w3-panel w3-pale-green'>$msg</div>"; ?>

  <!-- ============================================
       ADD/EDIT CANDIDATE FORM
       ============================================ -->
  <form method="post" class="w3-container w3-card-4 w3-padding">
    <?php if($editId > 0): ?>
      <!-- Hidden field to identify this as an update operation -->
      <input type="hidden" name="candID" value="<?php echo $editId; ?>">
      <h4>Edit Candidate</h4>
    <?php else: ?>
      <h4>Add New Candidate</h4>
    <?php endif; ?>
    
    <!-- Candidate name fields -->
    <label>First name</label><input class="w3-input" name="candFName" value="<?php echo $editData ? htmlentities($editData['candFName']) : ''; ?>" required>
    <label>Middle name</label><input class="w3-input" name="candMName" value="<?php echo $editData ? htmlentities($editData['candMName']) : ''; ?>">
    <label>Last name</label><input class="w3-input" name="candLName" value="<?php echo $editData ? htmlentities($editData['candLName']) : ''; ?>" required>
    
    <!-- Position dropdown (only shows open positions) -->
    <label>Position</label>
    <select name="posID" class="w3-select" required>
      <?php 
      // Reset result pointer to beginning
      $positions->data_seek(0);
      while($p = $positions->fetch_assoc()): 
        // Pre-select the position if editing
        $sel = ($editData && $p['posID'] == $editData['posID']) ? 'selected' : '';
      ?>
        <option value="<?php echo $p['posID']; ?>" <?php echo $sel; ?>><?php echo htmlentities($p['posName']); ?></option>
      <?php endwhile; ?>
    </select>
    
    <!-- Submit button (changes text based on edit/add mode) -->
    <button class="w3-button w3-green w3-margin-top" name="save"><?php echo $editId > 0 ? 'Update Candidate' : 'Add Candidate'; ?></button>
    <?php if($editId > 0): ?>
      <!-- Cancel button to exit edit mode -->
      <a class="w3-button w3-gray w3-margin-top" href="candidates.php">Cancel</a>
    <?php endif; ?>
  </form>

  <!-- ============================================
       SEARCH FORM
       ============================================ -->
  <form method="get" class="w3-container w3-card-2 w3-padding w3-margin-top" style="max-width:400px">
    <label>Search candidates</label>
    <input class="w3-input" name="search" value="<?php echo htmlentities($searchTerm); ?>" placeholder="Search by name or position">
    <button class="w3-button w3-blue w3-margin-top">Search</button>
    <?php if($searchTerm !== ''): ?>
      <!-- Clear search link (only shown when search is active) -->
      <a class="w3-button w3-tiny w3-margin-top" href="candidates.php">Clear</a>
    <?php endif; ?>
  </form>

  <!-- ============================================
       CANDIDATES LIST TABLE
       ============================================ -->
  <h4>All Candidates</h4>
  <table class="w3-table w3-striped w3-bordered">
    <tr><th>ID</th><th>Name</th><th>Position</th><th>Status</th><th>Actions</th></tr>
    <?php while($r = $cands->fetch_assoc()): ?>
      <tr>
        <td><?php echo $r['candID']; ?></td>
        <!-- Display full name (first + middle + last) -->
        <td><?php echo htmlentities($r['candFName'].' '.$r['candMName'].' '.$r['candLName']); ?></td>
        <td><?php echo htmlentities($r['posName']); ?></td>
        <td><?php echo $r['candStat']; ?></td>
        <td>
          <!-- Edit link (preserves search term in URL) -->
          <a class="w3-button w3-blue w3-tiny" href="?edit=<?php echo $r['candID']; ?><?php echo $searchTerm ? '&search='.urlencode($searchTerm) : ''; ?>">Edit</a>
          <?php if($r['candStat'] === 'active'): ?>
            <!-- Deactivate button (only shown for active candidates) -->
            <a class="w3-button w3-red w3-tiny" href="?deact=<?php echo $r['candID']; ?><?php echo $searchTerm ? '&search='.urlencode($searchTerm) : ''; ?>">Deactivate</a>
          <?php else: ?>
            <!-- Activate button (only shown for inactive candidates) -->
            <a class="w3-button w3-green w3-tiny" href="?activate=<?php echo $r['candID']; ?><?php echo $searchTerm ? '&search='.urlencode($searchTerm) : ''; ?>">Activate</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>

</body></html>