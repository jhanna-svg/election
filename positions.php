<?php
include 'db.php';

$msg = '';
$editId = isset($_GET['edit']) ? $_GET['edit'] : 0;
$editData = null;

// Load edit data if editing
if ($editId) {
  $data = $conn->query("SELECT * FROM positions WHERE posID=$editId");
  $editData = $data ? $data->fetch_assoc() : null;  
}

// Handle form submissions first
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (isset($_POST['add'])) {
      $posName = $_POST['posName'];
      $numOfPositions = $_POST['numOfPositions'];
      $conn->query("INSERT INTO positions (posName, numOfPositions) VALUES ('$posName', $numOfPositions)");
      $msg = "Position <b>$posName</b> added successfully.";
  } 
  elseif (isset($_POST['edit'])) {
      $id = $_POST['id'];
      $posName = $_POST['posName'];
      $numOfPositions = $_POST['numOfPositions'];
      $conn->query("UPDATE positions SET posName='$posName', numOfPositions=$numOfPositions WHERE posID=$id");
      $msg = "Position <b>$posName</b> updated successfully.";

      // CLEAR EDIT MODE
      $editId = 0;
      $editData = null;
  }
}

// Only handle GET actions if there was no POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
  if (isset($_GET['deactivate'])) {
      $id = $_GET['deactivate'];
      $conn->query("UPDATE positions SET posStat='closed' WHERE posID=$id");
      $msg = "Position ID <b>$id</b> deactivated.";
  }

  if (isset($_GET['activate'])) {
      $id = $_GET['activate'];
      $conn->query("UPDATE positions SET posStat='open' WHERE posID=$id");
      $msg = "Position ID <b>$id</b> activated.";
  }
}

// Get all positions
$result = $conn->query("SELECT * FROM positions");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Positions</title>
  <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
</head>
<body class="w3-container">
  <p><a class="w3-button" href="index.php">Back</a></p>
  <h3>Positions</h3>

  <!-- Message -->
  <?php if (!empty($msg)): ?>
    <div class='w3-panel w3-pale-green w3-border'>
      <?php echo $msg; ?>
    </div>
  <?php endif; ?>

  <!-- Add / Edit Form -->
  <form method="post" action="positions.php" class="w3-card-4 w3-padding">
    <?php if ($editId): ?>
      <input type="hidden" name="id" value="<?php echo $editId; ?>">
      <h4>Edit Position</h4>
    <?php else: ?>
      <h4>Add Position</h4>
    <?php endif; ?>

    <label>Name</label>
    <input class="w3-input" name="posName"
           value="<?php echo $editData ? htmlentities($editData['posName']) : ''; ?>" required>

    <label>Slots</label>
    <input class="w3-input" type="number" name="numOfPositions" min="1"
           value="<?php echo $editData ? $editData['numOfPositions'] : 1; ?>" required>

    <button class="w3-button w3-green w3-margin-top"
            name="<?php echo $editId ? 'edit' : 'add'; ?>">
      <?php echo $editId ? 'Update' : 'Add'; ?>
    </button>

    <?php if ($editId): ?>
      <a class="w3-button w3-gray w3-margin-top" href="positions.php">Cancel</a>
    <?php endif; ?>
  </form>

  <!-- Positions Table -->
  <h4 class="w3-margin-top">Existing Positions</h4>

  <table class="w3-table w3-striped w3-bordered">
    <tr>
      <th>ID</th>
      <th>Name</th>
      <th>Slots</th>
      <th>Status</th>
      <th>Actions</th>
    </tr>

    <?php while ($r = $result->fetch_assoc()): ?>
      <tr>
        <td><?php echo $r['posID']; ?></td>
        <td><?php echo htmlentities($r['posName']); ?></td>
        <td><?php echo $r['numOfPositions']; ?></td>
        <td><?php echo $r['posStat']; ?></td>
        <td>
          <a class="w3-button w3-blue w3-tiny" href="?edit=<?php echo $r['posID']; ?>">Edit</a>

          <?php if ($r['posStat'] == 'open'): ?>
            <a class="w3-button w3-red w3-tiny" href="?deactivate=<?php echo $r['posID']; ?>">Deactivate</a>
          <?php else: ?>
            <a class="w3-button w3-green w3-tiny" href="?activate=<?php echo $r['posID']; ?>">Activate</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>

</body>
</html>