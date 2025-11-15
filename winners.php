<?php
include 'db.php';
$positions = $conn->query("SELECT * FROM positions ORDER BY posID");
?>
<!DOCTYPE html><html>
<head><title>Winners</title><link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"></head>
<body class="w3-container">
  <h3>Election Winners</h3>

  <?php while($pos = $positions->fetch_assoc()):
    $posID = $pos['posID'];
    $limit = (int)$pos['numOfPositions'];
    // get candidates for this position with vote counts
    $cands = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM votes v WHERE v.candID=c.candID AND v.posID=$posID) as votes
                          FROM candidates c WHERE c.posID=$posID ORDER BY votes DESC LIMIT $limit");
  ?>
    <div class="w3-card-2 w3-padding w3-margin-bottom">
      <h4><?php echo htmlentities($pos['posName']); ?></h4>
      <table class="w3-table w3-bordered">
        <tr><th>Winner</th><th>Total Votes</th></tr>
        <?php while($w = $cands->fetch_assoc()): ?>
          <tr>
            <td><?php echo htmlentities($w['candFName'].' '.$w['candMName'].' '.$w['candLName']); ?></td>
            <td><?php echo (int)$w['votes']; ?></td>
          </tr>
        <?php endwhile; ?>
      </table>
    </div>
  <?php endwhile; ?>

  <p><a class="w3-button" href="index.php">Back</a></p>
</body></html>
