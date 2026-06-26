<?php
include '../config.php';
session_start();

// Protect page
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] != "recipient") {
    header("Location: login.php");
    exit();
}

// Handle location update FIRST before any queries
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["latitude"])) {
    $latitude = $_POST["latitude"];
    $longitude = $_POST["longitude"];

    pg_query_params($conn,
        "UPDATE users SET latitude = $1, longitude = $2 WHERE id = $3",
        array($latitude, $longitude, $_SESSION["user_id"])
    );
}

// Get all available food listings
$listings = pg_query($conn, 
    "SELECT food_listings.*, users.full_name AS donor_name, users.location AS donor_location 
     FROM food_listings 
     JOIN users ON food_listings.donor_id = users.id 
     WHERE food_listings.status = 'available' 
     ORDER BY food_listings.created_at DESC"
);

// Get current recipient location
$loc = pg_query_params($conn,
    "SELECT location, latitude, longitude FROM users WHERE id = $1",
    array($_SESSION["user_id"])
);
$loc_row = pg_fetch_assoc($loc);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipient Dashboard | Food Redistribution System</title>
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

    <h4 class="mb-4">Available Food Listings</h4>

    <?php if (pg_num_rows($listings) == 0): ?>
        <div class="alert alert-info">No food listings available right now. Check back later.</div>
    <?php else: ?>
        <div class="row">
            <?php while ($row = pg_fetch_assoc($listings)): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $row["food_name"]; ?></h5>
                            <p class="card-text">
                                <strong>Quantity:</strong> <?php echo $row["quantity"] . " " . $row["unit"]; ?><br>
                                <strong>Expires:</strong> <?php echo $row["expiry_date"]; ?><br>
                                <strong>Location:</strong> <?php echo $row["donor_location"]; ?><br>
                                <strong>Donor:</strong> <?php echo $row["donor_name"]; ?><br>
                                <?php if ($row["notes"]): ?>
                                    <strong>Notes:</strong> <?php echo $row["notes"]; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="card-footer">
                            <a href="claim.php?listing_id=<?php echo $row["id"]; ?>" class="btn btn-success w-100">Claim This</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

    <!-- Location Section -->
    <div class="card shadow-sm mt-4 p-4">
        <h5 class="mb-3">My Location</h5>
        <p class="text-muted">Set your exact location so riders can find you easily.</p>

        <?php if ($loc_row["latitude"] && $loc_row["longitude"]): ?>
            <div class="alert alert-success">
                ✓ Exact location is set — riders can find you on Google Maps.
                <br>
                <small>Coordinates: <?php echo $loc_row["latitude"]; ?>, <?php echo $loc_row["longitude"]; ?></small>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                No exact location set yet. Riders will use your text address instead.
            </div>
        <?php endif; ?>

        <button class="btn btn-success" onclick="getLocation()">
            Detect My Location Automatically
        </button>

        <form method="POST" action="recipient-dashboard.php" id="location-form">
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">
        </form>
    </div>

</div>

<script>
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            document.getElementById("latitude").value = position.coords.latitude;
            document.getElementById("longitude").value = position.coords.longitude;
            document.getElementById("location-form").submit();
        }, function(error) {
            alert("Could not get location. Please allow location access in your browser.");
        });
    } else {
        alert("Your browser does not support location detection.");
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>