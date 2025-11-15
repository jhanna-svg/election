<?php
include 'db.php';
$msg='';
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editData = null;
if($editId > 0) {
    $editData = $conn->query("SELECT * FROM candidates WHERE candID=$editId")->fetch_assoc();
}

$positions = $conn->query("SELECT * FROM positions WHERE posStat='open'");

// Save (add or update)
if(isset($_POST['save'])) {
    $fname = $conn->real_escape_string($_POST['candFName']);
    $mname = $conn->real_escape_string($_POST['candMName']);
    $lname = $conn->real_escape_string($_POST['candLName']);
    $pos = (int)$_POST['posID'];
    $id = isset($_POST['candID']) ? (int)$_POST['candID'] : 0;
    
    if($id > 0) {
        // Update
        $exists = $conn->query("SELECT candID FROM candidates WHERE candFName='$fname' AND candMName='$mname' AND candLName='$lname' AND posID=$pos AND candID!=$id LIMIT 1");
        if($exists && $exists->num_rows > 0) {
            $msg = "Another candidate with the same name already exists for this position.";
        } else {
            $conn->query("UPDATE candidates SET candFName='$fname', candMName='$mname', candLName='$lname', posID=$pos WHERE candID=$id");
            $msg = "Candidate updated.";
            $editId = 0;
            $editData = null;
        }
    } else {
        // Add
        $exists = $conn->query("SELECT candID FROM candidates WHERE candFName='$fname' AND candMName='$mname' AND candLName='$lname' AND posID=$pos LIMIT 1");
        if($exists && $exists->num_rows > 0) {
            $msg = "Candidate already exists for this position.";
        } else {
            $conn->query("INSERT INTO candidates (candFName,candMName,candLName,posID) VALUES ('$fname','$mname','$lname',$pos)");
            $msg = "Candidate added.";
        }
    }
}

// Deactivate
if(isset($_GET['deact'])) {
    $id = (int)$_GET['deact'];
    $conn->query("UPDATE candidates SET candStat='inactive' WHERE candID=$id");
    $msg = "Candidate deactivated.";
}

// Activate
if(isset($_GET['activate'])) {
    $id = (int)$_GET['activate'];
    $conn->query("UPDATE candidates SET candStat='active' WHERE candID=$id");
    $msg = "Candidate activated.";
}

$searchTerm = '';
$filter = '';
if(isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searchTerm = trim($_GET['search']);
    $safe = $conn->real_escape_string($searchTerm);
    $filter = "WHERE c.candFName LIKE '%$safe%' OR c.candMName LIKE '%$safe%' OR c.candLName LIKE '%$safe%' OR p.posName LIKE '%$safe%'";
}

$cands = $conn->query("SELECT c.*, p.posName FROM candidates c LEFT JOIN positions p ON c.posID=p.posID $filter ORDER BY c.candID");
?>
<!DOCTYPE html><html>
<head><title>Candidates</title><link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"></head>
<body class="w3-container">
  <p><a class="w3-button" href="index.php">Back</a></p>
  <h3>Candidates Management</h3>
  <?php if($msg) echo "<div class='w3-panel w3-pale-green'>$msg</div>"; ?>

  <form method="post" class="w3-container w3-card-4 w3-padding">
    <?php if($editId > 0): ?>
      <input type="hidden" name="candID" value="<?php echo $editId; ?>">
      <h4>Edit Candidate</h4>
    <?php else: ?>
      <h4>Add New Candidate</h4>
    <?php endif; ?>
    <label>First name</label><input class="w3-input" name="candFName" value="<?php echo $editData ? htmlentities($editData['candFName']) : ''; ?>" required>
    <label>Middle name</label><input class="w3-input" name="candMName" value="<?php echo $editData ? htmlentities($editData['candMName']) : ''; ?>">
    <label>Last name</label><input class="w3-input" name="candLName" value="<?php echo $editData ? htmlentities($editData['candLName']) : ''; ?>" required>
    <label>Position</label>
    <select name="posID" class="w3-select" required>
      <?php 
      $positions->data_seek(0);
      while($p = $positions->fetch_assoc()): 
        $sel = ($editData && $p['posID'] == $editData['posID']) ? 'selected' : '';
      ?>
        <option value="<?php echo $p['posID']; ?>" <?php echo $sel; ?>><?php echo htmlentities($p['posName']); ?></option>
      <?php endwhile; ?>
    </select>
    <button class="w3-button w3-green w3-margin-top" name="save"><?php echo $editId > 0 ? 'Update Candidate' : 'Add Candidate'; ?></button>
    <?php if($editId > 0): ?>
      <a class="w3-button w3-gray w3-margin-top" href="candidates.php">Cancel</a>
    <?php endif; ?>
  </form>

  <form method="get" class="w3-container w3-card-2 w3-padding w3-margin-top" style="max-width:400px">
    <label>Search candidates</label>
    <input class="w3-input" name="search" value="<?php echo htmlentities($searchTerm); ?>" placeholder="Search by name or position">
    <button class="w3-button w3-blue w3-margin-top">Search</button>
    <?php if($searchTerm !== ''): ?>
      <a class="w3-button w3-tiny w3-margin-top" href="candidates.php">Clear</a>
    <?php endif; ?>
  </form>

  <h4>All Candidates</h4>
  <table class="w3-table w3-striped w3-bordered">
    <tr><th>ID</th><th>Name</th><th>Position</th><th>Status</th><th>Actions</th></tr>
    <?php while($r = $cands->fetch_assoc()): ?>
      <tr>
        <td><?php echo $r['candID']; ?></td>
        <td><?php echo htmlentities($r['candFName'].' '.$r['candMName'].' '.$r['candLName']); ?></td>
        <td><?php echo htmlentities($r['posName']); ?></td>
        <td><?php echo $r['candStat']; ?></td>
        <td>
          <a class="w3-button w3-blue w3-tiny" href="?edit=<?php echo $r['candID']; ?><?php echo $searchTerm ? '&search='.urlencode($searchTerm) : ''; ?>">Edit</a>
          <?php if($r['candStat'] === 'active'): ?>
            <a class="w3-button w3-red w3-tiny" href="?deact=<?php echo $r['candID']; ?><?php echo $searchTerm ? '&search='.urlencode($searchTerm) : ''; ?>">Deactivate</a>
          <?php else: ?>
            <a class="w3-button w3-green w3-tiny" href="?activate=<?php echo $r['candID']; ?><?php echo $searchTerm ? '&search='.urlencode($searchTerm) : ''; ?>">Activate</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>

</body></html>