<?php
// Establish database connection
$servername = "localhost";
$username = "u415861906_infosec2223";
$password = "IM8OTSc=9cU";
$dbname = "u415861906_infosec2223";

// Create connection
$conn = new mysqli('localhost', 'u415861906_infosec2223', 'IM8OTSc=9cU', 'u415861906_infosec2223');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Insert clip logic (for handling new clip submission)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_clip'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $video_url = $_POST['video_url'];

    // Insert the new clip into the database
    $stmt = $conn->prepare("INSERT INTO valorant_clips (title, description, video_url) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $description, $video_url);

    if ($stmt->execute()) {
        echo "<script>showModal('Clip submitted successfully!');</script>";
    } else {
        echo "<script>showModal('Error submitting clip: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}

// Update clip logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_clip'])) {
    // Check if the necessary fields are set before using them
    if (isset($_POST['clip_id']) && isset($_POST['new_title']) && isset($_POST['new_description'])) {
        $clip_id = $_POST['clip_id'];
        $new_title = $_POST['new_title'];
        $new_description = $_POST['new_description'];

        // Update the clip in the database
        $stmt = $conn->prepare("UPDATE valorant_clips SET title = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_title, $new_description, $clip_id);

        if ($stmt->execute()) {
            echo "<script>showModal('Clip updated successfully!');</script>";
        } else {
            echo "<script>showModal('Error updating clip: " . $stmt->error . "');</script>";
        }

        $stmt->close();
    } else {
        echo "<script>showModal('Please ensure all fields are filled out.');</script>";
    }
}

// Delete clip logic
if (isset($_POST['delete_clip'])) {
    $clip_id = $_POST['clip_id'];

    // Delete the clip from the database
    $stmt = $conn->prepare("DELETE FROM valorant_clips WHERE id = ?");
    $stmt->bind_param("i", $clip_id);

    if ($stmt->execute()) {
        echo "<script>showModal('Clip deleted successfully!');</script>";
    } else {
        echo "<script>showModal('Error deleting clip: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}


// Fetch clips from the database
$sql = "SELECT * FROM valorant_clips";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valorant Clip Submission</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Orbitron', sans-serif;
            background: linear-gradient(to bottom, #e74c3c, #2c3e50);
            color: #FFFFFF;
            margin: 0;
            padding: 0;
        }
        h1, h2 {
            text-align: center;
            color: #f39c12;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        }
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
        }
        form {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
        }
        input[type="text"], textarea, input[type="file"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #f39c12;
            border-radius: 4px;
            font-size: 16px;
            color: #fff;
            background-color: transparent;
            font-family: 'Orbitron', sans-serif;
            box-sizing: border-box;
        }
        button {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 15px 25px;
            cursor: pointer;
            font-size: 18px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
            font-family: 'Orbitron', sans-serif;
        }
        button:hover {
            background-color: #c0392b;
        }
        .clip {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
        }
        .clip h3 {
            margin-top: 0;
            color: #f39c12;
        }
        .clip p {
            color: #ddd;
        }
        .clip a {
            color: #3498db;
            text-decoration: none;
        }
        .clip a:hover {
            text-decoration: underline;
        }
        .clip-form {
            margin-top: 20px;
        }
        .clip-form input[type="text"], .clip-form textarea, .clip-form input[type="file"] {
            width: calc(100% - 20px);
        }
        .clip-form button {
            background-color: #e74c3c;
        }
        .clip-form button:hover {
            background-color: #c0392b;
        }
        input[type="url"] {
        width: 100%;
        padding: 12px;
        margin: 10px 0 20px 0;  /* Added more space at the bottom */
        border: 1px solid #f39c12;
        border-radius: 4px;
        font-size: 16px;
        color: #fff;
        background-color: transparent;
        font-family: 'Orbitron', sans-serif;
        box-sizing: border-box;
        }

        /* Modal Styles */
.modal {
    display: none; /* Hidden by default */
    position: fixed;
    z-index: 1; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    background-color: rgba(0,0,0,0.4); /* Black with opacity */
    padding-top: 60px;
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%; /* Could be more or less, depending on screen size */
    text-align: center;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}


        
    </style>
</head>
<body>
<div id="successModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalMessage">Clip updated successfully!</h2>
    </div>
</div>

<div class="container">
        <h1>Clip Submission</h1>

        <form method="POST" action="">
            <input type="text" name="title" placeholder="Title" required><br>
            <textarea name="description" placeholder="Description" required></textarea><br>
            <input type="url" name="video_url" placeholder="Enter video URL" required><br>
            <button type="submit" name="submit_clip">Submit Clip</button>
        </form>

        <h2>Existing Clips</h2>

        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="clip">
                <h3><?php echo $row['title']; ?></h3>
                <p>Description: <?php echo $row['description']; ?></p>
                <p><a href="<?php echo $row['video_url']; ?>" target="_blank">Watch Video</a></p>

                <!-- Update Form -->
                <form method="POST" action="">
                    <input type="hidden" name="clip_id" value="<?php echo $row['id']; ?>">
                    <h4>Update Clip</h4>
                    <label for="new_title">New Title: </label>
                    <input type="text" name="new_title" value="<?php echo $row['title']; ?>" required><br>
                    <label for="new_description">New Description: </label>
                    <textarea name="new_description" required><?php echo $row['description']; ?></textarea><br>
                    <button type="submit" name="update_clip">Update Clip</button>
                </form>

                <!-- Delete Form -->
                <form method="POST" action="">
                    <input type="hidden" name="clip_id" value="<?php echo $row['id']; ?>">
                    <button type="submit" name="delete_clip" onclick="return confirm('Are you sure you want to delete this clip?')">Delete Clip</button>
                </form>
            </div>
        <?php endwhile; ?>

    </div>
</body>
</html>
<script> 
    var modal = document.getElementById("successModal");
var closeBtn = document.getElementsByClassName("close")[0];

// Show the modal with a custom message
function showModal(message) {
    document.getElementById("modalMessage").innerText = message;
    modal.style.display = "block"; // Show the modal
}

// Close the modal when the close button is clicked
closeBtn.onclick = function() {
    modal.style.display = "none"; // Hide the modal
}

// Close the modal if the user clicks outside of the modal
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none"; // Hide the modal
    }
}
</script>

<?php
$conn->close();
?>
