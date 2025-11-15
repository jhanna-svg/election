<?php
include 'db.php';
session_start();
if(!isset($_SESSION['voterID'])) {
    header("Location: login.php");
    exit;
}
$voter = $_SESSION['voterID'];
$msg='';

// fetch positions
$posRes = $conn->query("SELECT * FROM positions WHERE posStat='open' ORDER BY posID");

if(isset($_POST['submit_votes'])) {
    // Input format: votes[posID] = array of selected candIDs
    // Check voter is active and not already voted
    $q = $conn->query("SELECT voted, voterStat FROM voters WHERE voterID='$voter'")->fetch_assoc();
    if(!$q || $q['voterStat']!='active') { $msg = "Voter not active."; }
    else if($q['voted']=='Y') { $msg = "You already voted."; }
    else {
        // For each position, validate and insert votes
        $ok = true;
        foreach($_POST['votes'] as $posID => $candArray) {
            $posID = (int)$posID;
            // get limit
            $r = $conn->query("SELECT numOfPositions FROM positions WHERE posID=$posID")->fetch_assoc();
            $limit = (int)$r['numOfPositions'];
            // ensure count <= limit
            $count = is_array($candArray) ? count($candArray) : 0;
            if($count > $limit) { $ok = false; $msg = "You selected too many candidates for a position."; break; }
            // insert each chosen candidate
            if(is_array($candArray)){
                foreach($candArray as $candID){
                    $candID = (int)$candID;
                    $conn->query("INSERT INTO votes (posID, voterID, candID) VALUES ($posID, '".$conn->real_escape_string($voter)."', $candID)");
                }
            }
        }
        if($ok){
            // mark voter as voted
            $conn->query("UPDATE voters SET voted='Y' WHERE voterID='".$conn->real_escape_string($voter)."'");
            $msg = "Thank you. Your votes were recorded.";
        }
    }
}
?>
<!DOCTYPE html><html>
<head><title>Voting</title><link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"></head>
<body class="w3-container">
  <h3>Voting Page</h3>
  <p>Logged in as: <?php echo htmlentities($voter); ?> <a href="logout.php">(logout)</a></p>
  <?php if($msg) echo "<div class='w3-panel w3-pale-green'>$msg</div>"; ?>

  <form method="post">
    <?php while($pos = $posRes->fetch_assoc()): 
       $posID = $pos['posID'];
       $limit = (int)$pos['numOfPositions'];
       $cands = $conn->query("SELECT * FROM candidates WHERE posID=$posID AND candStat='active'");
    ?>
      <div class="w3-card-2 w3-padding w3-margin-bottom">
        <h4><?php echo htmlentities($pos['posName']); ?> (choose up to <?php echo $limit; ?>)</h4>
        <?php if($cands->num_rows==0) echo "<p>No candidates.</p>"; ?>
        <?php while($c = $cands->fetch_assoc()): ?>
           <label class="w3-block">
            <?php if($limit>1): ?>
              <input type="checkbox" name="votes[<?php echo $posID; ?>][]" value="<?php echo $c['candID']; ?>">
            <?php else: ?>
              <input type="radio" name="votes[<?php echo $posID; ?>][]" value="<?php echo $c['candID']; ?>">
            <?php endif; ?>
            <?php echo htmlentities($c['candFName'].' '.$c['candMName'].' '.$c['candLName']); ?>
          </label>
        <?php endwhile; ?>
      </div>
    <?php endwhile; ?>
    <button class="w3-button w3-blue" name="submit_votes">Submit Votes</button>
  </form>

  <p><a class="w3-button" href="index.php">Back to menu</a></p>
</body></html>