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


    jednodnioweCheckbox.addEventListener('change', () => {
        if (jednodnioweCheckbox.checked) {
            dateKoniec.value = '';
            dateKoniec.disabled = true;
        } else {
            dateKoniec.disabled = false;
        }
    });

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

    async function fetchPracownicy() {
        try {
            const response = await fetch('/getAllEmployees');
            const pracownicy = await response.json();
            pracownicyList = pracownicy;
            pracownicyDropdown.innerHTML = '';
            pracownicy.forEach(pracownik => {
                const pracownikDiv = document.createElement('div');
                pracownikDiv.textContent = `${pracownik.imie} ${pracownik.nazwisko}`;
                pracownikDiv.style.backgroundColor = pracownik.kolor;
                pracownikDiv.style.color = getComplementaryColor(pracownik.kolor);
                pracownikDiv.dataset.id = pracownik.idosoba;

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

    function addPracownikToList(pracownik) {
        if (selectedPracownicy.some(p => p.idosoba === pracownik.idosoba)) {
            alert('Ten pracownik został już dodany!');

            return;
        }

        selectedPracownicy.push(pracownik);
        const pracownikItem = document.createElement('div');
        pracownikItem.classList.add('pracownik-item');
        pracownikItem.style.backgroundColor = pracownik.kolor;
        pracownikItem.textContent = `${pracownik.imie} ${pracownik.nazwisko}`;
        pracownikItem.dataset.id = pracownik.idosoba;

        const removeBtn = document.createElement('span');
        removeBtn.textContent = 'x';
        removeBtn.addEventListener('click', () => {
            pracownicyContainer.removeChild(pracownikItem);
            selectedPracownicy = selectedPracownicy.filter(p => p.idosoba !== pracownik.idosoba);
            generateTable(selectedPracownicy);
        });
        pracownikItem.style.color = getComplementaryColor(pracownik.kolor);
        removeBtn.style.color = getComplementaryColor(pracownik.kolor);
        pracownikItem.appendChild(removeBtn);


        pracownicyContainer.insertBefore(pracownikItem, addPracownikBtn);

        pracownicyDropdown.classList.remove('active');
        generateTable(selectedPracownicy);
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



    fetchFirms();
    fetchPracownicy();

    document.getElementById('event-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(event.target);
        const eventData = Object.fromEntries(formData.entries());
        eventData.pracownicy = selectedPracownicy.map(pracownik => pracownik.idosoba);
        eventData.dni = {};
        Object.keys(selectedDays).forEach(pracownikId => {
            eventData.dni[pracownikId] = selectedDays[pracownikId];
        });
        console.log(eventData);
        try {
            const response = await fetch('/addEvent', {
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

        if (!startDateInput || !endDateInput) {
            return;
        }
        const startDate = new Date(startDateInput);
        const endDate = new Date(endDateInput);
        if (startDate > endDate) {
            return;
        }
        if (selectedPracownicy.length === 0) {
            return;
        }

        table.innerHTML = "";

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
                cellDiv.dataset.selected = "false";
                cellDiv.dataset.date = date;
                cellDiv.dataset.pracownikId = pracownik.idosoba;

                cellDiv.addEventListener("click", toggleCell);
                cellDiv.addEventListener("mouseover", handleMouseOver);

                cell.appendChild(cellDiv);
                row.appendChild(cell);
            });

            table.appendChild(row);
        });

        let isMouseDown = false;

        function toggleCell(event) {
            const cell = event.target;
            const pracownikId = cell.dataset.pracownikId;
            const date = cell.dataset.date;

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

            // console.log(selectedDays);
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
    };


});

