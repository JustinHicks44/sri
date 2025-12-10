<?php
require_once __DIR__ . '/Php_sri/Lib/auth.php';
$user = auth_current_user();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>MyTicket - Home</title>
        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
        <!-- Core theme CSS -->
        <link href="css/styles.css" rel="stylesheet" />
    </head>
    <body>
        <?php include __DIR__ . '/Php_sri/navbar.php'; ?>
        <!-- Page Content-->
        <div class="container px-4 px-lg-5">
            <!-- Heading Row-->
            <div class="row gx-4 gx-lg-5 align-items-center my-5">
                <div class="col-lg-7 d-flex align-items-center justify-content-center">
                    <img class="img-fluid rounded mb-4 mb-lg-0" src="ball-racket-are-grass-with-balls-ball_979520-151391.jpg" alt="Hero" style="max-height:420px; width:auto; object-fit:cover;" />
                </div>
                <div class="col-lg-5 d-flex flex-column justify-content-center text-center">
                    <h1 class="font-weight-light">Welcome to MyTicket</h1>
                    <p>We are MyTicket, a frontrunner in the ticket sale/event administration business! Whether you are a concert goer looking for your next show or an event runner hoping to sell as many tickets as you can, you're in the right place!</p>
                    <a class="btn btn-primary mx-auto" href="/sri/Admin/accountsignup.php">Sign Up!</a>
                </div>
            </div>
            <!-- Call to Action-->
            <div class="card text-white bg-secondary my-5 py-4 text-center">
                <div class="card-body"><p class="text-white m-0">Your one-stop shop for all things events: sports, concerts, fairs, public events, whatever you can think of!</p></div>
            </div>
            <!-- Content Row-->
            <div class="row gx-4 gx-lg-5">
                <div class="col-md-4 mb-5">
                    <div class="card h-100">
                        <div class="card-body">
                            <h2 class="card-title">Sign Up</h2>
                            <p class="card-text">Advertise your event or find your next concert!</p>
                        </div>
                        <div class="card-footer"><a class="btn btn-primary btn-sm" href="/sri/Admin/accountsignup.php">Sign up here!</a></div>
                    </div>
                </div>
                <div class="col-md-4 mb-5">
                    <div class="card h-100">
                        <div class="card-body">
                            <h2 class="card-title">Sign In</h2>
                            <p class="card-text">Sign in to your account to view your tickets and manage your account.</p>
                        </div>
                        <div class="card-footer"><a class="btn btn-primary btn-sm" href="/sri/Admin/login.php">Sign in!</a></div>
                    </div>
                </div>
                <div class="col-md-4 mb-5">
                    <div class="card h-100">
                        <div class="card-body">
                            <h2 class="card-title">Contact Us</h2>
                            <p class="card-text">Contact us with any questions or if you need any help!</p>
                        </div>
                        <div class="card-footer"><a class="btn btn-primary btn-sm" href="contact.php">Contact us!</a></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Footer-->
        <footer class="py-5 bg-dark">
            <div class="container px-4 px-lg-5"><p class="m-0 text-center text-white">Copyright &copy; MyTicket 2025</p></div>
        </footer>
        <!-- Bootstrap core JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Core theme JS-->
        <script src="js/scripts.js"></script>
    </body>
</html>
