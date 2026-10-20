<?php
session_start();
require __DIR__ . '/../lib/backend.php';

// Kurt Castro | 10.19

// Copied from myGarage.php
// Using request format agreed with Andrew

// Reviews: view/write ratings + reviews of vehicles
// Reuses myGarage search feature 

// To do: list ratings + reviews, write reviews, recalls + complaints link

// session checker
if (empty($_SESSION['session_key'])) {
	header('Location: login.php'); // redirects to login if no session key
	exit;
}

$msg = '';

// decides which popup is displayed based on which button is clicked
$action = '';
if (isset($_GET['action'])) {
	$action = $_GET['action'];
}

// what the user selected in the search dropdown
$pick_year = 0;
if (isset($_GET['year'])) {
	$pick_year = (int)$_GET['year'];
}

$pick_make = '';
if (isset($_GET['make'])) {
	$pick_make = trim($_GET['make']);
}

$pick_model = '';
if (isset($_GET['model'])) {
	$pick_model = trim($_GET['model']);
}

// write review form / just copied from previous myGarage.php 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {  // only runs after form is submitted
	$pick_year = (int)$_POST['year']; // keeps picked so review shows after posting
	$pick_make = trim($_POST['make']);
	$pick_model = trim($_POST['model']);
	$rating = (int)$_POST['rating'];
	$text = trim($_POST['text']);

        if ($rating < 1 || $rating > 5) { // server-side checks for empty dropdowns
		$msg = 'Rating must be 1-5.';
	} elseif ($text == '') {
		$msg = 'Write something for your review.';
	} else {
		$reply = send_request([ // review tied to year/make/model
			'type' => 'write_review',
			'session_key' => $_SESSION['session_key'],
			'username' => $_SESSION['username'],
			'year' => $pick_year,
			'make' => $pick_make,
			'model' => $pick_model,
			'rating' => $rating,
			'text' => $text,
		]);
		$msg = $reply['message'];
	}
}
   
// dropdown lists for the search popup (same as add in myGarage.php)
// in NHTSA's order: year, make model
// each dropdown only loads once previous one has been selected 
$years = [];
$makes = [];
$models = [];

$reply = send_request([ // all model years NHTSA has complaint data for (straight to Evans)
	'type' => 'get_years',
]);
if (isset($reply['years'])) {
	$years = $reply['years'];
        }
if ($pick_year != 0) { // only lists makes that were available from selected year
	$reply = send_request([ 
		'type' => 'get_makes',
		'year' => $pick_year,
	]);
	if (isset($reply['makes'])) {
		$makes = $reply['makes'];
	}
}
if ($pick_year != 0 && $pick_make != '') { // models that exist for that year + make
	$reply = send_request([ 
		'type' => 'get_models',
		'year' => $pick_year,
		'make' => $pick_make,
	]);
	if (isset($reply['models'])) {
		$models = $reply['models'];
	}
}

// grays out make/model dropdown until there's something to pick
$make_disabled = 'disabled';
if ($makes) {
	$make_disabled = '';
}
$model_disabled = 'disabled';
if ($models) {
	$model_disabled = '';
}

// need to load ratings from DB when all dropdowns are picked
// each review tied to year/make/model
$searched = false;
$avg_rating = 0;
$reviews = [];
if ($pick_year != 0 && $pick_make != '' && $pick_model != '') {
	$searched = true;
	$reply = send_request([
		'type' => 'get_reviews',
		'session_key' => $_SESSION['session_key'],
		'username' => $_SESSION['username'],
		'year' => $pick_year,
		'make' => $pick_make,
		'model' => $pick_model,
	]);
	if (isset($reply['avg_rating'])) {
		$avg_rating = $reply['avg_rating'];
	}
	if (isset($reply['reviews'])) {
		$reviews = $reply['reviews'];
	}
}

