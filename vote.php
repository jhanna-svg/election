<?php
include 'db.php';
session_start();

if (!isset($_SESSION['voterID'])) {
    header("Location: login.php");
    exit;
}

$voter = $_SESSION['voterID'];
$msg = '';

// Get all open positions
$positions = $conn->query("SELECT * FROM positions WHERE posStat='open' ORDER BY posID");

// Handle vote submission
if (isset($_POST['submit_votes'])) {
    $voterData = $conn->query("SELECT voted, voterStat FROM voters WHERE BINARY voterID='$voter'")->fetch_assoc();

    if (!$voterData || $voterData['voterStat'] !== 'active') {
        $msg = "Voter not active.";
    } elseif ($voterData['voted'] === 'Y') {
        $msg = "You already voted.";
    } else {
        foreach ($_POST['votes'] as $posID => $candArray) {
            $posID = (int)$posID;
            $limit = (int)$conn->query("SELECT numOfPositions FROM positions WHERE posID=$posID")->fetch_assoc()['numOfPositions'];
            if (is_array($candArray) && count($candArray) <= $limit) {
                foreach ($candArray as $candID) {
                    $candID = (int)$candID;
                    $conn->query("INSERT INTO votes (posID, voterID, candID) VALUES ($posID, '$voter', $candID)");
                }
            }
        }
        $conn->query("UPDATE voters SET voted='Y' WHERE BINARY voterID='$voter'");
        $msg = "Thank you. Your votes were recorded.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Voting</title>
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
</head>
<body class="w3-container">

<h3>Voting Page</h3>
<p>Logged in as: <?php echo htmlentities($voter); ?> <a href="logout.php">(logout)</a></p>

<?php if ($msg): ?>
<div class="w3-panel w3-pale-green"><?php echo $msg; ?></div>
<?php endif; ?>

<form method="post">
<?php while ($pos = $positions->fetch_assoc()):
    $posID = $pos['posID'];
    $limit = (int)$pos['numOfPositions'];
    $candidates = $conn->query("SELECT * FROM candidates WHERE posID=$posID AND candStat='active'");
?>
    <h4><?php echo htmlentities($pos['posName']); ?> (choose up to <?php echo $limit; ?>)</h4>
    <?php while ($c = $candidates->fetch_assoc()): ?>
        <label>
            <?php if ($limit > 1): ?>
                <input type="checkbox" name="votes[<?php echo $posID; ?>][]" value="<?php echo $c['candID']; ?>">
            <?php else: ?>
                <input type="radio" name="votes[<?php echo $posID; ?>][]" value="<?php echo $c['candID']; ?>">
            <?php endif; ?>
            <?php echo htmlentities($c['candFName'].' '.$c['candMName'].' '.$c['candLName']); ?>
        </label><br>
    <?php endwhile; ?>
<?php endwhile; ?>

<button class="w3-button w3-blue" name="submit_votes">Submit Votes</button>
</form>

<p><a class="w3-button" href="index.php">Back to menu</a></p>

</body>
</html>