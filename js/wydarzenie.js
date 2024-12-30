let dataForAll = {};
let selectedPracownicy = [];
const selectedDays = {};
let checker = 0;
let isBoss = false;
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

    const groupedByPracownik = pracownicy.reduce((acc, pracownik) => {
        if (!acc[pracownik.idosoba]) acc[pracownik.idosoba] = [];
        acc[pracownik.idosoba].push(pracownik.dzien);
        return acc;
    }, {});
    selectedPracownicy.forEach(pracownik => {
        const row = document.createElement("tr");

        const nameCell = document.createElement("td");
        nameCell.textContent = `${pracownik.imie} ${pracownik.nazwisko}`;
        nameCell.style.backgroundColor = pracownik.kolor;
        nameCell.style.color = getComplementaryColor(pracownik.kolor);
        row.appendChild(nameCell);
        selectedDays[pracownik.idosoba] = [];
        dates.forEach(date => {
            const cell = document.createElement("td");
            const cellDiv = document.createElement("div");

            cellDiv.classList.add("clickable-cell");
            cellDiv.dataset.date = date;
            cellDiv.dataset.pracownikId = pracownik.idosoba;

            cellDiv.addEventListener("click", toggleCell);
            cellDiv.addEventListener("mouseover", handleMouseOver);

            if (groupedByPracownik[pracownik.idosoba]?.includes(date)) {
                selectedDays[pracownik.idosoba].push(date);
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
    let isMouseDown = false;

    function toggleCell(event) {
        const cell = event.target;
        const { pracownikId, date } = cell.dataset;

        const isSelected = cell.dataset.selected === "true";
        cell.dataset.selected = isSelected ? "false" : "true";
        cell.classList.toggle("selected", !isSelected);

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


        //console.log(selectedDays); // Debugowanie
        //console.log(dataForAll.listapracownikow);
    }

    function handleMouseOver(event) {
        if (isMouseDown) {
            const cell = event.target;
            const { pracownikId, date } = cell.dataset;

            cell.dataset.selected = "true";
            cell.classList.add("selected");

            if (!selectedDays[pracownikId].includes(date)) {
                selectedDays[pracownikId].push(date);
            }
        }
    }
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
            const response = await fetch('/RNZManagementTool/getAllEmployees');
            pracownicyList = await response.json();
            populateDropdown();
        } catch (error) {
            console.error("Błąd podczas pobierania pracowników:", error);
        }
    }

    function populateDropdown() {
        pracownicyList.forEach(pracownik => {
            const pracownikDiv = document.createElement("div");
            pracownikDiv.textContent = `${pracownik.imie} ${pracownik.nazwisko}`;
            pracownikDiv.dataset.id = pracownik.idosoba;
            pracownikDiv.style.backgroundColor = pracownik.kolor;
            pracownikDiv.style.color = getComplementaryColor(pracownik.kolor);

            pracownikDiv.addEventListener("click", () => addPracownik(pracownik));
            pracownicyDropdown.appendChild(pracownikDiv);
            pracownicyContainer.appendChild(pracownicyDropdown);
        });
    }

    function addPracownik(pracownik) {
        if (selectedPracownicy.some(p => p.idosoba === pracownik.idosoba)) {
            alert("Ten pracownik już został dodany!");
            return;
        }
        pracownik.dzien = "0";
        dataForAll.listapracownikow.push(pracownik);
        selectedPracownicy.push(pracownik);
        const pracownikItem = document.createElement("div");
        pracownikItem.className = "pracownik-item";
        pracownikItem.textContent = `${pracownik.imie} ${pracownik.nazwisko}`;
        pracownikItem.style.backgroundColor = pracownik.kolor;
        pracownikItem.style.color = getComplementaryColor(pracownik.kolor);
        const removeBtn = document.createElement("span");
        removeBtn.textContent = "x";
        removeBtn.addEventListener("click", () => {
            pracownicyContainer.removeChild(pracownikItem);
            dataForAll.listapracownikow = dataForAll.listapracownikow.filter(p => p.idosoba !== pracownik.idosoba);
            selectedPracownicy = selectedPracownicy.filter(p => p.idosoba !== pracownik.idosoba);
            generateTable(dataForAll.listapracownikow);
        });
        removeBtn.style.color = "black";
        pracownikItem.appendChild(removeBtn);
        pracownicyContainer.insertBefore(pracownikItem, addPracownikBtn);
        pracownicyDropdown.classList.remove('active');
        generateTable(dataForAll.listapracownikow);

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
                    dataForAll.listapracownikow = dataForAll.listapracownikow.filter(p => p.idosoba !== pracownikId);
                    selectedPracownicy = selectedPracownicy.filter(p => p.idosoba !== pracownikId);
                    generateTable(dataForAll.listapracownikow);
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
        const deleteBtn = document.createElement("button");
        deleteBtn.textContent = `Usuń wydarzenie`;
        deleteBtn.id = "remove-event-btn";
        deleteBtn.type = "button";
        deleteBtn.addEventListener("click", handleDeleteEvent);
        document.getElementById('event-form').appendChild(deleteBtn);
    }

    async function fetchFirms() {
        try {
            const response = await fetch("/getAllFirms");
            const firms = await response.json();
            firms.forEach(firm => {
                const option = document.createElement('option');
                option.value = firm.nazwafirmy;
                firmList.appendChild(option);
            });
        } catch (error) {
            console.error('Błąd podczas ładowania firm:', error);
        }
    }

    async function fetchEventDetails(id) {
        try {
            const response = await fetch(`/getEvent?id=${id}`);
            const data = await response.json();

            if (response.ok && data) {
                dataForAll = data;
                populateEventDetails(data);
            } else {
                alert(data.message || "Błąd podczas pobierania danych");
            }
        } catch (error) {
            console.error("Błąd sieci:", error);
            alert("Nie udało się pobrać danych wydarzenia.");
        }
    }

    function populateEventDetails(event) {
        document.getElementById("nazwaWydarzenia").value = event.nazwawydarzenia || "";
        document.getElementById("firma").value = event.nazwafirmy || "";
        document.getElementById("miejsce").value = event.miejsce || "";
        document.getElementById("data-poczatek").value = event.datapoczatek || "";
        document.getElementById("data-koniec").value = event.datakoniec || "";
        document.getElementById("hotel").value = event.hotel || "";
        document.getElementById("osoba-zarzadzajaca").value = event.osobazarzadzajaca || "";
        document.getElementById("komentarz").value = event.komentarz || "";

        const pracownicyContainer = document.getElementById("pracownicy-container");
        pracownicyContainer.innerHTML = "";
        const uniquePracownicy = new Map();
        //console.log(event);
        event.listapracownikow.forEach(pracownik => {
            if (!uniquePracownicy.has(pracownik.idosoba)) {
                uniquePracownicy.set(pracownik.idosoba, pracownik);
            }
        });
        uniquePracownicy.forEach(pracownik => {
            selectedPracownicy.push(pracownik);
            const pracownikElement = document.createElement("div");
            pracownikElement.textContent = `${pracownik.imie} ${pracownik.nazwisko}`;
            pracownikElement.className = "pracownik-item";
            pracownikElement.dataset.id = pracownik.idosoba;
            pracownikElement.style.backgroundColor = pracownik.kolor;
            pracownikElement.style.color = getComplementaryColor(pracownik.kolor);
            pracownicyContainer.appendChild(pracownikElement);
        });

        const datapoczatekInput = document.getElementById("data-poczatek");
        const datakoniecInput = document.getElementById("data-koniec");

        datapoczatekInput.addEventListener("change", () => generateTable(event.listapracownikow));
        datakoniecInput.addEventListener("change", () => generateTable(event.listapracownikow));
        generateTable(event.listapracownikow);
    }

    const editEventButton = document.getElementById("edit-event-btn");
    if (editEventButton) {
        isBoss = true;
    }
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

    const handleDeleteEvent = async () => {
        try {
            const response = await fetch(`/deleteEvent?id=${eventId}`, {
                method: "POST",
                headers: { "Content-Type": "application/json" }
            });
            const result = await response.json();
            alert(result.message || "Wydarzenie zostało usunięte!");
        } catch (error) {
            console.error("Błąd podczas aktualizacji wydarzenia:", error);
        }
    };


    if (isBoss) {
        editEventButton.addEventListener("click", handleEditEvent);
    }

    if (eventId) {
        fetchEventDetails(eventId);
    } else {
        console.error('Brak parametru ID w URL');
    }




});


document.getElementById("event-form").addEventListener("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
    const eventData = Object.fromEntries(formData.entries());
    eventData.pracownicy = selectedPracownicy.map(p => p.idosoba);
    eventData.dni = {};
    Object.keys(selectedDays).forEach(pracownikId => {
        eventData.dni[pracownikId] = selectedDays[pracownikId];
    });
    //console.log(eventData);

    try {
        const response = await fetch(`/updateEvent?id=${eventId}`, {
            method: "POST",
            body: JSON.stringify(eventData),
            headers: { "Content-Type": "application/json" }
        });

        const result = await response.json();
        alert(result.message);
    } catch (error) {
        console.error("Błąd podczas aktualizacji wydarzenia:", error);
    }
});