<?php
include '../config.php';
session_start();

// Protect page - only recipients can access
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] != "recipient") {
    header("Location: login.php");
    exit();
}

// Get listing_id from the URL
$listing_id = $_GET["listing_id"];

// Get the food listing details
$result = pg_query_params($conn,
    "SELECT food_listings.*, users.full_name AS donor_name, users.location AS donor_location
     FROM food_listings
     JOIN users ON food_listings.donor_id = users.id
     WHERE food_listings.id = $1 AND food_listings.status = 'available'",
    array($listing_id)
);

$listing = pg_fetch_assoc($result);

// If listing doesn't exist or already claimed redirect back
if (!$listing) {
    header("Location: recipient-dashboard.php");
    exit();
}

$success = "";
$error = "";

// Handle claim form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pickup_date = $_POST["pickup_date"];
    $recipient_phone = trim($_POST["recipient_phone"]);
    $recipient_id = $_SESSION["user_id"];

    // Handle location if provided
    if (isset($_POST["latitude"]) && $_POST["latitude"] != "") {
        pg_query_params($conn,
            "UPDATE users SET latitude = $1, longitude = $2 WHERE id = $3",
            array($_POST["latitude"], $_POST["longitude"], $recipient_id)
        );
    }

    // Insert claim
    $claim = pg_query_params($conn,
        "INSERT INTO claims (listing_id, recipient_id, pickup_date, recipient_phone)
         VALUES ($1, $2, $3, $4)",
        array($listing_id, $recipient_id, $pickup_date, $recipient_phone)
    );

    // Update food listing status to claimed
    pg_query_params($conn,
        "UPDATE food_listings SET status = 'claimed' WHERE id = $1",
        array($listing_id)
    );

    if ($claim) {
        $success = "You have successfully claimed this listing! The donor will be notified.";
    } else {
        $error = "Something went wrong: " . pg_last_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Food | Food Redistribution System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="#">Food Relief System</a>
        <div class="ms-auto">
            <span class="text-white me-3">Welcome, <?php echo $_SESSION["user_name"]; ?></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-4">

                <h5 class="mb-4">Claim Food Listing</h5>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <a href="recipient-dashboard.php" class="btn btn-success w-100">Back to Dashboard</a>
                <?php else: ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <!-- Show listing details -->
                    <div class="alert alert-light border mb-4">
                        <h6><?php echo $listing["food_name"]; ?></h6>
                        <p class="mb-1"><strong>Quantity:</strong> <?php echo $listing["quantity"] . " " . $listing["unit"]; ?></p>
                        <p class="mb-1"><strong>Expires:</strong> <?php echo date("d M Y", strtotime($listing["expiry_date"])); ?></p>
                        <p class="mb-1"><strong>Pickup Location:</strong> <?php echo $listing["donor_location"]; ?></p>
                        <p class="mb-1"><strong>Donor:</strong> <?php echo $listing["donor_name"]; ?></p>
                        <?php if ($listing["notes"]): ?>
                            <p class="mb-0"><strong>Notes:</strong> <?php echo $listing["notes"]; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Claim form -->
                    <form method="POST" action="claim.php?listing_id=<?php echo $listing_id; ?>" id="claim-form">

                        <div class="mb-3">
                            <label class="form-label">Preferred Pickup Date</label>
                            <input type="date" name="pickup_date" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Your Phone Number</label>
                            <input type="text" name="recipient_phone" class="form-control" placeholder="e.g. 0712345678" required>
                            <small class="text-muted">The rider will call you on arrival.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Your Dropoff Location</label>
                            <input type="text" name="manual_location" id="manual_location" class="form-control" placeholder="e.g. Kibera, Nairobi">
                            <small class="text-muted">Type your location or detect it automatically below.</small>
                        </div>

                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-success w-100" onclick="getLocation()">
                                Detect My Location Automatically
                            </button>
                            <div id="location-status" class="mt-2"></div>
                        </div>

                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">

                        <button type="submit" class="btn btn-success w-100">Confirm Claim</button>
                        <a href="recipient-dashboard.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>

                    </form>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script>
function getLocation() {
    if (navigator.geolocation) {
        document.getElementById("location-status").innerHTML = '<small class="text-muted">Detecting location...</small>';
        navigator.geolocation.getCurrentPosition(function(position) {
            document.getElementById("latitude").value = position.coords.latitude;
            document.getElementById("longitude").value = position.coords.longitude;
            document.getElementById("location-status").innerHTML = '<small class="text-success">✓ Location detected successfully!</small>';
        }, function(error) {
            document.getElementById("location-status").innerHTML = '<small class="text-danger">Could not detect location. Please type it manually.</small>';
        });
    } else {
        document.getElementById("location-status").innerHTML = '<small class="text-danger">Your browser does not support location detection.</small>';
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>