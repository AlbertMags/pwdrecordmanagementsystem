<?php 
include("../db_connect.php"); 

// Fetch Dynamic Stats
$total_services = 0;
$total_announcements = 0;
$total_news = 0;

if(isset($conn)){
    $total_services = $conn->query("SELECT id FROM services")->num_rows;
    $total_announcements = $conn->query("SELECT id FROM announcements")->num_rows;
    $total_news = $conn->query("SELECT id FROM news")->num_rows;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWD Community Portal | Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
    <div class="logo-area">
        <img src="../uploads/mswdo.jpg" alt="MSWDO Logo" class="logo-img">
        
         <div>
            <h2>PWD Community Portal</h2>
            <p>EB Magalona, Negros Occidental</p>
        </div>


    </div>

    <div class="menu-toggle" id="mobile-menu" onclick="toggleMenu()">
        <i class="fas fa-bars"></i>
    </div>

    <ul id="nav-links">
        <li><a href="#home-sec" class="nav-link">Home</a></li>
        <li><a href="#announcements" class="nav-link">Announcements</a></li>
        <li><a href="#services" class="nav-link">Services</a></li>
        <li><a href="#news" class="nav-link">News</a></li>
        <li><a href="#contact" class="nav-link">Contact</a></li>
        <li><a href="../login.php" class="login-nav-btn"><i class="fas fa-sign-in-alt"></i> Login</a></li>
    </ul>
</nav>

<section id="home-sec">
    <div class="hero">
        <div class="hero-text">
            <h1>PWD Community Portal in E. B. Magalona</h1>
        <p> Angat Saraviahanon Your dedicated gateway to PWD benefits, news, and support from the MSWDO and the Municipality—where no one is left behind.</p>
            <div class="btn-group">
                <a href="#services" class="btn btn-blue"><i class="fas fa-hand-holding-heart"></i> Explore Services</a>
            </div>
        </div>
        <div class="hero-img-area">
            <img src="../uploads/mam.jpg" alt="PWD Community" class="hero-mam-img">
        </div>
    </div>

    <div class="stats-row">
        <div class="stat-box"><h3><?php echo $total_services; ?></h3><p>Services</p></div>
        <div class="stat-box"><h3><?php echo $total_announcements; ?></h3><p>Announcements</p></div>
        <div class="stat-box"><h3><?php echo $total_news; ?></h3><p>Activities</p></div>
    </div>
</section>

<?php include 'announcements.php'; ?>
<?php include 'services.php'; ?>
<?php include 'news.php'; ?>
<?php include 'contact.php'; ?>

<footer class="main-footer">
    <div class="footer-centered-content">
        <div class="footer-logo">
            <i class="fas fa-wheelchair"></i>
            <span>PWD PORTAL</span>
        </div>
        <p class="footer-desc">Dedicated to providing accessible services for EB Magalona community members.</p>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2026 EB Magalona PWD Community Portal. All rights reserved.</p>
    </div>
</footer>

<script>
    function toggleMenu() {
        const navLinks = document.getElementById("nav-links");
        navLinks.classList.toggle("show");
    }

    const sections = document.querySelectorAll("section[id]");
    const navLinksList = document.querySelectorAll(".nav-link");

    function scrollSpy() {
        let current = "";
        
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 10) {
            current = "contact";
        } else {
            sections.forEach((section) => {
                const sectionTop = section.offsetTop;
                if (window.pageYOffset >= sectionTop - 200) {
                    current = section.getAttribute("id");
                }
            });
        }

        navLinksList.forEach((link) => {
            link.classList.remove("active");
            if (current !== "" && link.getAttribute("href").includes(current)) {
                link.classList.add("active");
            }
        });
    }

    window.addEventListener("scroll", scrollSpy);
    window.addEventListener("load", scrollSpy);

    document.querySelectorAll('#nav-links a').forEach(link => {
        link.addEventListener('click', () => {
            document.getElementById("nav-links").classList.remove("show");
        });
    });
</script>
</body>
</html>