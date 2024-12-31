<?php
// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "moviereservation";

//database connection
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}


session_start();

// para sa logout
if (isset($_GET['logout'])) {
    session_destroy();  // Destroy the session
    header("Location: general.php");  // Redirect to the homepage or login page
    exit();
}

$errorMessageLogin = "";
$errorMessageSignup = "";
$successMessageSignup = "";
$errorMessageProfile = "";
$successMessageProfile = "";

//login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['loginEmail']) && isset($_POST['loginPassword'])) {
    $inputEmailOrUsername = $_POST['loginEmail'];
    $inputPassword = $_POST['loginPassword'];

    $sql = "SELECT * FROM users WHERE username = :username OR email = :email";
    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':username', $inputEmailOrUsername, PDO::PARAM_STR);
    $stmt->bindParam(':email', $inputEmailOrUsername, PDO::PARAM_STR);


    $stmt->execute();

    //crosscheck users account
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($inputPassword, $user['password'])) {
            // If password is correct, start session and store user data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
        } else {
            $errorMessageLogin = "Invalid password.";
        }
    } else {
        $errorMessageLogin = "No user found with that username/email.";
    }
}

//signup
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signupUsername']) && isset($_POST['signupEmail']) && isset($_POST['signupPassword'])) {
    $signupUsername = $_POST['signupUsername'];
    $signupEmail = $_POST['signupEmail'];
    $signupPassword = password_hash($_POST['signupPassword'], PASSWORD_BCRYPT);

    //crosscheck for username and email
    $checkSql = "SELECT * FROM users WHERE username = :username OR email = :email";
    $stmt = $pdo->prepare($checkSql);
    $stmt->bindParam(':username', $signupUsername, PDO::PARAM_STR);
    $stmt->bindParam(':email', $signupEmail, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        //inserting in database
        $insertSql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
        $stmt = $pdo->prepare($insertSql);
        $stmt->bindParam(':username', $signupUsername, PDO::PARAM_STR);
        $stmt->bindParam(':email', $signupEmail, PDO::PARAM_STR);
        $stmt->bindParam(':password', $signupPassword, PDO::PARAM_STR);
        $stmt->execute();

        $successMessageSignup = "Registration successful! Please log in.";
    } else {
        $errorMessageSignup = "Username or email already exists.";
    }
}

//profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['updatedUsername']) && isset($_POST['updatedEmail'])) {
    $updateUsername = $_POST['updatedUsername'];
    $updateEmail = $_POST['updatedEmail'];
    $updatePassword = isset($_POST['updatedPassword']) ? $_POST['updatedPassword'] : '';


    // user or email exisiting
    $checkSql = "SELECT COUNT(*) FROM users WHERE (username = :username OR email = :email) AND id != :user_id";
    $stmt = $pdo->prepare($checkSql);
    $stmt->bindParam(':username', $updateUsername, PDO::PARAM_STR);
    $stmt->bindParam(':email', $updateEmail, PDO::PARAM_STR);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();

    $existing = $stmt->fetchColumn();

    if ($existing == 0) {
        if (!empty($updatePassword)) {
            $hashedPassword = password_hash($updatePassword, PASSWORD_DEFAULT);
            $updateSql = "UPDATE users SET username = :username, email = :email, password = :password WHERE id = :user_id";
            $stmt = $pdo->prepare($updateSql);
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        } else {
            $updateSql = "UPDATE users SET username = :username, email = :email WHERE id = :user_id";
            $stmt = $pdo->prepare($updateSql);
        }

        $stmt->bindParam(':username', $updateUsername, PDO::PARAM_STR);
        $stmt->bindParam(':email', $updateEmail, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $successMessageProfile = "Profile updated successfully!";
        } else {
            $errorMessageProfile = "No changes made to your profile.";
        }
    } else {
        $errorMessageProfile = "Username or email already exists.";
    }
}
function displayMessages($successMessage, $errorMessage) {
    if (!empty($successMessage)) {
        echo "<div class='success'>$successMessage</div>";
    }
    if (!empty($errorMessage)) {
        echo "<div class='error'>$errorMessage</div>";
    }
}
    
// delete account
if (isset($_POST['deleteAccount'])) {
    $deleteSql = "DELETE FROM users WHERE id = :user_id";
    $stmt = $pdo->prepare($deleteSql);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    session_destroy();
    header("Location: general.php");
    exit();
}

