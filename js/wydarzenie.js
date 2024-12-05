let dataForAll = {};
let selectedPracownicy = [];
const selectedDays = {};
let checker = 0;
function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}
const eventId = getQueryParam('id');

function generateTable(pracownicy) {

    const startDateInput = document.getElementById("data-poczatek").value;
    const endDateInput = document.getElementById("data-koniec").value;
    const table = document.getElementById("schedule-table");
    const tableContainer = document.getElementById("schedule-table-container");
    table.innerHTML = "";

    const startDate = new Date(startDateInput);
    const endDate = new Date(endDateInput);

    // Nagłówek tabeli
    const headerRow = document.createElement("tr");
    headerRow.innerHTML = `<th>Pracownik</th>`;
    const dates = [];
    for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
        const dayHeader = document.createElement("th");
        const date = d.toISOString().split("T")[0];
        dates.push(date);
        dayHeader.textContent = date;
        headerRow.appendChild(dayHeader);
    }
    table.appendChild(headerRow);

    // Tworzenie wierszy dla pracowników
    const groupedByPracownik = pracownicy.reduce((acc, pracownik) => {
        if (!acc[pracownik.IdOsoba]) acc[pracownik.IdOsoba] = [];
        acc[pracownik.IdOsoba].push(pracownik.Dzien);
        return acc;
    }, {});
    selectedPracownicy.forEach(pracownik => {
        const row = document.createElement("tr");

        const nameCell = document.createElement("td");
        nameCell.textContent = `${pracownik.Imie} ${pracownik.Nazwisko}`;
        nameCell.style.backgroundColor = pracownik.kolor;
        nameCell.style.color = getComplementaryColor(pracownik.kolor);
        row.appendChild(nameCell);
        selectedDays[pracownik.IdOsoba] = [];
        dates.forEach(date => {
            const cell = document.createElement("td");
            const cellDiv = document.createElement("div");

            cellDiv.classList.add("clickable-cell");
            cellDiv.dataset.date = date;
            cellDiv.dataset.pracownikId = pracownik.IdOsoba;

            cellDiv.addEventListener("click", toggleCell);
            cellDiv.addEventListener("mouseover", handleMouseOver);

            // Zamalowanie komórek na podstawie dni
            if (groupedByPracownik[pracownik.IdOsoba]?.includes(date)) {
                selectedDays[pracownik.IdOsoba].push(date);
                cellDiv.dataset.selected = "true";
                cellDiv.classList.add("selected");
            } else {
                cellDiv.dataset.selected = "false";
            }

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
        const { pracownikId, date } = cell.dataset;

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
        console.log(dataForAll.ListaPracownikow);
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

    tableContainer.style.display = "block";
    if (checker == 0) {
        table.classList.add("disabled");
    }
    checker = 1;


}


function getComplementaryColor(color) {
    const dummyDiv = document.createElement('div');
    dummyDiv.style.color = color;
    document.body.appendChild(dummyDiv);
    const computedColor = window.getComputedStyle(dummyDiv).color;
    document.body.removeChild(dummyDiv);
    const rgbMatch = computedColor.match(/rgb\((\d+), (\d+), (\d+)\)/);
    if (!rgbMatch) {
        console.error('Nie można obliczyć koloru dla:', color);
        return '#000000';
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

document.addEventListener("DOMContentLoaded", () => {
    const firmList = document.getElementById('firm-list');
    const pracownicyDropdown = document.createElement("div");
    const addPracownikBtn = document.createElement("span");
    const pracownicyContainer = document.getElementById("pracownicy-container");
    const selectedDays = {};
    let pracownicyList = [];

    pracownicyDropdown.id = "pracownicy-dropdown";
    pracownicyDropdown.classList.add("hidden");


    async function fetchPracownicy() {
        try {
            const response = await fetch('http://localhost/RNZManagementTool/php/get_pracownicy.php');
            pracownicyList = await response.json();
            populateDropdown();
        } catch (error) {
            console.error("Błąd podczas pobierania pracowników:", error);
        }
    }

    function populateDropdown() {
        pracownicyList.forEach(pracownik => {
            const pracownikDiv = document.createElement("div");
            pracownikDiv.textContent = `${pracownik.Imie} ${pracownik.Nazwisko}`;
            pracownikDiv.dataset.id = pracownik.IdOsoba;
            pracownikDiv.style.backgroundColor = pracownik.kolor;
            pracownikDiv.style.color = getComplementaryColor(pracownik.kolor);

            pracownikDiv.addEventListener("click", () => addPracownik(pracownik));
            pracownicyDropdown.appendChild(pracownikDiv);
            pracownicyContainer.appendChild(pracownicyDropdown);
        });
    }

    function addPracownik(pracownik) {
        if (selectedPracownicy.some(p => p.IdOsoba === pracownik.IdOsoba)) {
            alert("Ten pracownik już został dodany!");
            return;
        }
        pracownik.Dzien = "0";
        dataForAll.ListaPracownikow.push(pracownik);
        selectedPracownicy.push(pracownik);
        const pracownikItem = document.createElement("div");
        pracownikItem.className = "pracownik-item";
        pracownikItem.textContent = `${pracownik.Imie} ${pracownik.Nazwisko}`;
        pracownikItem.style.backgroundColor = pracownik.kolor;
        pracownikItem.style.color = getComplementaryColor(pracownik.kolor);
        const removeBtn = document.createElement("span");
        removeBtn.textContent = "x";
        removeBtn.addEventListener("click", () => {
            pracownicyContainer.removeChild(pracownikItem);
            dataForAll.ListaPracownikow = dataForAll.ListaPracownikow.filter(p => p.IdOsoba !== pracownik.IdOsoba);
            selectedPracownicy = selectedPracownicy.filter(p => p.IdOsoba !== pracownik.IdOsoba);
            generateTable(dataForAll.ListaPracownikow);
        });
        removeBtn.style.color = "black";
        pracownikItem.appendChild(removeBtn);
        pracownicyContainer.insertBefore(pracownikItem, addPracownikBtn);
        pracownicyDropdown.classList.remove('active');
        generateTable(dataForAll.ListaPracownikow);

    }
    function updateDropdownPosition() {
        const rect = addPracownikBtn.getBoundingClientRect();
        pracownicyDropdown.style.top = `${rect.bottom + window.scrollY + 5}px`;
        pracownicyDropdown.style.left = `${rect.left + window.scrollX}px`;
    }

    addPracownikBtn.addEventListener("click", () => {
        pracownicyDropdown.classList.toggle("active");
        if (pracownicyDropdown.classList.contains("active")) {
            updateDropdownPosition();
        }
    });


    document.addEventListener('click', (event) => {
        if (!pracownicyContainer.contains(event.target) && !pracownicyDropdown.contains(event.target)) {
            pracownicyDropdown.classList.remove('active');
        }
    });







    function enableFormEditing() {
        const table = document.getElementById("schedule-table");
        table.classList.remove("disabled");
        const pracownicyContainer = document.getElementById("pracownicy-container");
        const pracownikItems = pracownicyContainer.querySelectorAll(".pracownik-item");
        pracownikItems.forEach(pracownikItem => {
            if (!pracownikItem.querySelector("span.remove-btn")) {
                const removeBtn = document.createElement("span");
                removeBtn.textContent = "x";
                removeBtn.className = "remove-btn";
                removeBtn.addEventListener("click", () => {
                    pracownicyContainer.removeChild(pracownikItem);
                    const pracownikId = pracownikItem.dataset.id;
                    dataForAll.ListaPracownikow = dataForAll.ListaPracownikow.filter(p => p.IdOsoba !== pracownikId);
                    selectedPracownicy = selectedPracownicy.filter(p => p.IdOsoba !== pracownikId);
                    generateTable(dataForAll.ListaPracownikow);
                });
                pracownikItem.appendChild(removeBtn);
            }
        });

        addPracownikBtn.id = "add-pracownik-btn";
        addPracownikBtn.textContent = "+";
        addPracownikBtn.classList.add("add-btn");
        document.getElementById("pracownicy-container").appendChild(addPracownikBtn);
        document.querySelectorAll("#event-form input, #event-form textarea").forEach(input => input.disabled = false);
        addPracownikBtn.style.display = "inline-block";
        fetchPracownicy();
        fetchFirms();
    }

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

    async function fetchEventDetails(id) {
        try {
            const response = await fetch(`http://localhost/RNZManagementTool/php/get_event.php?id=${id}`);
            const data = await response.json();
            if (response.ok && data.length > 0) {
                dataForAll = data[0];
                populateEventDetails(data[0]);
            } else {
                alert(data.message || "Błąd podczas pobierania danych");
            }
        } catch (error) {
            console.error("Błąd sieci:", error);
            alert("Nie udało się pobrać danych wydarzenia.");
        }
    }

    function populateEventDetails(event) {
        document.getElementById("nazwaWydarzenia").value = event.NazwaWydarzenia || "";
        document.getElementById("firma").value = event.NazwaFirmy || "";
        document.getElementById("miejsce").value = event.Miejsce || "";
        document.getElementById("data-poczatek").value = event.DataPoczatek || "";
        document.getElementById("data-koniec").value = event.DataKoniec || "";
        document.getElementById("hotel").value = event.Hotel || "";
        document.getElementById("osoba-zarzadzajaca").value = event.OsobaZarzadzajaca || "";
        document.getElementById("komentarz").value = event.Komentarz || "";

        const pracownicyContainer = document.getElementById("pracownicy-container");
        pracownicyContainer.innerHTML = "";
        const uniquePracownicy = new Map();
        event.ListaPracownikow.forEach(pracownik => {
            if (!uniquePracownicy.has(pracownik.IdOsoba)) {
                uniquePracownicy.set(pracownik.IdOsoba, pracownik);
            }
        });
        uniquePracownicy.forEach(pracownik => {
            selectedPracownicy.push(pracownik);
            const pracownikElement = document.createElement("div");
            pracownikElement.textContent = `${pracownik.Imie} ${pracownik.Nazwisko}`;
            pracownikElement.className = "pracownik-item";
            pracownikElement.dataset.id = pracownik.IdOsoba;
            pracownikElement.style.backgroundColor = pracownik.kolor;
            pracownikElement.style.color = getComplementaryColor(pracownik.kolor);
            pracownicyContainer.appendChild(pracownikElement);
        });

        const dataPoczatekInput = document.getElementById("data-poczatek");
        const dataKoniecInput = document.getElementById("data-koniec");

        dataPoczatekInput.addEventListener("change", () => generateTable(event.ListaPracownikow));
        dataKoniecInput.addEventListener("change", () => generateTable(event.ListaPracownikow));
        generateTable(event.ListaPracownikow);
    }

    const editEventButton = document.getElementById("edit-event-btn");
    const handleEditEvent = () => {
        enableFormEditing();
        editEventButton.textContent = "Zapisz";
        editEventButton.id = "save-event-btn";
        editEventButton.removeEventListener("click", handleEditEvent);
        editEventButton.addEventListener("click", handleSaveEvent);
    };
    const handleSaveEvent = () => {
        const eventForm = document.getElementById('event-form');
        eventForm.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    };

    editEventButton.addEventListener("click", handleEditEvent);


    if (eventId) {
        fetchEventDetails(eventId);
    } else {
        console.error('Brak parametru ID w URL');
    }




});


document.getElementById("event-form").addEventListener("submit", async (event) => {
    event.preventDefault();
    console.log(eventId);
    const formData = new FormData(event.target);
    const eventData = Object.fromEntries(formData.entries());
    eventData.pracownicy = selectedPracownicy.map(p => p.IdOsoba);
    eventData.dni = {};
    Object.keys(selectedDays).forEach(pracownikId => {
        eventData.dni[pracownikId] = selectedDays[pracownikId];
    });
    console.log(eventData.dni);

    try {
        const response = await fetch(`http://localhost/RNZManagementTool/php/update_event.php?id=${eventId}`, {
            method: "POST",
            body: JSON.stringify(eventData),
            headers: { "Content-Type": "application/json" }
        });

        const result = await response.json();
        alert(result.message || "Wydarzenie zostało zaktualizowane!");
    } catch (error) {
        console.error("Błąd podczas aktualizacji wydarzenia:", error);
    }
});