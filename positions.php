<?php
/**
 * POSITIONS MANAGEMENT PAGE
 * 
 * This page handles the management of election positions:
 * - Add new positions (e.g., President, Vice President, Senators)
 * - Edit existing positions
 * - Set number of available slots per position (e.g., 1 for President, 12 for Senators)
 * - Activate/deactivate positions (closed positions won't appear in voting)
 */

include 'db.php';
$msg = ''; // Message variable to display success/error messages

// Check if we're editing a position (edit ID passed via GET parameter)
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editData = null;
if($editId > 0) {
    // Fetch position data for editing
    $editData = $conn->query("SELECT * FROM positions WHERE posID=$editId")->fetch_assoc();
}

// ============================================
// HANDLE FORM SUBMISSION (Add or Update)
// ============================================
if(isset($_POST['save'])) {
    // Sanitize input data to prevent SQL injection
    $name = $conn->real_escape_string($_POST['posName']);
    $num = (int)$_POST['numOfPositions']; // Number of available slots (e.g., 1 for President, 12 for Senators)
    $id = isset($_POST['posID']) ? (int)$_POST['posID'] : 0; // Position ID (0 = new, >0 = update)
    
    if($id > 0) {
        // ===== UPDATE EXISTING POSITION =====
        // Check if another position with the same name exists (excluding current position)
        $exists = $conn->query("SELECT posID FROM positions WHERE posName='$name' AND posID!=$id LIMIT 1");
        if($exists && $exists->num_rows > 0) {
            $msg = "Another position already uses that name.";
        } else {
            // Update position information
            $conn->query("UPDATE positions SET posName='$name', numOfPositions=$num WHERE posID=$id");
            $msg = "Position updated.";
            // Reset edit mode
            $editId = 0;
            $editData = null;
        }
    } else {
        // ===== ADD NEW POSITION =====
        // Check if position with same name already exists
        $exists = $conn->query("SELECT posID FROM positions WHERE posName='$name' LIMIT 1");
        if($exists && $exists->num_rows > 0) {
            $msg = "Position already exists.";
        } else {
            // Insert new position (default status is 'open')
            $conn->query("INSERT INTO positions (posName,numOfPositions) VALUES ('$name',$num)");
            $msg = "Position added.";
        }
    }
}

// ============================================
// HANDLE DEACTIVATE ACTION
// ============================================
if(isset($_GET['deact'])) {
    $id = (int)$_GET['deact'];
    // Set position status to 'closed' (won't appear in voting page)
    $conn->query("UPDATE positions SET posStat='closed' WHERE posID=$id");
    $msg = "Position deactivated.";
}

// ============================================
// HANDLE ACTIVATE ACTION
// ============================================
if(isset($_GET['activate'])) {
    $id = (int)$_GET['activate'];
    // Set position status to 'open' (will appear in voting page)
    $conn->query("UPDATE positions SET posStat='open' WHERE posID=$id");
    $msg = "Position activated.";
}

// Fetch all positions for display in the table
$positions = $conn->query("SELECT * FROM positions");

?>
<!DOCTYPE html>
<html>
<head><title>Positions</title><link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"></head>
<body class="w3-container">
  <p><a class="w3-button" href="index.php">Back</a></p>
  <h3>Positions Management</h3>
  
  <!-- Display success/error messages -->
  <?php if($msg) echo "<div class='w3-panel w3-pale-green'>$msg</div>"; ?>

  <!-- ============================================
       ADD/EDIT POSITION FORM
       ============================================ -->
  <form method="post" class="w3-container w3-card-4 w3-padding">
    <?php if($editId > 0): ?>
      <!-- Hidden field to identify this as an update operation -->
      <input type="hidden" name="posID" value="<?php echo $editId; ?>">
      <h4>Edit Position</h4>
    <?php else: ?>
      <h4>Add New Position</h4>
    <?php endif; ?>
    
    <!-- Position name input -->
    <label>Position Name</label>
    <input class="w3-input" name="posName" value="<?php echo $editData ? htmlentities($editData['posName']) : ''; ?>" required>
    
    <!-- Number of available slots (e.g., 1 for President, 12 for Senators) -->
    <label>Number of positions (e.g., 1 for President, up to 12 for senators)</label>
    <input class="w3-input" name="numOfPositions" type="number" value="<?php echo $editData ? $editData['numOfPositions'] : '1'; ?>" required>
    
    <!-- Submit button (changes text based on edit/add mode) -->
    <button class="w3-button w3-green w3-margin-top" name="save"><?php echo $editId > 0 ? 'Update Position' : 'Add Position'; ?></button>
    <?php if($editId > 0): ?>
      <!-- Cancel button to exit edit mode -->
      <a class="w3-button w3-gray w3-margin-top" href="positions.php">Cancel</a>
    <?php endif; ?>
  </form>

  <!-- ============================================
       POSITIONS LIST TABLE
       ============================================ -->
  <h4>Existing Positions</h4>
  <table class="w3-table w3-striped w3-bordered">
    <tr><th>ID</th><th>Name</th><th>Slots</th><th>Status</th><th>Actions</th></tr>
    <?php while($r = $positions->fetch_assoc()): ?>
      <tr>
        <td><?php echo $r['posID']; ?></td>
        <td><?php echo htmlentities($r['posName']); ?></td>
        <td><?php echo $r['numOfPositions']; ?></td>
        <td><?php echo $r['posStat']; ?></td>
        <td>
          <!-- Edit link -->
          <a class="w3-button w3-blue w3-tiny" href="?edit=<?php echo $r['posID']; ?>">Edit</a>
          <?php if($r['posStat'] == 'open'): ?>
            <!-- Deactivate button (only shown for open positions) -->
            <a class="w3-button w3-red w3-tiny" href="?deact=<?php echo $r['posID']; ?>">Deactivate</a>
          <?php else: ?>
            <!-- Activate button (only shown for closed positions) -->
            <a class="w3-button w3-green w3-tiny" href="?activate=<?php echo $r['posID']; ?>">Activate</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>

</body>
</html>
