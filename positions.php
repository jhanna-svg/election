<?php
include 'db.php';
$msg = '';
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editData = null;
if($editId > 0) {
    $editData = $conn->query("SELECT * FROM positions WHERE posID=$editId")->fetch_assoc();
}

// Save (add or update)
if(isset($_POST['save'])) {
    $name = $conn->real_escape_string($_POST['posName']);
    $num = (int)$_POST['numOfPositions'];
    $id = isset($_POST['posID']) ? (int)$_POST['posID'] : 0;
    
    if($id > 0) {
        // Update
        $exists = $conn->query("SELECT posID FROM positions WHERE posName='$name' AND posID!=$id LIMIT 1");
        if($exists && $exists->num_rows > 0) {
            $msg = "Another position already uses that name.";
        } else {
            $conn->query("UPDATE positions SET posName='$name', numOfPositions=$num WHERE posID=$id");
            $msg = "Position updated.";
            $editId = 0;
            $editData = null;
        }
    } else {
        // Add
        $exists = $conn->query("SELECT posID FROM positions WHERE posName='$name' LIMIT 1");
        if($exists && $exists->num_rows > 0) {
            $msg = "Position already exists.";
        } else {
            $conn->query("INSERT INTO positions (posName,numOfPositions) VALUES ('$name',$num)");
            $msg = "Position added.";
        }
    }
}

if(isset($_GET['deact'])) {
    $id = (int)$_GET['deact'];
    $conn->query("UPDATE positions SET posStat='closed' WHERE posID=$id");
    $msg = "Position deactivated.";
}

if(isset($_GET['activate'])) {
    $id = (int)$_GET['activate'];
    $conn->query("UPDATE positions SET posStat='open' WHERE posID=$id");
    $msg = "Position activated.";
}

$positions = $conn->query("SELECT * FROM positions");

?>
<!DOCTYPE html>
<html>
<head><title>Positions</title><link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"></head>
<body class="w3-container">
  <p><a class="w3-button" href="index.php">Back</a></p>
  <h3>Positions Management</h3>
  <?php if($msg) echo "<div class='w3-panel w3-pale-green'>$msg</div>"; ?>

  <form method="post" class="w3-container w3-card-4 w3-padding">
    <?php if($editId > 0): ?>
      <input type="hidden" name="posID" value="<?php echo $editId; ?>">
      <h4>Edit Position</h4>
    <?php else: ?>
      <h4>Add New Position</h4>
    <?php endif; ?>
    <label>Position Name</label>
    <input class="w3-input" name="posName" value="<?php echo $editData ? htmlentities($editData['posName']) : ''; ?>" required>
    <label>Number of positions (e.g., 1 for President, up to 12 for senators)</label>
    <input class="w3-input" name="numOfPositions" type="number" value="<?php echo $editData ? $editData['numOfPositions'] : '1'; ?>" required>
    <button class="w3-button w3-green w3-margin-top" name="save"><?php echo $editId > 0 ? 'Update Position' : 'Add Position'; ?></button>
    <?php if($editId > 0): ?>
      <a class="w3-button w3-gray w3-margin-top" href="positions.php">Cancel</a>
    <?php endif; ?>
  </form>

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
          <a class="w3-button w3-blue w3-tiny" href="?edit=<?php echo $r['posID']; ?>">Edit</a>
          <?php if($r['posStat'] == 'open'): ?>
                <a class="w3-button w3-red w3-tiny" href="?deact=<?php echo $r['posID']; ?>">Deactivate</a>
            <?php else: ?>
                <a class="w3-button w3-green w3-tiny" href="?activate=<?php echo $r['posID']; ?>">Activate</a>
            <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>

</body>
</html>
