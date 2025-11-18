<?php
include 'db.php';

// Get all positions
$positions = $conn->query("SELECT * FROM positions ORDER BY posID");
?>
<!DOCTYPE html>
<html>
<head>
<title>Election Results</title>
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
</head>
<body class="w3-container">

<h3>Election Results</h3>

<?php while($pos = $positions->fetch_assoc()): 
    $posID = $pos['posID'];
    // Total votes for this position
    $totalRow = $conn->query("SELECT COUNT(*) as total FROM votes WHERE posID=$posID")->fetch_assoc();
    $total = (int)$totalRow['total'];
    // Candidates with vote counts
    $candidates = $conn->query("
        SELECT c.*, 
        (SELECT COUNT(*) FROM votes v WHERE v.candID=c.candID AND v.posID=$posID) as votes
        FROM candidates c 
        WHERE c.posID=$posID
    ");
?>
<h4><?php echo htmlentities($pos['posName']); ?></h4>
<table class="w3-table w3-striped w3-bordered">
<tr>
    <th>Candidate</th>
    <th>Total Votes</th>
    <th>Voting %</th>
</tr>
<?php while($c = $candidates->fetch_assoc()):
    $v = (int)$c['votes'];
    $pct = $total>0 ? round(($v/$total)*100,2) : '';
?>
<tr>
    <td><?php echo htmlentities($c['candFName'].' '.$c['candMName'].' '.$c['candLName']); ?></td>
    <td><?php echo $v; ?></td>
    <td><?php echo $pct !== '' ? $pct.' %' : ''; ?></td>
</tr>
<?php endwhile; ?>
</table>
<?php endwhile; ?>

<p><a class="w3-button" href="index.php">Back</a></p>

</body>
</html>