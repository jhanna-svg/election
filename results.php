<?php
/**
 * ELECTION RESULTS PAGE
 * 
 * This page displays the election results:
 * - Shows vote counts for each candidate per position
 * - Calculates and displays voting percentages
 * - Displays results for all positions
 */

include 'db.php';

// Get all positions to display results for each one
$positions = $conn->query("SELECT * FROM positions ORDER BY posID");
?>
<!DOCTYPE html><html>
<head><title>Results</title><link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"></head>
<body class="w3-container">
  <h3>Election Results</h3>

  <!-- Loop through each position to display its results -->
  <?php while($pos = $positions->fetch_assoc()):
     $posID = $pos['posID'];
     
     // Calculate total number of votes cast for this position
     // (Note: This counts vote records, not unique voters, since voters can vote for multiple candidates)
     $totalRow = $conn->query("SELECT COUNT(*) as total FROM votes WHERE posID=$posID")->fetch_assoc();
     $total = (int)$totalRow['total'];
     
     // Get all candidates for this position with their vote counts
     // Uses subquery to count votes for each candidate
     $candidates = $conn->query("SELECT c.*, 
          (SELECT COUNT(*) FROM votes v WHERE v.candID=c.candID AND v.posID=$posID) as votes
       FROM candidates c WHERE c.posID=$posID");
  ?>
    <!-- Results card for this position -->
    <div class="w3-card-2 w3-padding w3-margin-bottom">
      <h4><?php echo htmlentities($pos['posName']); ?></h4>
      <table class="w3-table w3-bordered">
        <tr><th>Candidate</th><th>Total Votes</th><th>Voting %</th></tr>
        <?php while($c = $candidates->fetch_assoc()):
            $v = (int)$c['votes']; // Number of votes for this candidate
            // Calculate percentage (avoid division by zero)
            $pct = $total>0 ? round(($v/$total)*100,2) : '';
        ?>
          <tr>
            <!-- Display candidate full name -->
            <td><?php echo htmlentities($c['candFName'].' '.$c['candMName'].' '.$c['candLName']); ?></td>
            <!-- Display vote count -->
            <td><?php echo $v; ?></td>
            <!-- Display percentage (only if there are votes) -->
            <td><?php echo $pct !== '' ? $pct.' %' : ''; ?></td>
          </tr>
        <?php endwhile; ?>
      </table>
    </div>
  <?php endwhile; ?>

  <p><a class="w3-button" href="index.php">Back</a></p>
</body></html>