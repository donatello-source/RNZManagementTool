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
    <link rel="stylesheet" href="../../../css/profil.css">
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
                    <li><a href="wyplaty.php">Wyplaty</a></li>
                    <li><a href="firmy.php">Firmy</a></li>
                    <li><a href="ustawienia.php">Ustawienia</a></li>
                </ul>
            </nav>
        </aside>
        <main id="employee-profile">
            <!-- Tutaj będą wyświetlane dane pracownika -->
        </main>

        <script>
        // Funkcja do odczytywania parametrów z URL
        function getQueryParam(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param); // Zwróci wartość parametru 'id'
        }
        // Odczytanie id z URL
        const employeeId = getQueryParam('id');
        // Sprawdzenie, czy istnieje ID i pobranie danych
        if (employeeId) {
            fetchEmployeeData(employeeId);
        } else {
            console.error('Brak parametru ID w URL');
        }

        // Funkcja do pobrania danych pracownika z API
        function fetchEmployeeData(id) {
            fetch(`http://localhost/RNZManagementTool/php/get_employee.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Błąd:', data.error);
                    } else {
                        displayEmployeeProfile(data); // Wyświetl dane pracownika
                        console.log(data);
                    }
                })
                .catch(error => console.error('Błąd podczas ładowania danych:', error));
        }

        function getComplementaryColor(color) {
            console.log(color);
            // Tworzymy ukryty element do zamiany dowolnego formatu koloru na RGB
            const dummyDiv = document.createElement('div');
            dummyDiv.style.color = color; // Ustawienie koloru
            document.body.appendChild(dummyDiv);
            console.log(color);
            // Pobranie rzeczywistego koloru w formacie RGB
            const computedColor = window.getComputedStyle(dummyDiv).color; // Wynik w 'rgb(r, g, b)'
            document.body.removeChild(dummyDiv); // Usunięcie elementu po użyciu
            // Wyciągnięcie składowych RGB
            const rgbMatch = computedColor.match(/rgb\((\d+), (\d+), (\d+)\)/);
            if (!rgbMatch) {
                console.error('Nie można obliczyć koloru dla:', color);
                return '#000000'; // Domyślny kolor: czarny
            }

            const r = parseInt(rgbMatch[1]);
            const g = parseInt(rgbMatch[2]);
            const b = parseInt(rgbMatch[3]);

            // Obliczenie koloru dopełniającego
            const compR = 255 - r;
            const compG = 255 - g;
            const compB = 255 - b;

            // Konwersja na format RGB
            if (r + g + b == 0 && color != 'black' && color != '#000000') {
                return `rgb(0, 0, 0)`
            }
            return `rgb(${compR}, ${compG}, ${compB})`;
        }


        function displayEmployeeProfile(employee) {
            const profileContainer = document.getElementById('employee-profile');
            if (profileContainer) {
                // Obliczenie koloru przeciwnika
                const complementaryColor = getComplementaryColor(employee.kolor);

                profileContainer.innerphp = `
            <div class="employee-card">
                <div class="profil-name">
                    <label for="employee-name">Imię i nazwisko:</label>
                    <input type="text" id="employee-name" value="${employee.Imie} ${employee.Nazwisko}" readonly>
                </div>
                <div class="profil-phone">
                    <label for="employee-phone">Numer telefonu:</label>
                    <input type="text" id="employee-phone" value="${employee.NumerTelefonu}" readonly>
                </div>
                <div class="profil-mail">
                    <label for="employee-mail">Email:</label>
                    <input type="email" id="employee-mail" value="${employee.Email}" readonly>
                </div>
                <div class="profil-addres">
                    <label for="employee-address">Adres zamieszkania:</label>
                    <input type="text" id="employee-address" value="${employee.AdresZamieszkania}" readonly>
                </div>
                <div class="profil-state">
                    <label for="employee-position">Stanowisko:</label>
                    <input type="text" id="employee-position" value="${employee.Status}" readonly>
                </div>
            </div>

            <style>
                #employee-profile {
                    background-color: ${employee.kolor};
                }
                #employee-profile label {
                    color: ${complementaryColor};
                }
            </style>
        `;
            } else {
                console.error('Element #employee-profile nie został znaleziony.');
            }
        }
        </script>
    </div>

    <script src="../../../js/global.js">
    </script>
</body>

</html>