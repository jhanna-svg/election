<?php
include 'db.php';

$msg = '';
$editId = isset($_GET['edit']) ? $_GET['edit'] : 0;
$editData = null;

// ===== SEARCH FILTER =====
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Load edit data if editing
if ($editId) {
    $data = $conn->query("SELECT * FROM candidates WHERE candID=$editId");
    $editData = $data ? $data->fetch_assoc() : null;
}

// Get positions
$positions = $conn->query("SELECT * FROM positions WHERE posStat='open'");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_POST['add'])) {

        $fname = $_POST['candFName'];
        $mname = $_POST['candMName'];
        $lname = $_POST['candLName'];
        $posID = $_POST['posID'];
        $conn->query("
            INSERT INTO candidates (candFName, candMName, candLName, posID) 
            VALUES ('$fname', '$mname', '$lname', $posID)
        ");
        $msg = "Candidate <b>$fname $lname</b> added successfully.";
    }
    elseif (isset($_POST['edit'])) {
        $id = $_POST['id'];
        $fname = $_POST['candFName'];
        $mname = $_POST['candMName'];
        $lname = $_POST['candLName'];
        $posID = $_POST['posID'];
        $conn->query("
            UPDATE candidates 
            SET candFName='$fname', candMName='$mname', candLName='$lname', posID=$posID 
            WHERE candID=$id
        ");
        $msg = "Candidate <b>$fname $lname</b> updated successfully.";

        $editId = 0;
        $editData = null;
    }
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
  if (isset($_GET['deactivate'])) {
      $id = (int)$_GET['deactivate'];
      $conn->query("UPDATE candidates SET candStat='inactive' WHERE candID=$id");
      $msg = "Candidate ID <b>$id</b> deactivated.";
  }

  if (isset($_GET['activate'])) {
      $id = (int)$_GET['activate'];
      $conn->query("UPDATE candidates SET candStat='active' WHERE candID=$id");
      $msg = "Candidate ID <b>$id</b> activated.";
  }
}

$searchQuery = "";
if (!empty($search)) {
    $searchQuery = "
        WHERE 
            c.candFName LIKE '%$search%' OR
            c.candMName LIKE '%$search%' OR
            c.candLName LIKE '%$search%' OR
            p.posName LIKE '%$search%'
    ";
}

$result = $conn->query("
    SELECT c.*, p.posName 
    FROM candidates c
    LEFT JOIN positions p ON c.posID=p.posID
    $searchQuery
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Candidates</title>
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
</head>

<body class="w3-container">

<p><a class="w3-button" href="index.php">Back</a></p>
<h3>Candidates</h3>

<!-- MESSAGE -->
<?php if (!empty($msg)): ?>
<div class="w3-panel w3-pale-green w3-border"><?php echo $msg; ?></div>
<?php endif; ?>

<form method="post" action="candidates.php" class="w3-card-4 w3-padding">

<?php if ($editId): ?>
    <input type="hidden" name="id" value="<?php echo $editId; ?>">
    <h4>Edit Candidate</h4>
<?php else: ?>
    <h4>Add Candidate</h4>
<?php endif; ?>

<label>First Name</label>
<input class="w3-input" name="candFName" 
       value="<?php echo $editData ? htmlentities($editData['candFName']) : ''; ?>" required>

<label>Middle Name</label>
<input class="w3-input" name="candMName" 
       value="<?php echo $editData ? htmlentities($editData['candMName']) : ''; ?>">

<label>Last Name</label>
<input class="w3-input" name="candLName" 
       value="<?php echo $editData ? htmlentities($editData['candLName']) : ''; ?>" required>

<label>Position</label>
<select class="w3-select" name="posID" required>
    <?php
    // Reload open positions
    $positions = $conn->query("SELECT * FROM positions WHERE posStat='open'");
    while ($p = $positions->fetch_assoc()):
        $sel = ($editData && $p['posID'] == $editData['posID']) ? "selected" : "";
    ?>
        <option value="<?php echo $p['posID']; ?>" <?php echo $sel; ?>>
            <?php echo htmlentities($p['posName']); ?>
        </option>
    <?php endwhile; ?>
</select>

<button class="w3-button w3-green w3-margin-top" 
        name="<?php echo $editId ? 'edit' : 'add'; ?>">
    <?php echo $editId ? 'Update' : 'Add'; ?>
</button>

<?php if ($editId): ?>
    <a class="w3-button w3-gray w3-margin-top" href="candidates.php">Cancel</a>
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
        <a href="candidates.php" class="w3-button w3-gray w3-margin-top">Clear</a>
    <?php endif; ?>
</form>

<h4 class="w3-margin-top">Existing Candidates</h4>

<table class="w3-table w3-striped w3-bordered">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Position</th>
    <th>Status</th>
    <th>Actions</th>
</tr>

<?php while ($r = $result->fetch_assoc()): ?>
<tr>
    <td><?php echo $r['candID']; ?></td>
    <td><?php echo htmlentities($r['candFName'].' '.$r['candMName'].' '.$r['candLName']); ?></td>
    <td><?php echo htmlentities($r['posName']); ?></td>
    <td><?php echo $r['candStat']; ?></td>

    <td>
        <a class="w3-button w3-blue w3-tiny" href="?edit=<?php echo $r['candID']; ?>">Edit</a>

        <?php if ($r['candStat'] === 'active'): ?>
            <a class="w3-button w3-red w3-tiny" href="?deactivate=<?php echo $r['candID']; ?>">Deactivate</a>
        <?php else: ?>
            <a class="w3-button w3-green w3-tiny" href="?activate=<?php echo $r['candID']; ?>">Activate</a>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>