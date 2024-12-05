<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: /RNZManagementTool/public/views/index.php');
    exit();
}
$user = $_SESSION['user'];
?>

<!DOCTYPE php>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strona Główna</title>
    <link href='https://fonts.googleapis.com/css?family=Playfair Display' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/global.css">
    <link rel="stylesheet" href="../../../css/wyplaty.css">
</head>

<body>
    <header>
        <img width="512" height="512" src="https://robimynazywo.pl/wp-content/uploads/2023/07/cropped-Logo_1080.png"
            class="custom-logo" alt="ROBIMY NA ŻYWO">
        <div class="RNZ-Header-text">
            <a href="http://www.robimynazywo.pl">ROBIMY NA ŻYWO</a>
            <div>Nie ma problemów, są tylko wyzwania do rozwiązania</div>
        </div>
        <div class="profile-link">
            <a href="profile.php"><?= $user['first_name'] . ' ' . $user['last_name'] ?></a>
        </div>
        <div class="logout-button">
            <a href="/RNZManagementTool/security/logout">Wyloguj się</a>
        </div>
    </header>
    <div class="container">
        <aside class="sidebar">
            <button class="menu-toggle">☰</button>
            <nav>
                <ul>
                    <li><a href="main.php">Home</a></li>
                    <li><a href="pracownicy.php">Pracownicy</a></li>
                    <li><a href="wydarzenia.php">Wydarzenia</a></li>
                    <li><a href="firmy.php">Firmy</a></li>
                    <li><a href="ustawienia.php">Ustawienia</a></li>
                </ul>
            </nav>
        </aside>
        <main class="content">
            <div class="events">

            </div>
        </main>
    </div>

    <script>
    const menuToggle = document.querySelector(".menu-toggle");
    const sidebar = document.querySelector(".sidebar");

    menuToggle.addEventListener("click", () => {
        sidebar.classList.toggle("active");
    });
    </script>
</body>

</html>