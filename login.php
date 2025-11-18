<?php
include 'db.php';
session_start();

$msg = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST['voterID'];
    $pass = $_POST['voterPass'];

    $q = $conn->query("SELECT * FROM voters WHERE BINARY voterID='$id' AND voterPass='$pass' AND voterStat='active'");
    if ($q && $q->num_rows === 1) {
        $v = $q->fetch_assoc();
        if ($v['voted'] === 'Y') {
            $msg = "You have already voted. Voting is not allowed again.";
        } else {
            $_SESSION['voterID'] = $id;
            header("Location: vote.php");
            exit;
        }
    } else {
        $msg = "Invalid login or inactive account.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Voter Login</title>
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
</head>
<body class="w3-container">

<h3>Voter Login</h3>

<?php if ($msg): ?>
<div class="w3-panel w3-pale-red w3-border"><?php echo $msg; ?></div>
<?php endif; ?>

<form method="post" class="w3-card-4 w3-padding" style="max-width:400px;">
    <label>Voter ID</label>
    <input class="w3-input" name="voterID" required>

    <label>Password</label>
    <input class="w3-input" type="password" name="voterPass" required>

    <button class="w3-button w3-green w3-margin-top" name="login">Login to Vote</button>
</form>

<p><a class="w3-button" href="index.php">Back</a></p>

</body>
</html>