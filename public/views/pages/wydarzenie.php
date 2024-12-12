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
    <title>Wydarzenie</title>
    <link href='https://fonts.googleapis.com/css?family=Playfair Display' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/global.css">
    <link rel="stylesheet" href="../../../css/wydarzenie.css">
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
                    <li><a href="wydarzenia.php">Wydarzenia</a></li>
                    <li><a href="pracownicy.php">Pracownicy</a></li>
                    <li><a href="firmy.php">Firmy</a></li>
                    <li><a href="stanowiska.php">Stanowiska</a></li>
                    <li><a href="czas_pracy.php">Czas Pracy</a></li>
                    <li><a href="wyplaty.php">Wypłaty</a></li>
                    <li><a href="ustawienia.php">Ustawienia</a></li>
                    <?php else: ?>
                    <li><a href="main.php">Home</a></li>
                    <li><a href="wydarzenia.php">Wydarzenia</a></li>
                    <li><a href="pracownicy.php">Pracownicy</a></li>
                    <li><a href="czas_pracy.php">Czas Pracy</a></li>
                    <li><a href="wyplaty.php">Wypłaty</a></li>
                    <li><a href="ustawienia.php">Ustawienia</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>
        <main class="event-container">
            <h1>Szczegóły wydarzenia</h1>
            <form id="event-form">
                <div class="form-group">
                    <label for="nazwaWydarzenia">Nazwa</label>
                    <input type="text" id="nazwaWydarzenia" name="nazwaWydarzenia" disabled>
                </div>
                <div class="form-group">
                    <label for="firma">Firma</label>
                    <input type="text" id="firma" name="firma" list="firm-list" disabled>
                    <datalist id="firm-list">
                    </datalist>
                </div>

                <div class="form-group">
                    <label for="miejsce">Miejsce</label>
                    <input type="text" id="miejsce" name="miejsce" disabled>
                </div>

                <div class="form-group">
                    <label for="data-poczatek">Data Początek</label>
                    <input type="date" id="data-poczatek" name="data-poczatek" disabled>
                </div>

                <div class="form-group" id="data-koniec-container">
                    <label for="data-koniec">Data Koniec</label>
                    <input type="date" id="data-koniec" name="data-koniec" disabled>
                </div>
                <div class="form-group">
                    <label for="pracownicy">Pracownicy</label>
                    <div id="pracownicy-container">
                        <span id="add-pracownik-btn" class="add-btn">+</span>
                        <div id="pracownicy-dropdown" class="hidden"></div>
                    </div>
                </div>
                <div id="schedule-table-container">
                    <table id="schedule-table"></table>
                </div>


                <div class="form-group">
                    <label for="hotel">Hotel</label>
                    <input type="text" id="hotel" name="hotel" disabled>
                </div>

                <div class="form-group">
                    <label for="osoba-zarzadzajaca">Osoba zarządzająca</label>
                    <input type="text" id="osoba-zarzadzajaca" name="osoba-zarzadzajaca" disabled>
                </div>

                <div class="form-group">
                    <label for="komentarz">Komentarz</label>
                    <textarea id="komentarz" name="komentarz" disabled></textarea>
                </div>

                <button type="button" id="edit-event-btn">Edytuj wydarzenie</button>
            </form>
        </main>

    </div>
    <script src="../../../js/wydarzenie.js"></script>
    <script src="../../../js/global.js"></script>
</body>

</html>