// display movies from db
function getMovies($pdo) {
    $sql = "SELECT * FROM movies";  
    $stmt = $pdo->prepare($sql);
    $stmt->execute();


    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// if logged in/ display
$movies = [];
if (isset($_SESSION['user_id'])) {
    $movies = getMovies($pdo);
}

// reservation form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['seats']) && isset($_POST['movieId'])) {
    $seats = $_POST['seats'];
    $movieId = $_POST['movieId'];
    $userId = $_SESSION['user_id'];  // Assuming the user is logged in

    // Insert reservation details into the database
    $reserveSql = "INSERT INTO reservations (user_id, movie_id, seats) VALUES (:user_id, :movie_id, :seats)";
    $stmt = $pdo->prepare($reserveSql);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':movie_id', $movieId, PDO::PARAM_INT);
    $stmt->bindParam(':seats', $seats, PDO::PARAM_INT);
    $stmt->execute();

    // You can add a success message or redirection here
    echo "Reservation successful!";
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sureflix - Movie Reservation</title>
    <style>
        /* Add your CSS styles here */
        * {
            text-decoration: none;
            box-sizing: border-box;
        }
        .navbar {
            background: crimson;
            font-family: calibri;
            padding-right: 15px;
            padding-left: 15px;
        }
        .navdiv {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo a {
            font-size: 35px;
            font-weight: 600;
            color: white;
        }
        li {
            list-style: none;
            display: inline-block;
        }
        li a {
            color: white;
            font-size: 18px;
            font-weight: bold;
            margin-right: 25px;
        }
        button {
            background-color: black;
            margin-left: 10px;
            border-radius: 10px;
            padding: 10px;
            width: 120px;
            text-align: center;
        }
        button a {
            color: white;
            font-weight: bold;
            font-size: 15px;
        }

        /* para sa modals*/
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            width: 300px;
            border-radius: 10px;
            text-align: center;
        }

        .modal-content input {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .modal-content button {
            width: 70%;
            padding: 10px;
            background-color: crimson;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            margin-top: 20px;
        }

        .modal-content button:hover {
            background-color: darkred;
        }

        .modal-content .close-btn {
            float: right;
            cursor: pointer;
            font-size: 20px;
            color: #aaa;
        }
        .modal-content .close-btn:hover {
            color: black;
        }

        .error-message {
            margin-top: 15px;
            font-size: 14px;
            font-weight: bold;
            color: red;
        }

        .success-message {
            margin-top: 15px;
            font-size: 14px;
            font-weight: bold;
            color: green;
        }

        /* movies */
.movie-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-top: 30px;
        }
        .movie-card {
    width: 200px; 
    margin: 20px;
    padding: 10px;
    background-color: #f0f0f0;
    border-radius: 10px;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    position: relative;
    text-align: center;
    overflow: hidden;
}

.movie-poster {
    width: 100%; 
    height: 300px; 
    object-fit: cover; 
    margin-bottom: 10px;
}

.movie-title {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 10px;
}

.movie-genre {
    font-size: 14px;
    color: #777;
}

.movie-description {
    font-size: 12px;
    color: #555;
}

.reserve-btn {
    background-color: crimson;
    color: white;
    border: none;
    border-radius: 5px;
    padding: 10px;
    cursor: pointer;
    font-size: 14px;
    margin-top: 10px;
}

.reserve-btn:hover {
    background-color: darkred;
}
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navdiv">
        <div class="logo"><a href="#">Sureflix</a></div>
        <ul>
            <li><a href="javascript:void(0)" onclick="openUpdatePopup()">Profile</a></li>
            <li><a href="#">About</a></li>
            <li><a href="#">Contact</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- logout button -->
                <button><a href="?logout=true">Logout</a></button>
            <?php else: ?>
                <!-- login kung walang acc -->
                <button onclick="openLoginPopup()"><a href="javascript:void(0)">Login</a></button>
            <?php endif; ?>
        </ul>
    </div>
</nav>



<!-- login modal -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeLoginPopup()">&times;</span>
        <h2>Login</h2>
        <form action="" method="POST">
            <input type="text" name="loginEmail" placeholder="Username or Email" required>
            <input type="password" name="loginPassword" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>

        <p>Don't have an account? <a href="javascript:void(0)" onclick="openSignupPopup()">Sign up here</a></p>

        <?php if (!empty($errorMessageLogin)): ?>
            <p class="error-message"><?php echo $errorMessageLogin; ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- sign up modal -->
<div id="signupModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeSignupPopup()">&times;</span>
        <h2>Sign Up</h2>
        <form action="" method="POST">
            <input type="text" name="signupUsername" placeholder="Username" required>
            <input type="email" name="signupEmail" placeholder="Email" required>
            <input type="password" name="signupPassword" placeholder="Password" required>
            <button type="submit">Sign Up</button>
        </form>

        <?php if (!empty($errorMessageSignup)): ?>
            <p class="error-message"><?php echo $errorMessageSignup; ?></p>
        <?php endif; ?>

        <?php if (!empty($successMessageSignup)): ?>
            <p class="success-message"><?php echo $successMessageSignup; ?></p>
        <?php endif; ?>
    </div>
