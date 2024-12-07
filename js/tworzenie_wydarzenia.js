document.addEventListener('DOMContentLoaded', () => {
    const nazwaWydarzenia = document.getElementById('nazwaWydarzenia');
    const datePoczatek = document.getElementById('data-poczatek');

    const dateKoniec = document.getElementById('data-koniec');
    const jednodnioweCheckbox = document.getElementById('jednodniowe');
    const firmList = document.getElementById('firm-list');
    const pracownicyContainer = document.getElementById('pracownicy-container');
    const pracownicyDropdown = document.getElementById('pracownicy-dropdown');
    const addPracownikBtn = document.getElementById('add-pracownik-btn');
    const selectedDays = {};

    let pracownicyList = [];
    let selectedPracownicy = [];


    datePoczatek.addEventListener("change", () => generateTable(selectedPracownicy));
    dateKoniec.addEventListener("change", () => generateTable(selectedPracownicy));

    // Ustaw dzisiejszą datę jako minimalną
    const today = new Date().toISOString().split('T')[0];
    datePoczatek.min = today;

    // Obsługa checkboxa "Wydarzenie jednodniowe"
    jednodnioweCheckbox.addEventListener('change', () => {
        if (jednodnioweCheckbox.checked) {
            dateKoniec.value = '';
            dateKoniec.disabled = true;
        } else {
            dateKoniec.disabled = false;
        }
    });

    // Pobierz firmy z bazy
    async function fetchFirms() {
        try {
            const response = await fetch("http://localhost/RNZManagementTool/php/get_firms.php");
            const firms = await response.json();
            firms.forEach(firm => {
                const option = document.createElement('option');
                option.value = firm.NazwaFirmy;
                firmList.appendChild(option);
            });
        } catch (error) {
            console.error('Błąd podczas ładowania firm:', error);
        }
    }

    async function fetchPracownicy() {
        try {
            const response = await fetch('http://localhost/RNZManagementTool/php/get_pracownicy.php');
            const pracownicy = await response.json();
            pracownicyList = pracownicy;
            pracownicyDropdown.innerHTML = '';
            pracownicy.forEach(pracownik => {
                const pracownikDiv = document.createElement('div');
                pracownikDiv.textContent = `${pracownik.Imie} ${pracownik.Nazwisko}`;
                pracownikDiv.style.backgroundColor = pracownik.kolor; // Ustaw kolor tła
                pracownikDiv.style.color = getComplementaryColor(pracownik.kolor); // Ustaw kolor tła
                pracownikDiv.dataset.id = pracownik.IdOsoba;

                pracownikDiv.addEventListener('click', () => {
                    addPracownikToList(pracownik);
                });

                pracownicyDropdown.appendChild(pracownikDiv);
            });
        } catch (error) {
            console.error('Błąd podczas ładowania pracowników:', error);
        }
    }
    function getComplementaryColor(color) {
        // Tworzymy ukryty element do zamiany dowolnego formatu koloru na RGB
        const dummyDiv = document.createElement('div');
        dummyDiv.style.color = color; // Ustawienie koloru
        document.body.appendChild(dummyDiv);
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

        const compR = 255 - r;
        const compG = 255 - g;
        const compB = 255 - b;

        if (r + g + b == 0 && color != 'black' && color != '#000000') {
            return `rgb(0, 0, 0)`
        }
        return `rgb(${compR}, ${compG}, ${compB})`;
    }

    // Funkcja dodająca pracownika do listy
    function addPracownikToList(pracownik) {
        if (selectedPracownicy.some(p => p.IdOsoba === pracownik.IdOsoba)) {
            alert('Ten pracownik został już dodany!');

            return;
        }

        selectedPracownicy.push(pracownik);
        const pracownikItem = document.createElement('div');
        pracownikItem.classList.add('pracownik-item');
        pracownikItem.style.backgroundColor = pracownik.kolor;
        pracownikItem.textContent = `${pracownik.Imie} ${pracownik.Nazwisko}`;
        pracownikItem.dataset.id = pracownik.IdOsoba;

        const removeBtn = document.createElement('span');
        removeBtn.textContent = 'x';
        removeBtn.addEventListener('click', () => {
            pracownicyContainer.removeChild(pracownikItem);
            selectedPracownicy = selectedPracownicy.filter(p => p.IdOsoba !== pracownik.IdOsoba);
            generateTable(selectedPracownicy);
        });
        pracownikItem.style.color = getComplementaryColor(pracownik.kolor);
        removeBtn.style.color = getComplementaryColor(pracownik.kolor);
        pracownikItem.appendChild(removeBtn);


        pracownicyContainer.insertBefore(pracownikItem, addPracownikBtn);

        pracownicyDropdown.classList.remove('active'); // Schowaj dropdown
        generateTable(selectedPracownicy);
    }
    function updateDropdownPosition() {
        const rect = addPracownikBtn.getBoundingClientRect(); // Pobieramy pozycję przycisku "+" w oknie
        pracownicyDropdown.style.top = `${rect.bottom + window.scrollY + 5}px`; // Ustawiamy dropdown tuż pod przyciskiem
        pracownicyDropdown.style.left = `${rect.left + window.scrollX}px`; // Ustawiamy lewą krawędź dropdowna
    }

    // Obsługa kliknięcia "+" przycisku
    addPracownikBtn.addEventListener("click", () => {
        pracownicyDropdown.classList.toggle("active");
        if (pracownicyDropdown.classList.contains("active")) {
            updateDropdownPosition(); // Aktualizujemy pozycję dropdowna, gdy się pojawi
        }
    });


    // Ukryj dropdown, jeśli kliknięto poza nim
    document.addEventListener('click', (event) => {
        if (!pracownicyContainer.contains(event.target) && !pracownicyDropdown.contains(event.target)) {
            pracownicyDropdown.classList.remove('active');
        }
    });



    fetchFirms();
    fetchPracownicy();

    document.getElementById('event-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(event.target);
        const eventData = Object.fromEntries(formData.entries());
        eventData.pracownicy = selectedPracownicy.map(pracownik => pracownik.IdOsoba);
        eventData.dni = {};
        Object.keys(selectedDays).forEach(pracownikId => {
            eventData.dni[pracownikId] = selectedDays[pracownikId]; // Przypisz dni z selectedDays
        });
        try {
            const response = await fetch('http://localhost/RNZManagementTool/php/add_event.php', {
                method: 'POST',
                body: JSON.stringify(eventData),
                headers: {
                    'Content-Type': 'application/json'
                }
            });


            const result = await response.json();
            alert(result.message || 'Wydarzenie zostało utworzone!');
        } catch (error) {
            console.error('Błąd podczas tworzenia wydarzenia:', error);
        }
    });

    function generateTable(pracownicy) {
        const startDateInput = document.getElementById("data-poczatek").value;
        const endDateInput = document.getElementById("data-koniec").value;
        const pracownicyContainer = document.getElementById("pracownicy-container");
        const tableContainer = document.getElementById("schedule-table-container");
        const table = document.getElementById("schedule-table");

        // Weryfikacja poprawności danych
        if (!startDateInput || !endDateInput) {
            return;
        }
        const startDate = new Date(startDateInput);
        const endDate = new Date(endDateInput);
        if (startDate > endDate) {
            return;
        }
        // Pobranie wybranych pracowników
        if (selectedPracownicy.length === 0) {
            return;
        }

        // Czyszczenie tabeli
        table.innerHTML = "";

        // Nagłówek tabeli
        const headerRow = document.createElement("tr");
        headerRow.innerHTML = `<th>Pracownik</th>`;

        const dates = [];
        for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
            const dayHeader = document.createElement("th");
            const date = d.toISOString().split("T")[0]; // Data w formacie YYYY-MM-DD
            dates.push(date);
            dayHeader.textContent = date;
            headerRow.appendChild(dayHeader);
        }
        table.appendChild(headerRow);

        // Tworzenie wierszy dla pracowników
        selectedPracownicy.forEach(pracownik => {
            const row = document.createElement("tr");

            // Imię i nazwisko pracownika
            const nameCell = document.createElement("td");
            nameCell.textContent = `${pracownik.Imie} ${pracownik.Nazwisko}`;
            nameCell.style.backgroundColor = pracownik.kolor; // Kolor wiersza
            nameCell.style.color = getComplementaryColor(pracownik.kolor);
            row.appendChild(nameCell);

            // Inicjalizujemy dane pracownika
            selectedDays[pracownik.IdOsoba] = [];

            // Generowanie kolumn dla dni
            dates.forEach(date => {
                const cell = document.createElement("td");
                const cellDiv = document.createElement("div");

                // Styl i klikalność komórek
                cellDiv.classList.add("clickable-cell");
                cellDiv.dataset.selected = "false"; // Stan zaznaczenia
                cellDiv.dataset.date = date;
                cellDiv.dataset.pracownikId = pracownik.IdOsoba;

                // Dodajemy eventy
                cellDiv.addEventListener("click", toggleCell);
                cellDiv.addEventListener("mouseover", handleMouseOver);

                cell.appendChild(cellDiv);
                row.appendChild(cell);
            });

            table.appendChild(row);
        });

        // Zmienna do obsługi przeciągania
        let isMouseDown = false;

        // Obsługa klikalnych komórek
        function toggleCell(event) {
            const cell = event.target;
            const pracownikId = cell.dataset.pracownikId;
            const date = cell.dataset.date;

            const isSelected = cell.dataset.selected === "true";
            cell.dataset.selected = isSelected ? "false" : "true";
            cell.classList.toggle("selected", !isSelected);

            // Aktualizujemy dane w selectedDays
            if (!isSelected) {
                if (!selectedDays[pracownikId].includes(date)) {
                    selectedDays[pracownikId].push(date);
                }
            } else {
                const index = selectedDays[pracownikId].indexOf(date);
                if (index > -1) {
                    selectedDays[pracownikId].splice(index, 1);
                }
            }

            console.log(selectedDays); // Debugowanie
        }

        // Obsługa przeciągania
        function handleMouseOver(event) {
            if (isMouseDown) {
                const cell = event.target;
                const { pracownikId, date } = cell.dataset;

                cell.dataset.selected = "true";
                cell.classList.add("selected");

                // Dodajemy do selectedDays, jeśli nie istnieje
                if (!selectedDays[pracownikId].includes(date)) {
                    selectedDays[pracownikId].push(date);
                }
            }
        }
        // Obsługa zdarzeń myszy
        table.addEventListener("mousedown", () => (isMouseDown = true));
        document.addEventListener("mouseup", () => (isMouseDown = false));

        // Pokazanie tabeli
        tableContainer.style.display = "block";
    };


});

