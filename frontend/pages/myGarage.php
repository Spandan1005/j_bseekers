<?php
session_start();
require __DIR__ . '/../lib/backend.php';

// Kurt Castro | 10.13

// Copied from register.php
// Using request format agreed with Spandan + Andrew
// Testing done locally until DB is completely set up

// My Garage: list of user's cars, add/remove buttons, popup with car details, dropdown list for car additions

// To do: list, buttons, popup

// session checker
if (empty($_SESSION['session_key'])) {
	header('Location: login.php'); // redirects to login if no session key
	exit;
}

$msg = '';

// add car form
// NHTSA's order: year, make, model
if ($_SERVER['REQUEST_METHOD'] === 'POST') {  // only runs after form is submitted
	$action = $_POST['action']; // which button was clicked
	
	if ($action === 'add') { // add car popup form
		$year = (int)$_POST['year'];
		$make = trim($_POST['make']);
		$model = trim($_POST['model']);

        if ($year == 0 || $make == '' || $model == '') { // server-side checks for empty dropdowns
		$msg = 'Pick a year, make, and model.';
        } else {
		$reply = send_request([ // sends car data to DB
			'type' => 'add_car',
			'session_key' => $_SESSION['session_key'], // tells DB whose garage it is
			'username' => $_SESSION['username'], // for Andrew's listeners + extra validation
			'year' => $year,
			'make' => $make,
			'model' => $model,
		]);
		$msg = $reply['message'];
	}
	}
       
	if ($action === 'remove') { // remove button (now works only if car is selected)
		$car_id = 0;
		if (isset($_POST['car_id'])) {
			$car_id = (int)$_POST['car_id'];
		}

                if ($car_id == 0) { // check for no car picked before remove
			$msg = 'Select a car first';
		} else {
			$reply = send_request([
				'type' => 'remove_car',
				'session_key' => $_SESSION['session_key'],
				'username' => $_SESSION['username'],
				'car_id' => $car_id,
			]);
			$msg = $reply['message'];
		}
	}
}

// decides which popup is displayed based on which button is clicked
$action = '';
if (isset($_GET['action'])) {
	$action = $_GET['action'];
}

// what the user selected in the add dropdown
$pick_year = 0;
if (isset($_GET['year'])) {
	$pick_year = (int)$_GET['year'];
}

$pick_make = '';
if (isset($_GET['make'])) {
	$pick_make = trim($_GET['make']);
}

// details popup
// API info still not stored in DB, creating sample skeleton for future plug + play (hopefully)
// ^ 10.13 / 11:34
$popup_car = null;
$recalls = [];
$complaints = [];

if ($action === 'view') {
	$car_id = 0;
	if (isset($_GET['car_id'])) {
		$car_id = (int)$_GET['car_id'];
	}
        if ($car_id == 0) { // no car id in link so doesn't show anything 
		$msg = 'Select a car first';
		$action = '';
	} else {
		$reply = send_request([ // DB sends back car with recalls + complaints
			'type' => 'get_car',
			'session_key' => $_SESSION['session_key'],
			'username' => $_SESSION['username'],
			'car_id' => $car_id,
		]);
		if (isset($reply['car'])) {
			$popup_car = $reply['car'];
		}
		if (isset($reply['recalls'])) {
			$recalls = $reply['recalls'];
		}
		if (isset($reply['complaints'])) {
			$complaints = $reply['complaints'];
		}
        }
}

// dropdown lists for the add popup
// in NHTSA's order: year, make model
// each dropdown only loads once previous one has been selected 
$years = [];
$makes = [];
$models = [];

