<?php
session_start();
require __DIR__ . '/../lib/backend.php';

// Kurt Castro | 10.24

// Copied from myGarage.php

// Shop Finder: search for shops near entered location or user's current location
// Results from Google Places 

// session checker
if (empty($_SESSION['session_key'])) {
	header('Location: login.php'); // redirects to login if no session key
	exit;
}

$msg = '';

// what user picked in search (service)
$pick_service = '';
if (isset($_GET['service'])) {
	$pick_service = trim($_GET['service']);
}

$pick_location = '';
if (isset($_GET['location'])) {
	$pick_location = trim($_GET['location']);
}

$pick_make = ''; // from make dropdown / empty = any
if (isset($_GET['make'])) {
	$pick_make = trim($_GET['make']);
}

// fixed list for dropdowns / updated to match Evans 10.25
// no dropdown cause Evans only uses radius with coords + city search
$services = ['repair' => 'Auto repair', 'tire' => 'Tire shop', 'body_shop' => 'Body shop', 'dealer' => 'Dealership', 'car_wash' => 'Car wash'];

// makes from user's garage / same req as myGarage
$makes = [];
$garage = send_request([
	'type' => 'get_garage',
	'session_key' => $_SESSION['session_key'],
	'username' => $_SESSION['username'],
]);
if (isset($garage['cars'])) {
	foreach ($garage['cars'] as $car) {
		$makes[] = $car['make'];
		if (!in_array($car['make'], $makes)) { 
			$makes[] = $car['make'];
		}
	}
}

// runs search when form is sent 
$searched = false;
$shops = [];
if (isset($_GET['search'])) {
	if ($pick_location == '' | $pick_service == '') { // dont send blank search cause of usage
		$msg = 'Select a service and type a city or ZIP.';
	} else {
		$searched = true;
		$reply = send_request([ // straight to DMZ / no session key cause public
			'type' => 'shopSearch',
			'service' => $pick_service,
			'location' => $pick_location,
			'make' => $pick_make,
		]);
		if (isset($reply['shops'])) {
			$shops = $reply['shops'];
		}
		if ($reply['status'] !== 'success') { // show DMZ error
			$msg = $reply['message'];
		}
	}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Shop Finder</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<main>
<h3>Shop Finder</h3>
<p><a href="home.php">Home</a></p>

<?php if ($msg): // prevent XSS by escaping <,> so HTML can't be ran in msg ?> 
  <p><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<?php // search form / similar to reviews search ?>
<form method="GET" action="shopFinder.php" id="shopform">
    <input type="hidden" name="search" value="1" />
    <div>
      <label for="service">Service</label>
      <select id="service" name="service">
    <option value="">-- Pick a service --</option>
    <?php foreach ($services as $key => $label): // key sent to Evans ?>
      <?php if ($key === $pick_service): // keeps service selected after search ?>
        <option value="<?= htmlspecialchars($key) ?>" selected><?= htmlspecialchars($label) ?></option>
      <?php else: ?>
        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
      <?php endif; ?>
    <?php endforeach; ?>
      </select>
    </div>
    <div> 
      <label for="make">For my car</label>
      <select id="make" name="make">
    <option value="">-- Any make --</option>
    <?php foreach ($makes as $m): // escape since it comes from API through garage ?>
      <?php if ($m === $pick_make): // keeps picked make selected after search ?>
        <option value="<?= htmlspecialchars($m) ?>" selected><?= htmlspecialchars($m) ?></option>
      <?php else: ?>
        <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
      <?php endif; ?>
    <?php endforeach; ?>
        </select>
      </div>
    <div>
      <label for="location">City or ZIP</label>
      <input type="text" id="location" name="location" maxlength="50" placeholder="Newark, NJ" value="<?= htmlspecialchars($pick_location) ?>" />
    </div>
    <button type="submit">Search</button>
</form>

<?php if (!$searched): ?>
<p>Pick a service and type a city or ZIP, then click Search.</p>
<?php else: ?>
<h3>Shops near <?= htmlspecialchars($pick_location) ?></h3>
<?php if (!$shops): ?>
<p>No shops found.</p>
<?php endif; ?>
<?php foreach ($shops as $s): // escape since its from API ?>
<div>
  <p><strong><?= htmlspecialchars($s['name']) ?></strong></p>
  <p><?= htmlspecialchars($s['address']) ?></p>
  <?php // _blank opens in new tab so search doesnt disappear ?>
  <p><a href="<?= htmlspecialchars($s['maps_url']) ?>" target="_blank">Open in Google Maps</a></p>
</div>
<?php endforeach; ?>
<?php endif; ?>

</main>
</body>
</html>
