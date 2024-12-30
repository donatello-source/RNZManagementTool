<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: /RNZManagementTool/');
    exit();
}
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wypłaty</title>
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
            <a href="ustawienia.php"><?= $user['first_name'] . ' ' . $user['last_name'] ?></a>
            <div id="userStatus" hidden><?= $user['status'] ?></div>
        </div>
        <form class="logout" action="/RNZManagementTool/logout" method="POST">
            <button class="logoutBtn" type="submit">Wyloguj się</button>
        </form>
    </header>
    <div class="container">
        <button class="menu-toggle">☰</button>
        <aside class="sidebar">
            <nav>
                <ul>
                    <?php if ($_SESSION['user']['status'] === 'administrator' || $_SESSION['user']['status'] === 'szef' ): ?>
                    <li><a href="main.php">Home</a></li>
                    <li><a href="wydarzenia.php">Wydarzenia</a></li>
                    <li><a href="pracownicy.php">Pracownicy</a></li>
                    <li><a href="firmy.php">Firmy</a></li>
                    <li><a href="stanowiska.php">Stanowiska</a></li>
                    <li><a href="czas_pracy.php">Czas Pracy</a></li>
                    <li><a href="wyplaty.php" class="selected">Wypłaty</a></li>
                    <li><a href="podsumowanie.php">Podsumowanie</a></li>
                    <li><a href="ustawienia.php">Ustawienia</a></li>
                    <?php else: ?>
                    <li><a href="main.php">Home</a></li>
                    <li><a href="wydarzenia.php">Wydarzenia</a></li>
                    <li><a href="pracownicy.php">Pracownicy</a></li>
                    <li><a href="czas_pracy.php">Czas Pracy</a></li>
                    <li><a href="wyplaty.php" class="selected">Wypłaty</a></li>
                    <li><a href="ustawienia.php">Ustawienia</a></li>
                    <?php endif; ?>

                </ul>
            </nav>
        </aside>
        <main class="content">
            <div id="filter-container">
                <form id="month-filter">
                    <label for="month">Wybierz miesiąc:</label>
                    <input type="month" id="month" name="month" value="<?= date('Y-m') ?>">
                    <button type="submit">Pokaż</button>
                </form>
            </div>

            <div id="payout-container">
                <div class="no-payouts">Brak danych o wypłatach za wybrany miesiąc.</div>
            </div>
        </main>
    </div>
    <script src="../../../js/wyplaty.js"></script>
    <script src="../../../js/global.js"></script>
</body>

</html>