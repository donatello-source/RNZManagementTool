<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: /RNZManagementTool/');
    exit();
}

$user = $_SESSION['user'];

if ($_SESSION['user']['status'] !== 'administrator' && $_SESSION['user']['status'] !== 'szef' ){
    header('Location: /public/views/pages/wydarzenia.php');
    exit();
}
?>
<!DOCTYPE php>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tworzenie Wydarzenia</title>
    <link href='https://fonts.googleapis.com/css?family=Playfair Display' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/global.css">
    <link rel="stylesheet" href="../../../css/tworzenie_wydarzenia.css">
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
                    <li><a href="main.php">Home</a></li>
                    <li><a href="wydarzenia.php">Wydarzenia</a></li>
                    <li><a href="pracownicy.php">Pracownicy</a></li>
                    <li><a href="firmy.php">Firmy</a></li>
                    <li><a href="stanowiska.php">Stanowiska</a></li>
                    <li><a href="czas_pracy.php">Czas Pracy</a></li>
                    <li><a href="wyplaty.php">Wypłaty</a></li>
                    <li><a href="podsumowanie.php">Podsumowanie</a></li>
                    <li><a href="ustawienia.php">Ustawienia</a></li>
                </ul>
            </nav>
        </aside>
        <main class="create-event-container">
            <h1>Utwórz nowe wydarzenie</h1>
            <form id="event-form">
                <div class="form-group">
                    <label for="nazwaWydarzenia">Nazwa Wydarzenia <span class="required">*</span></label>
                    <input type="text" id="nazwaWydarzenia" name="nazwaWydarzenia" placeholder="Podaj nazwe wydarzenia"
                        required>
                </div>

                <div class="form-group">
                    <label for="firma">Firma <span class="required">*</span></label>
                    <input type="text" id="firma" name="firma" placeholder="Wpisz nazwę firmy" list="firm-list"
                        required>
                    <datalist id="firm-list">
                    </datalist>
                </div>

                <div class="form-group">
                    <label for="miejsce">Miejsce <span class="required">*</span></label>
                    <input type="text" id="miejsce" name="miejsce" placeholder="Podaj miejsce wydarzenia" required>
                </div>

                <div class="form-group">
                    <label for="data-poczatek">Data Początek <span class="required">*</span></label>
                    <input type="date" id="data-poczatek" name="data-poczatek" required>
                </div>

                <div class="form-group" id="data-koniec-container">
                    <label for="data-koniec">Data Koniec</label>
                    <input type="date" id="data-koniec" name="data-koniec">
                </div>
                <div class="form-group" id="checkbox-form">
                    <label>
                        Wydarzenie jednodniowe<input type="checkbox" id="jednodniowe">
                    </label>
                </div>

                <div class="form-group" id="pracownik_dropdown">
                    <label for="pracownicy">Pracownicy</label>
                    <div id="pracownicy-container">
                        <span id="add-pracownik-btn" class="add-btn">+</span>
                    </div>
                    <div id="pracownicy-dropdown" class="hidden">
                    </div>
                </div>
                <button type="button" id="generate-table-btn">Uzupełnij dni</button>
                <div id="schedule-table-container">
                    <table id="schedule-table"></table>
                </div>

                <div class="form-group">
                    <label for="hotel">Hotel</label>
                    <input type="text" id="hotel" name="hotel" placeholder="Podaj nazwę hotelu">
                </div>
                <div class="form-group">
                    <label for="osoba-zarzadzajaca">Osoba zarządzająca</label>
                    <input type="text" id="osoba-zarzadzajaca" name="osoba-zarzadzajaca"
                        placeholder="Podaj imię i nazwisko">
                </div>

                <div class="form-group">
                    <label for="komentarz">Komentarz</label>
                    <textarea id="komentarz" name="komentarz" placeholder="Dodaj komentarz..."></textarea>
                </div>
                <?php if ($user['status'] === 'szef' || $user['status'] === 'administrator'): ?>
                <div class="form-group">
                    <label for="dodatkowe-koszta">Dodatkowe koszta</label>
                    <input type="number" id="dodatkowe-koszta" name="dodatkowe-koszta">
                </div>
                <?php endif; ?>
                <button type="submit" id="submit-btn">Utwórz wydarzenie</button>
            </form>
        </main>

        <script src="../../../js/tworzenie_wydarzenia.js"></script>
        <script src="../../../js/global.js"></script>
</body>

</html>