// recalls + complaints popup (link)
// by year/make/model since car doesn't have to be in garage
$recalls = [];
$complaints = [];
if ($action === 'info' && $searched) {
	$reply = send_request([
		'type' => 'get_vehicle_info',
		'session_key' => $_SESSION['session_key'],
		'username' => $_SESSION['username'],
		'year' => $pick_year,
		'make' => $pick_make,
		'model' => $pick_model,
	]);
	if (isset($reply['recalls'])) {
		$recalls = $reply['recalls'];
	}
	if (isset($reply['complaints'])) {
		$complaints = $reply['complaints'];
	}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Reviews</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<main>
<h3>Reviews</h3>
<p><a href="home.php">Home</a></p>

<?php if ($msg): // prevent XSS by escaping <,> so HTML can't be ran in msg ?> 
  <p><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<?php // search dropdowns in place of a popup (same as myGarage.php, page reloads after selection) ?>
    <form method="GET" action="reviews.php">
    <div>
      <label for="year">Year</label>
      <select id="year" name="year" onchange="this.form.submit()">
	<option value="">-- Pick a year --</option>
	<?php foreach ($years as $y): ?>
	  <?php if ($y == $pick_year): // keeps the picked year selected after reload ?>
	    <option value="<?= htmlspecialchars($y) ?>" selected><?= htmlspecialchars($y) ?></option>
          <?php else: ?>
	    <option value="<?= htmlspecialchars($y) ?>"><?= htmlspecialchars($y) ?></option>
          <?php endif; ?>
	<?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="make">Make</label>
      <select id="make" name="make" onchange="this.form.submit()" <?= $make_disabled ?>>
        <option value="">-- Pick a Make --</option>
        <?php foreach ($makes as $m): ?>
	  <?php if ($m === $pick_make): // keeps selected make even after reload ?>
        <option value="<?= htmlspecialchars($m) ?>" selected><?= htmlspecialchars($m) ?></option>
          <?php else: ?>
        <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
          <?php endif; ?>
	<?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="model">Model</label>
      <select id="model" name="model" onchange="this.form.submit()" <?= $model_disabled ?>>
        <option value="">-- Pick a Make --</option>
        <?php foreach ($models as $m): ?>
	  <?php if ($m === $pick_make): // keeps selected model after reload ?>
        <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
          <?php else: ?>
        <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
          <?php endif; ?>
        <?php endforeach; ?>
          </select>
        </div>
      </form>

  <?php if ($searched): // only shows when car is picked ?>
  <h3><?= htmlspecialchars($pick_year . ' ' . $pick_make . ' ' . $pick_model) ?></h3>
  <p><a href="reviews.php?action=info&year=<?= (int)$pick_year ?>&make=<?= urlencode($pick_make) ?>&model=<?= urlencode($pick_model) ?>">View Recalls & Complaints</a></p>

  <?php if (!$reviews): ?>
  <p>No reviews yet. Be the first to write one.</p>
  <?php else: ?>
  <p>Average rating: <?= htmlspecialchars($avg_rating) ?> / 5</p>
  <?php endif; ?>

  <?php foreach ($reviews as $r): // escape chars since reviews are typed
  <p><strong><?= htmlspecialchars($r['username']) ?></strong> - <?= (int)$r['rating'] ?> / 5 - <?= date('m/d/Y', (int)$r['date']) ?></p>
  <p><?= htmlspecialchars($r['text']) ?></p>
  <?php endforeach; ?>
    <div>
      <label for="model">Model</label>
      <select id="model" name="model" <?= $model_disabled ?>>
        <option value="">-- Pick a Model --</option>
        <?php foreach ($models as $m): ?>
      <?php if ($m === $pick_model): // keeps selected model after reload ?>
        <option value="<?= htmlspecialchars($m) ?>" selected><?= htmlspecialchars ($m) ?></option>
          <?php else: ?>
	<option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
          <?php endif; ?>
	<?php endforeach; ?>
        <h3>Write a Review</h3>
        <?php // which car the review is for ?>
        <form method="POST" action="reviews.php">
            <input type="hidden" name="year" value="<?= (int)$pick_year ?>" />
            <input type="hidden" name="make" value="<?= htmlspecialchars($pick_make) ?>" />
	    <input type="hidden" name="model" value="<?= htmlspecialchars($pick_model) ?>" />

            <div>
              <label for="rating">Rating</label>
              <select id="rating" name="rating" required>
         <option value="">-- Pick a rating --</option>
         <option value="5">5 - Excellent</option>
         <option value="4">4 - Good</option>
         <option value="3">3 - Okay</option>
         <option value="2">2 - Bad</option>
         <option value="1">1 - Terrible</option>
           </select>
         </div>
	 <div>
           <label for="text">Review</label>
           <textarea id="text" name="text" required maxlength="1000"></textarea>
         </div>
         <button type="submit">Post Review</button>
</form>
<?php endif; ?>

<?php if ($action === 'info' && $searched): // reused from details popup in myGarage.php ?>
<dialog id="popup">
    <h3><?= htmlspecialchars($pick_year . ' ' . $pick_make . ' ' . $pick_model) ?></h3>

    <h3>Recall History</h3>
    <?php if (!$recalls): ?>
    <p>No recalls found</p>
    <?php endif; ?>
    <?php foreach ($recalls as $r): ?>
    <p><strong><?= htmlspecialchars($r['component']) ?></strong> - <?= date('m/d/Y', (int)$r['date']) ?></p>
    <p>Campaign: <?= htmlspecialchars($r['campaign']) ?></p>
    <p><?= htmlspecialchars($r['summary']) ?></p>
    <?php endforeach; ?>

    <h3>Complaints</h3>
    <?php if (!$complaints): ?>
    <p>No complaints found</p>
    <?php endif; ?>
    <?php foreach ($complaints as $c): ?>
    <p><strong><?= htmlspecialchars($c['component']) ?></strong> - <?= date('m/d/Y', (int)$c['date']) ?></p>
    <p><?= htmlspecialchars($c['summary']) ?></p>
    <?php endforeach; ?>

    <p><a href="reviews.php">Close</a></p>
</dialog>
<?php endif; ?>

<?php if ($searched): // only shows when car is picked ?>
<h3><?= htmlspecialchar($pick_year . ' ' . $pick_make . ' ' . $pick_model) ?></h3>
    <p><a href="reviews.php?action=info&year=<?= (int)$pick_year ?>&make=<?= urlencode($pick_make) ?>&model=<?= urlencode($pick_model) ?>">View Recalls & Complaints</a></p>

<?php if (!$reviews): ?>
<p>No reviews yet. Be the first to write one.</p>
<?php else: ?>
<p>Average rating: <?= htmlspecialchars($avg_rating) ?> / 5</p>
<?php endif; ?>

<?php foreach ($reviews as $r): // need to escape since reviews are typed ?>
<p><strong><?= htmlspecialchars($r['username']) ?></strong> - <?= (int)$r['rating'] ?> / 5 - <?= date('m/d/Y', (int)$r['date']) ?></p>
<p><?= htmlspecialchars($r['text']) ?></p>
<?php endforeach; ?>
<?php endif; ?>

<script>
let popup = document.getElementById('popup'); // only display when popup was asked for
if (popup) {
	popup.showModal(); // hidden until opened
}
</script>
</main>
</body>
</html>