if ($action === 'add') {
	$reply = send_request([ // all model years NHTSA has complaint data for
		'type' => 'get_years',
		'session_key' => $_SESSION['session_key'],
		'username' => $_SESSION['username'],
	]);
	if (isset($reply['years'])) {
		$years = $reply['years'];
        }
	if ($pick_year != 0) { // only lists makes that were available from selected year
		$reply = send_request([ 
			'type' => 'get_makes',
			'session_key' => $_SESSION['session_key'],
			'username' => $_SESSION['username'],
			'year' => $pick_year,
		]);
		if (isset($reply['makes'])) {
			$makes = $reply['makes'];
		}
	}
	if ($pick_year != 0 && $pick_make != '') { // models that exist for that year + make
		$reply = send_request([ 
			'type' => 'get_models',
			'session_key' => $_SESSION['session_key'],
			'username' => $_SESSION['username'],
			'year' => $pick_year,
			'make' => $pick_make,
		]);
		if (isset($reply['models'])) {
			$models = $reply['models'];
		}
	}
}

// grays out make/model dropdown + add car button until there's something to pick
$make_disabled = 'disabled';
if ($makes) {
	$make_disabled = '';
}
$model_disabled = 'disabled';
if ($models) {
	$model_disabled = '';
}

// need to load user's car every time page opens
$cars = [];
$garage = send_request([
	'type' => 'get_garage',
	'session_key' => $_SESSION['session_key'],
	'username' => $_SESSION['username'],
]);
if (isset($garage['cars'])) { // stays empty if DB doesn't have any cars stored to send back
	$cars = $garage['cars'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My Garage</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<main>
<h3>My Garage</h3>
<p><a href="home.php">Home</a></p>

<?php if ($msg): // prevent XSS by escaping <,> so HTML can't be ran in msg ?> 
  <p><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<form method="GET" action="myGarage.php">
    <p>
    <button type="submit" name="action" value="add">Add</button>
    <button type="submit" name="action" value="remove" formmethod="POST" onclick="return confirm('Are you sure you want to remove this car?')">Remove</button>
    </p>

    <?php // checks to see if list is empty instead of outputting "No cars yet" by default ?>
    <?php if (!$cars): ?>
    <p>You currently have 0 cars in your garage</p>
    <p>Click Add to add a car</p>
    <?php endif; ?>

    <?php foreach ($cars as $car): // name opens detail popup/radio button to select car to remove ?>
    <div>
      <input type="radio" name="car_id" value="<?= (int)$car['car_id'] ?>" />
      <a href="myGarage.php?action=view&car_id=<?= (int)$car['car_id'] ?>"><?= htmlspecialchars($car['year'] . ' ' . $car['make'] . ' ' . $car['model']) ?></a>
    </div>
    <?php endforeach; ?>
</form>

<?php if ($action === 'add'): ?>
<dialog id="popup">
    <h3>Add a Car</h3>
    <?php // picking year or make should reload the page so next dropdown shows results according to previous selection
    ?>
    <form method="GET" action="myGarage.php">
    <input type="hidden" name="action" value="add" />
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
      <select id="model" name="model" <?= $model_disabled ?>>
        <option value="">-- Pick a Model --</option>
        <?php foreach ($models as $m): ?>
          <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php // add car switches same form to POST so car gets saved instead of reloading list ?>
    <button type="submit" formmethod="POST" name="action" value="add" <?= $model_disabled ?>>Add Car</button>
    <a href="myGarage.php">Cancel</a>
    </form>
</dialog>
<?php endif; ?>

<?php if ($action === 'view' && $popup_car): // API not yet integrated, so testing locally with a fake backend / 10.14 ?>
	<dialog id="popup">
	    <h3><?= htmlspecialchars($popup_car['year'] . ' ' . $popup_car['make'] . ' ' . $popup_car['model']) ?></h3>
	    <p>Year: <?= htmlspecialchars($popup_car['year']) ?></p>
            <p>Make: <?= htmlspecialchars($popup_car['make']) ?></p>
	    <p>Model: <?= htmlspecialchars($popup_car['model']) ?></p>

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

            <p><a href="myGarage.php">Close</a></p>
       </dialog>
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
