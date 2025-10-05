<?php
// Practical_6.php
// Saves registration rows into registrations.csv and shows a success page.

// DEVELOPMENT: show errors in the browser so you can debug during the practical.
// Remove or disable these lines on a production server.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set timezone so timestamps are correct
date_default_timezone_set('Asia/Kolkata');

function clean($str) {
    return trim(htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
}

// Only accept POST submissions
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Register.html');
    exit;
}

// Read and sanitize inputs
$fullname = isset($_POST['fullname']) ? clean($_POST['fullname']) : '';
$username = isset($_POST['username']) ? clean($_POST['username']) : '';
$email = isset($_POST['email']) ? clean($_POST['email']) : '';
$phone = isset($_POST['phone']) ? clean($_POST['phone']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

$errors = [];

// Server-side validation
if ($fullname === '') $errors[] = 'Full name is required.';
if ($username === '') $errors[] = 'Username is required.';
if ($email === '') $errors[] = 'Email is required.';
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email is not valid.';
if ($password === '') $errors[] = 'Password is required.';
elseif (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';
if ($phone !== '' && !preg_match('/^\d{10}$/', $phone)) $errors[] = 'Phone must be 10 digits if provided.';

if (!empty($errors)) {
    // Show a simple error page listing the validation messages
    ?>
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <title>Submission errors</title>
      <style> body{font-family:Arial, sans-serif;padding:24px} .err{color:#dc2626} </style>
    </head>
    <body>
      <h1>There were problems with your submission</h1>
      <ul class="err">
        <?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?>
      </ul>
      <p><a href="Register.html">Go back to the form</a></p>
    </body>
    </html>
    <?php
    exit;
}

// Prepare data to write
$timestamp = date('Y-m-d H:i:s');
$hashed_password = password_hash($password, PASSWORD_DEFAULT); // store hash, not plain text
$row = [$timestamp, $fullname, $username, $email, $phone, $hashed_password];

// File to store registrations. It will be created in the same folder.
$csvFile = __DIR__ . '/registrations.csv';

// If file does not exist, create it and write header line
if (!file_exists($csvFile)) {
    $f = fopen($csvFile, 'w');
    if ($f === false) {
        die('Unable to create data file. Check folder permissions.');
    }
    fputcsv($f, ['timestamp','fullname','username','email','phone','password_hash']);
    fclose($f);
}

// Append the new row safely
$f = fopen($csvFile, 'a');
if ($f === false) {
    die('Unable to open data file. Check folder permissions.');
}

if (flock($f, LOCK_EX)) {
    fputcsv($f, $row);
    fflush($f);
    flock($f, LOCK_UN);
    fclose($f);
} else {
    fclose($f);
    die('Could not lock the data file. Try again later.');
}

// Show success page
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Registration Successful</title>
  <style>
    body{font-family:system-ui, -apple-system, "Segoe UI", Roboto, Arial; background:#f6f9fc; padding:40px}
    .card{max-width:650px;margin:20px auto;background:#fff;padding:24px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.06)}
    h1{margin:0 0 8px}
    .success{color:#16a34a;font-weight:600}
    .small{color:#6b7280}
    .btn{display:inline-block;margin-top:14px;padding:10px 14px;border-radius:8px;text-decoration:none;background:#3b82f6;color:white}
  </style>
</head>
<body>
  <div class="card">
    <h1>Thank you, <?php echo htmlspecialchars($fullname); ?></h1>
    <p class="success">Your form has been recorded successfully.</p>
    <p class="small">Your data is saved to <code>registrations.csv</code> in this folder.</p>
    <p>
      <a class="btn" href="Register.html">Submit another response</a>
      <!-- Optional: a link to the CSV file. If you do not want the CSV visible in browser, remove this line. -->
      <a style="margin-left:10px" href="registrations.csv" download>Download CSV</a>
    </p>
  </div>
</body>
</html>