</div>
<!-- update Modal-->
<div id="updateModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeUpdatePopup()">&times;</span>
        <h2>Update Your Profile</h2>
        <form action="" method="POST">
            <input type="text" name="updatedUsername" placeholder="New Username" required>
            <input type="email" name="updatedEmail" placeholder="New Email" required>
            <input type="password" name="updatedPassword" placeholder="New Password" required>
            <button type="submit">Update Profile</button>
        </form>
        <form action="" method="POST">
            <!-- Confirmation for Account Deletion -->
            <button type="submit" name="deleteAccount" onclick="return confirm('Are you sure you want to delete your account? This action cannot be undone.')">Delete Your Account</button>
        </form>
    </div>
</div>
    </div>
</div>

<!-- movie list -->
<div class="movie-list">
    <?php if (!empty($movies)): ?>
        <?php foreach ($movies as $movie): ?>
            <div class="movie-card">
    <img src="<?php echo htmlspecialchars($movie['poster']); ?>" alt="Movie Poster" class="movie-poster">
    <div class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></div>
    <div class="movie-genre"><?php echo htmlspecialchars($movie['genre']); ?></div>
    <div class="movie-description"><?php echo htmlspecialchars($movie['description']); ?></div>
    <button class="reserve-btn" onclick="openReserveModal(<?php echo $movie['id']; ?>, '<?php echo addslashes($movie['title']); ?>')">Reserve Seats</button>
</div>
            <!-- text display sa login-->
        <?php endforeach; ?>
        <?php else: ?>
            <div style="display: flex; 
                justify-content: center; 
                align-items: center; 
                height: 100vh; /* Ensure full viewport height */
                width: 100%; /* Ensure full width */
                background-color: #f9f9f9; 
                border-radius: 10px; 
                margin-top: 30px; 
                box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);">
        <p style="text-align: center; 
                  font-size: 36px; 
                  font-weight: bold; 
                  color: crimson; 
                  font-family: 'Arial', sans-serif; 
                  margin: 0;">
            WELCOME TO SUREFLIX!<br> BOOK YOUR TICKETS WITH US NOW!
        </p>
    </div>
<?php endif; ?>
</div>

<!-- reservation modal-->
<div id="reserveModal" class="modal">
    <div class="modal-content" style="width: 1000px; padding: 20px;">
        <span class="close-btn" onclick="closeReserveModal()">&times;</span>
        <h2>Reserve Seats</h2>
        <form action="" method="POST">
            <div id="movieDetails"></div>
            <label for="seats">Number of Seats:</label>
            <input type="number" name="seats" id="seats" min="1" required><br><br>
            <button type="submit">Confirm Reservation</button>
        </form>
    </div>
</div>

<script>
// pop up login
function openLoginPopup() {
    closeAllModals();  // Close all modals before opening the login modal
    document.getElementById('loginModal').style.display = "flex";
}

//close login modal
function closeLoginPopup() {
    document.getElementById('loginModal').style.display = 'none';
}

// pop up signup modal
function openSignupPopup() {
    closeAllModals();  // Close all modals before opening the signup modal
    document.getElementById('signupModal').style.display = "flex";
}

//close signup modal
function closeSignupPopup() {
    document.getElementById('signupModal').style.display = 'none';
}
// pop up reserve modal
function openReserveModal(movieId, movieTitle) {
    closeAllModals();  // Close all modals before opening the reserve modal
    const movieDetails = `
        <p><strong>Movie Title:</strong> ${movieTitle}</p>
        <p><strong>Movie ID:</strong> ${movieId}</p>
    `;
    document.getElementById('movieDetails').innerHTML = movieDetails;
    document.getElementById('reserveModal').style.display = "flex";
}
//close reserve modal
function closeReserveModal() {
    document.getElementById('reserveModal').style.display = 'none';
}

//close all modals
function closeAllModals() {
    document.getElementById('loginModal').style.display = 'none';
    document.getElementById('signupModal').style.display = 'none';
    document.getElementById('reserveModal').style.display = 'none';
    document.getElementById('updateModal').style.display='none';
}
// pop up update modal
function openUpdatePopup() {
    closeAllModals();  // Close all modals before opening the update modal
    document.getElementById('updateModal').style.display = "flex";
}

//lose the updatemodal
function closeUpdatePopup() {
    document.getElementById('updateModal').style.display = 'none';
}

//close all modals
window.onload = function() {
    closeAllModals();
};
</script>

</body>
</html>
