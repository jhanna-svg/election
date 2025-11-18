<?php
include 'db.php';

$msg = '';
$editId = isset($_GET['edit']) ? $_GET['edit'] : 0;
$editData = null;

// Load edit data if editing
if ($editId) {
    $data = $conn->query("SELECT * FROM voters WHERE voterID='$editId'");
    $editData = $data ? $data->fetch_assoc() : null;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $id = $_POST['voterID'];
  $fname = $_POST['voterFName'];
  $mname = $_POST['voterMName'];
  $lname = $_POST['voterLName'];
  $pass = isset($_POST['voterPass']) ? $_POST['voterPass'] : '';

  if (isset($_POST['add'])) {
      // Add new voter with password
      $conn->query("INSERT INTO voters (voterID, voterPass, voterFName, voterMName, voterLName, voterStat, voted) 
                    VALUES ('$id','$pass','$fname','$mname','$lname','active','N')");
      $msg = "Voter <b>$fname $lname</b> added successfully.";
  }
  elseif (isset($_POST['edit'])) {
      // Update voter info
      $set = "voterID='$id', voterFName='$fname', voterMName='$mname', voterLName='$lname'";
      if (!empty($pass)) {
          $set .= ", voterPass='$pass'"; // update password only if provided
      }
      $conn->query("UPDATE voters SET $set WHERE voterID='$editId'");
      $msg = "Voter <b>$fname $lname</b> updated successfully.";
      $editId = 0;
      $editData = null;
  }
}

// Handle activate/deactivate
if ($_SERVER["REQUEST_METHOD"] != "POST") {
  if (isset($_GET['deactivate'])) {
      $id = $_GET['deactivate'];
      $conn->query("UPDATE voters SET voterStat='inactive' WHERE voterID='$id'");
      $msg = "Voter ID <b>$id</b> deactivated.";
  }
  if (isset($_GET['activate'])) {
      $id = $_GET['activate'];
      $conn->query("UPDATE voters SET voterStat='active' WHERE voterID='$id'");
      $msg = "Voter ID <b>$id</b> activated.";
  }
}

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchQuery = '';
if ($search) {
    $searchQuery = "WHERE voterID LIKE '%$search%' OR voterFName LIKE '%$search%' OR voterMName LIKE '%$search%' OR voterLName LIKE '%$search%'";
}

// Fetch voters
$result = $conn->query("SELECT * FROM voters $searchQuery");
?>

<!DOCTYPE html>
<html>
<head>
<title>Voters</title>
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
</head>
<body class="w3-container">

<p><a class="w3-button" href="index.php">Back</a></p>
<h3>Voters</h3>

<?php if ($msg): ?>
<div class="w3-panel w3-pale-green w3-border"><?php echo $msg; ?></div>
<?php endif; ?>

<form method="post" class="w3-card-4 w3-padding">

<?php if ($editId): ?>
    <input type="hidden" name="voterID" value="<?php echo $editId; ?>">
    <h4>Edit Voter</h4>
<?php else: ?>
    <h4>Add Voter</h4>
<?php endif; ?>

<label>Voter ID</label>
<input class="w3-input" name="voterID" 
       value="<?php echo $editData ? htmlentities($editData['voterID']) : ''; ?>" required>

<label>Password <?php echo $editId ? '(leave blank to keep current)' : ''; ?></label>
<input class="w3-input" type="password" name="voterPass" <?php echo $editId ? '' : 'required'; ?>>

<label>First Name</label>
<input class="w3-input" name="voterFName" 
       value="<?php echo $editData ? htmlentities($editData['voterFName']) : ''; ?>" required>

<label>Middle Name</label>
<input class="w3-input" name="voterMName" 
       value="<?php echo $editData ? htmlentities($editData['voterMName']) : ''; ?>">

<label>Last Name</label>
<input class="w3-input" name="voterLName" 
       value="<?php echo $editData ? htmlentities($editData['voterLName']) : ''; ?>" required>

<button class="w3-button w3-green w3-margin-top" 
        name="<?php echo $editId ? 'edit' : 'add'; ?>">
    <?php echo $editId ? 'Update' : 'Add'; ?>
</button>

<?php if ($editId): ?>
    <a class="w3-button w3-gray w3-margin-top" href="voters.php">Cancel</a>
<?php endif; ?>

</form>

<form method="get" class="w3-margin-bottom w3-padding w3-light-grey">
    <input class="w3-input w3-border" 
           type="text" 
           name="search" 
           placeholder="Search candidate or position..." 
           value="<?php echo htmlentities($search); ?>">

    <button class="w3-button w3-blue w3-margin-top">Search</button>

    <?php if (!empty($search)): ?>
        <a href="voters.php" class="w3-button w3-gray w3-margin-top">Clear</a>
    <?php endif; ?>
</form>

<h4>Existing Voters</h4>
<table class="w3-table w3-striped w3-bordered">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Status</th>
    <th>Voted?</th>
    <th>Actions</th>
</tr>

<?php while ($r = $result->fetch_assoc()): ?>
<tr>
    <td><?php echo $r['voterID']; ?></td>
    <td><?php echo htmlentities($r['voterFName'].' '.$r['voterMName'].' '.$r['voterLName']); ?></td>
    <td><?php echo $r['voterStat']; ?></td>
    <td><?php echo $r['voted']; ?></td>
    <td>
        <a class="w3-button w3-blue w3-tiny" href="?edit=<?php echo $r['voterID']; ?>">Edit</a>
        <?php if ($r['voterStat'] === 'active'): ?>
            <a class="w3-button w3-red w3-tiny" href="?deactivate=<?php echo $r['voterID']; ?>">Deactivate</a>
        <?php else: ?>
            <a class="w3-button w3-green w3-tiny" href="?activate=<?php echo $r['voterID']; ?>">Activate</a>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
</table>

</body>
</html>