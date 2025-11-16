<?php
/**
 * ELECTION WINNERS PAGE
 * 
 * This page displays the winners of the election:
 * - Shows top candidates for each position based on vote count
 * - Number of winners displayed equals the number of available slots (numOfPositions)
 * - Results are sorted by vote count in descending order
 * - Example: Shows 1 winner for President, up to 12 winners for Senators
 */

include 'db.php';
// Get all positions to determine winners for each
$positions = $conn->query("SELECT * FROM positions ORDER BY posID");
?>
<!DOCTYPE html><html>
<head><title>Winners</title><link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"></head>
<body class="w3-container">
  <h3>Election Winners</h3>

  <!-- Loop through each position to display its winners -->
  <?php while($pos = $positions->fetch_assoc()):
    $posID = $pos['posID'];
    $limit = (int)$pos['numOfPositions']; // Number of winners to display (e.g., 1 for President, 12 for Senators)
    
    // Get top candidates for this position, ordered by vote count (descending)
    // LIMIT restricts results to the number of available slots
    $cands = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM votes v WHERE v.candID=c.candID AND v.posID=$posID) as votes
                          FROM candidates c WHERE c.posID=$posID ORDER BY votes DESC LIMIT $limit");
  ?>
    <!-- Winners card for this position -->
    <div class="w3-card-2 w3-padding w3-margin-bottom">
      <h4><?php echo htmlentities($pos['posName']); ?></h4>
      <table class="w3-table w3-bordered">
        <tr><th>Winner</th><th>Total Votes</th></tr>
        <?php while($w = $cands->fetch_assoc()): ?>
          <tr>
            <!-- Display winner's full name -->
            <td><?php echo htmlentities($w['candFName'].' '.$w['candMName'].' '.$w['candLName']); ?></td>
            <!-- Display winner's vote count -->
            <td><?php echo (int)$w['votes']; ?></td>
          </tr>
        <?php endwhile; ?>
      </table>
    </div>
  <?php endwhile; ?>

  <p><a class="w3-button" href="index.php">Back</a></p>
</body></html>
