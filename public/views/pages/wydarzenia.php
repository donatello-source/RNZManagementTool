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
    <title>Wydarzenia</title>
    <link href='https://fonts.googleapis.com/css?family=Playfair Display' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/global.css">
    <link rel="stylesheet" href="../../../css/wydarzenia.css">
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
        <aside class="sidebar">
            <button class="menu-toggle">☰</button>
            <nav>
                <ul>
                    <?php if ($_SESSION['user']['status'] === 'administrator' || $_SESSION['user']['status'] === 'szef' ): ?>
                    <li><a href="main.php">Home</a></li>
                    <li><a href="wydarzenia.php" class="selected">Wydarzenia</a></li>
                    <li><a href="pracownicy.php">Pracownicy</a></li>
                    <li><a href="firmy.php">Firmy</a></li>
                    <li><a href="stanowiska.php">Stanowiska</a></li>
                    <li><a href="czas_pracy.php">Czas Pracy</a></li>
                    <li><a href="wyplaty.php">Wypłaty</a></li>
                    <li><a href="podsumowanie.php">Podsumowanie</a></li>
                    <li><a href="ustawienia.php">Ustawienia</a></li>
                    <?php else: ?>
                    <li><a href="main.php">Home</a></li>
                    <li><a href="wydarzenia.php" class="selected">Wydarzenia</a></li>
                    <li><a href="pracownicy.php">Pracownicy</a></li>
                    <li><a href="czas_pracy.php">Czas Pracy</a></li>
                    <li><a href="wyplaty.php">Wypłaty</a></li>
                    <li><a href="ustawienia.php">Ustawienia</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>
        <main class="content">
            <?php if ($user['status'] === 'szef' || $user['status'] === 'administrator'): ?>
            <div class="create_event" onclick="location.href='../pages/tworzenie_wydarzenia.php'">+</div>
            <?php endif; ?>
            <div class="search-bar">
                <input type="text" id="search-input" placeholder="Wyszukaj wydarzenie..." />
                <button id="filter-button"><img src="https://cdn-icons-png.flaticon.com/512/107/107799.png"
                        alt="Filters Icon"></button>
            </div>
            <div id="filters-panel" class="filters-panel hidden">
                <div class="filters-content">
                    <h2>Filtry</h2>
                    <div class="filter-group">
                        <label>Miejsce:</label>
                        <input type="text" id="filter-place-input" placeholder="Dodaj miejsce" />
                        <button id="add-place-button">Dodaj</button>
                        <ul id="filter-places-list"></ul>
                    </div>
                    <div class="filter-group">
                        <label>Firma:</label>
                        <input type="text" id="filter-company-input" list="companies-datalist"
                            placeholder="Dodaj firmę" />
                        <datalist id="companies-datalist"></datalist>
                        <button id="add-company-button">Dodaj</button>
                        <ul id="filter-companies-list"></ul>
                    </div>
                    <div class="filter-group">
                        <label>Data:</label>
                        <input type="month" id="filter-date-start" />
                        <input type="month" id="filter-date-end" />
                    </div>
                    <div class="filter-group">
                        <label>Pracownicy:</label>
                        <input type="text" id="filter-employee-input" list="employees-datalist"
                            placeholder="Dodaj pracownika" />
                        <datalist id="employees-datalist"></datalist>
                        <button id="add-employee-button">Dodaj</button>
                        <ul id="filter-employees-list"></ul>
                        <label>
                            <input type="radio" name="employee-mode" value="all" checked /> Wszyscy
                        </label>
                        <label>
                            <input type="radio" name="employee-mode" value="any" /> Którykolwiek
                        </label>
                    </div>
                    <button id="apply-filters-button">Zastosuj filtry</button>
                    <button id="clear-filters-button">Wyczyść filtry</button>
                </div>
            </div>
            <div id="events-container" class="events"></div>
        </main>
    </div>
    <script src="../../../js/wydarzenia.js"></script>
    <script src="../../../js/global.js"></script>
</body>

</html>