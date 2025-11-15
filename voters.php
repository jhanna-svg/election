<?php
include 'db.php';
$msg='';
$editId = isset($_GET['edit']) ? $conn->real_escape_string($_GET['edit']) : '';
$editData = null;
if($editId !== '') {
    $editData = $conn->query("SELECT * FROM voters WHERE voterID='$editId'")->fetch_assoc();
}

// Save (add or update)
if(isset($_POST['save'])) {
    $id = $conn->real_escape_string($_POST['voterID']);
    $fname = $conn->real_escape_string($_POST['voterFName']);
    $mname = $conn->real_escape_string($_POST['voterMName']);
    $lname = $conn->real_escape_string($_POST['voterLName']);
    $currentId = isset($_POST['currentVoterID']) ? $conn->real_escape_string($_POST['currentVoterID']) : '';
    
    if($currentId !== '') {
        // Update
        if($id !== $currentId) {
            $exists = $conn->query("SELECT voterID FROM voters WHERE voterID='$id' LIMIT 1");
            if($exists && $exists->num_rows > 0) {
                $msg = "Voter ID already exists.";
            } else {
                $pass = isset($_POST['voterPass']) && trim($_POST['voterPass']) !== '' ? $conn->real_escape_string($_POST['voterPass']) : '';
                $set = "voterID='$id', voterFName='$fname', voterMName='$mname', voterLName='$lname'";
                if($pass !== '') {
                    $set .= ", voterPass='$pass'";
                }
                $conn->query("UPDATE voters SET $set WHERE voterID='$currentId'");
                $msg="Voter updated.";
                $editId = '';
                $editData = null;
            }
        } else {
            $pass = isset($_POST['voterPass']) && trim($_POST['voterPass']) !== '' ? $conn->real_escape_string($_POST['voterPass']) : '';
            $set = "voterFName='$fname', voterMName='$mname', voterLName='$lname'";
            if($pass !== '') {
                $set .= ", voterPass='$pass'";
            }
            $conn->query("UPDATE voters SET $set WHERE voterID='$currentId'");
            $msg="Voter updated.";
            $editId = '';
            $editData = null;
        }
    } else {
        // Add
        $pass = $conn->real_escape_string($_POST['voterPass']);
        $exists = $conn->query("SELECT voterID FROM voters WHERE voterID='$id' LIMIT 1");
        if($exists && $exists->num_rows > 0) {
            $msg = "Voter ID already exists.";
        } else {
            $conn->query("INSERT INTO voters (voterID,voterPass,voterFName,voterMName,voterLName) VALUES ('$id','$pass','$fname','$mname','$lname')");
            $msg="Voter registered.";
        }
    }
}

if(isset($_GET['deact'])) {
    $id = $conn->real_escape_string($_GET['deact']);
    $conn->query("UPDATE voters SET voterStat='inactive' WHERE voterID='$id'");
    $msg="Voter deactivated.";
}

if(isset($_GET['activate'])) {
    $id = $conn->real_escape_string($_GET['activate']);
    $conn->query("UPDATE voters SET voterStat='active' WHERE voterID='$id'");
    $msg="Voter activated.";
}

$searchTerm = '';
$filter = '';
if(isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searchTerm = trim($_GET['search']);
    $safeSearch = $conn->real_escape_string($searchTerm);
    $filter = "WHERE voterID LIKE '%$safeSearch%' OR voterFName LIKE '%$safeSearch%' OR voterMName LIKE '%$safeSearch%' OR voterLName LIKE '%$safeSearch%'";
}

$voters = $conn->query("SELECT * FROM voters $filter ORDER BY voterID");
?>
<!DOCTYPE html><html>
<head><title>Voters</title><link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"></head>
<body class="w3-container">
  <p><a class="w3-button" href="index.php">Back</a></p>
  <h3>Voters Management</h3>
  <?php if($msg) echo "<div class='w3-panel w3-pale-green'>$msg</div>"; ?>

  <form method="post" class="w3-container w3-card-4 w3-padding">
    <?php if($editId !== ''): ?>
      <input type="hidden" name="currentVoterID" value="<?php echo htmlentities($editId); ?>">
      <h4>Edit Voter</h4>
    <?php else: ?>
      <h4>Add New Voter</h4>
    <?php endif; ?>
    <label>Voter ID (username)</label><input class="w3-input" name="voterID" value="<?php echo $editData ? htmlentities($editData['voterID']) : ''; ?>" required>
    <label>Password <?php echo $editId !== '' ? '(leave blank to keep current)' : ''; ?></label><input class="w3-input" name="voterPass" type="password" <?php echo $editId === '' ? 'required' : ''; ?>>
    <label>First name</label><input class="w3-input" name="voterFName" value="<?php echo $editData ? htmlentities($editData['voterFName']) : ''; ?>" required>
    <label>Middle name</label><input class="w3-input" name="voterMName" value="<?php echo $editData ? htmlentities($editData['voterMName']) : ''; ?>">
    <label>Last name</label><input class="w3-input" name="voterLName" value="<?php echo $editData ? htmlentities($editData['voterLName']) : ''; ?>" required>
    <button class="w3-button w3-green w3-margin-top" name="save"><?php echo $editId !== '' ? 'Update Voter' : 'Register Voter'; ?></button>
    <?php if($editId !== ''): ?>
      <a class="w3-button w3-gray w3-margin-top" href="voters.php<?php echo $searchTerm ? '?search='.urlencode($searchTerm) : ''; ?>">Cancel</a>
    <?php endif; ?>
  </form>

  <form method="get" class="w3-container w3-card-2 w3-padding w3-margin-top" style="max-width:400px">
    <label>Search voters</label>
    <input class="w3-input" name="search" value="<?php echo htmlentities($searchTerm); ?>" placeholder="Search by ID or name">
    <button class="w3-button w3-blue w3-margin-top">Search</button>
    <?php if($searchTerm !== ''): ?>
      <a class="w3-button w3-tiny w3-margin-top" href="voters.php">Clear</a>
    <?php endif; ?>
  </form>

  <h4>All Voters</h4>
  <table class="w3-table w3-striped w3-bordered">
    <tr><th>ID</th><th>Name</th><th>Status</th><th>Voted?</th><th>Actions</th></tr>
    <?php while($r = $voters->fetch_assoc()): ?>
      <tr>
        <td><?php echo $r['voterID']; ?></td>
        <td><?php echo htmlentities($r['voterFName'].' '.$r['voterMName'].' '.$r['voterLName']); ?></td>
        <td><?php echo $r['voterStat']; ?></td>
        <td><?php echo $r['voted']; ?></td>
        <td>
          <a class="w3-button w3-blue w3-tiny" href="?edit=<?php echo urlencode($r['voterID']); ?><?php echo $searchTerm ? '&search='.urlencode($searchTerm) : ''; ?>">Edit</a>
          <?php if($r['voterStat'] === 'active'): ?>
            <a class="w3-button w3-red w3-tiny" href="?deact=<?php echo urlencode($r['voterID']); ?><?php echo $searchTerm ? '&search='.urlencode($searchTerm) : ''; ?>">Deactivate</a>
          <?php else: ?>
            <a class="w3-button w3-green w3-tiny" href="?activate=<?php echo urlencode($r['voterID']); ?><?php echo $searchTerm ? '&search='.urlencode($searchTerm) : ''; ?>">Activate</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>

</body></html>
