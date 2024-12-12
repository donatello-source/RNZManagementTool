<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: /RNZManagementTool/');
    exit();
}

$user = $_SESSION['user'];

if ($_SESSION['user']['status'] !== 'administrator' && $_SESSION['user']['status'] !== 'szef' ){
    header('Location: /RNZManagementTool/public/views/main.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strona Główna</title>
    <link href='https://fonts.googleapis.com/css?family=Playfair Display' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/global.css">
    <link rel="stylesheet" href="../../../css/stanowiska.css">
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
                    <li><a href="main.php">Home</a></li>
                    <li><a href="wydarzenia.php">Wydarzenia</a></li>
                    <li><a href="pracownicy.php">Pracownicy</a></li>
                    <li><a href="firmy.php">Firmy</a></li>
                    <li><a href="stanowiska.php" class="selected">Stanowiska</a></li>
                    <li><a href="czas_pracy.php">Czas Pracy</a></li>
                    <li><a href="wyplaty.php">Wypłaty</a></li>
                    <li><a href="ustawienia.php">Ustawienia</a></li>
                </ul>
            </nav>
        </aside>
        <main id="position-container">
            <h2>Pracownicy i Stanowiska</h2>
            <table id="positions-table">
                <thead>
                    <tr>
                        <th>Pracownik</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
            <button id="save-positions">Zapisz zmiany</button>
        </main>


    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const positionContainer = document.getElementById("position-container");
        const table = document.getElementById("positions-table");
        const saveButton = document.getElementById("save-positions");

        let data = {};

        fetch("http://localhost/RNZManagementTool/php/get_update_position.php")
            .then(response => response.json())
            .then(responseData => {
                data = responseData;
                renderTable(data);
            });

        function renderTable(data) {
            const {
                pracownicy,
                stanowiska,
                powiazania
            } = data;
            const thead = table.querySelector("thead tr");
            const tbody = table.querySelector("tbody");

            stanowiska.forEach(stanowisko => {
                const th = document.createElement("th");
                th.textContent = stanowisko.NazwaStanowiska;
                thead.appendChild(th);
            });

            pracownicy.forEach(pracownik => {
                const row = document.createElement("tr");
                const nameCell = document.createElement("td");
                const link = document.createElement("a");
                link.href =
                    `profil.php?id=${pracownik.IdOsoba}`;
                link.textContent = `${pracownik.Imie} ${pracownik.Nazwisko}`;
                link.style.textDecoration = "none";
                link.style.color = "inherit";
                nameCell.appendChild(link);
                row.appendChild(nameCell);

                stanowiska.forEach(stanowisko => {
                    const cell = document.createElement("td");
                    cell.classList.add("clickable");
                    cell.dataset.idOsoba = pracownik.IdOsoba;
                    cell.dataset.idStanowiska = stanowisko.IdStanowiska;

                    const isAssigned = powiazania.some(
                        p => p.IdOsoba === pracownik.IdOsoba && p.IdStanowiska ===
                        stanowisko.IdStanowiska
                    );

                    if (isAssigned) {
                        cell.classList.add("selected");
                    }

                    cell.addEventListener("click", () => {
                        cell.classList.toggle("selected");
                    });

                    row.appendChild(cell);
                });

                tbody.appendChild(row);
            });
        }

        saveButton.addEventListener("click", () => {
            const updatedPowiazania = [];
            const removedPowiazania = [];
            const cells = table.querySelectorAll("td.clickable");

            cells.forEach(cell => {
                const powiazanie = {
                    IdOsoba: cell.dataset.idOsoba,
                    IdStanowiska: cell.dataset.idStanowiska,
                };

                if (cell.classList.contains("selected")) {
                    updatedPowiazania.push(powiazanie);
                } else {
                    removedPowiazania.push(powiazanie);
                }
            });

            fetch("http://localhost/RNZManagementTool/php/get_update_position.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        powiazania: updatedPowiazania,
                        usunPowiazania: removedPowiazania,
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    //console.log(data.details);
                })
                .catch(error => {
                    console.error('Błąd podczas zapisywania zmian:', error);
                });
        });


    });
    </script>

    <script src="../../../js/global.js">
    </script>
</body>

</html>