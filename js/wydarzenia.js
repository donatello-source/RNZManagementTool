let allEvents = [];
let activeFilters = {
    places: [],
    companies: [],
    dateStart: null,
    dateEnd: null,
    employees: [],
    employeeMode: "all"
};

async function fetchFirms() {
    try {
        const response = await fetch("http://localhost/RNZManagementTool/php/get_firms.php");
        const companies = await response.json();
        const dataList = document.getElementById('companies-datalist');
        dataList.innerHTML = companies.map(company => `<option value="${company.NazwaFirmy}">`).join('');
    } catch (error) {
        console.error('Błąd podczas ładowania firm:', error);
    }
}
fetchFirms();

async function fetchPracownicy() {
    try {
        const response = await fetch('http://localhost/RNZManagementTool/php/get_pracownicy.php');
        employees = await response.json();
        const dataList = document.getElementById('employees-datalist');
        dataList.innerHTML = employees.map(employee => `<option value="${employee.Imie} ${employee.Nazwisko}">`).join('');
    } catch (error) {
        console.error("Błąd podczas pobierania pracowników:", error);
    }
}

fetchPracownicy();

document.getElementById('filter-button').addEventListener('click', () => {
    const panel = document.getElementById('filters-panel');
    panel.classList.toggle('active');
});


function addChip(inputId, containerId, filterKey) {
    console.log(containerId);
    const input = document.getElementById(inputId);
    const value = input.value.trim();
    if (value && !activeFilters[filterKey].includes(value)) {
        activeFilters[filterKey].push(value);
        const chip = document.createElement('span');
        chip.className = 'chip';
        chip.innerHTML = `${value} <button onclick="removeChip(this, '${value}', '${containerId}', '${filterKey}')">X</button>`;
        document.getElementById(containerId).appendChild(chip);
        input.value = '';
    }
}

function removeChip(button, value, containerId, filterKey) {
    const container = document.getElementById(containerId);
    activeFilters[filterKey] = activeFilters[filterKey].filter(item => item !== value);
    container.removeChild(button.parentElement);
}

// Dodawanie miejsc
document.getElementById('add-place-button').addEventListener('click', () => {
    addChip('filter-place-input', 'filter-places-list', 'places');
});

// Dodawanie firm
document.getElementById('add-company-button').addEventListener('click', () => {
    addChip('filter-company-input', 'filter-companies-list', 'companies');
});

// Dodawanie pracowników
document.getElementById('add-employee-button').addEventListener('click', () => {
    addChip('filter-employee-input', 'filter-employees-list', 'employees');
});

// Obsługa zmiany trybu pracowników
document.querySelectorAll('input[name="employee-mode"]').forEach(radio => {
    radio.addEventListener('change', (e) => {
        activeFilters.employeeMode = e.target.value;
    });
});

// Zastosowanie filtrów
document.getElementById('apply-filters-button').addEventListener('click', () => {
    activeFilters.dateStart = document.getElementById('filter-date-start').value;
    activeFilters.dateEnd = document.getElementById('filter-date-end').value;
    filterAndDisplayEvents();
});

// Czyszczenie filtrów
document.getElementById('clear-filters-button').addEventListener('click', () => {
    activeFilters = {
        places: [],
        companies: [],
        dateStart: null,
        dateEnd: null,
        employees: [],
        employeeMode: "all"
    };
    updateFilterList('filter-places-list', []);
    updateFilterList('filter-companies-list', []);
    updateFilterList('filter-employees-list', []);
    fetchEvents();
});

function updateFilterList(elementId, items) {
    const listElement = document.getElementById(elementId);
    listElement.innerHTML = items.map(item => `<li>${item} <button onclick="removeFilter('${elementId}', '${item}')">Usuń</button></li>`).join('');
}

function removeFilter(listId, item) {
    const key = listId.replace('filter-', '').replace('-list', '');
    activeFilters[key] = activeFilters[key].filter(filterItem => filterItem !== item);
    updateFilterList(listId, activeFilters[key]);
}

function filterAndDisplayEvents() {
    const filteredEvents = allEvents.filter(event => {
        // Filtr miejsca
        const matchesPlace = !activeFilters.places.length || activeFilters.places.includes(event.Miejsce);

        // Filtr firmy
        const matchesCompany = !activeFilters.companies.length || activeFilters.companies.includes(event.NazwaFirmy);

        // Filtr daty
        const eventStart = new Date(event.DataPoczatek);
        const eventEnd = new Date(event.DataKoniec);
        const filterStart = activeFilters.dateStart ? new Date(activeFilters.dateStart) : null;
        const filterEnd = activeFilters.dateEnd ? new Date(activeFilters.dateEnd) : null;
        const matchesDate = (!filterStart || eventEnd >= filterStart) && (!filterEnd || eventStart <= filterEnd);

        // Filtr pracowników
        const employeeIds = event.ListaPracownikow.map(e => e.Imie + ' ' + e.Nazwisko);
        const matchesEmployees = activeFilters.employeeMode === 'all'
            ? activeFilters.employees.every(emp => employeeIds.includes(emp))
            : activeFilters.employees.some(emp => employeeIds.includes(emp));

        return matchesPlace && matchesCompany && matchesDate && matchesEmployees;
    });

    displayEvents(filteredEvents);
}


async function fetchEvents() {
    try {
        const response = await fetch('http://localhost/RNZManagementTool/php/get_events.php');
        const events = await response.json();
        allEvents = events;
        displayEvents(events);
    } catch (error) {
        console.error('Błąd podczas ładowania wydarzeń:', error);
    }
}

function filterEvents(query) {
    const filteredEvents = allEvents.filter(event => {
        const searchQuery = query.toLowerCase();
        return (
            event.NazwaWydarzenia.toLowerCase().includes(searchQuery) ||
            event.Miejsce.toLowerCase().includes(searchQuery) ||
            event.NazwaFirmy.toLowerCase().includes(searchQuery) ||
            event.Komentarz?.toLowerCase().includes(searchQuery) ||
            event.ListaPracownikow.some(employee =>
                `${employee.Imie} ${employee.Nazwisko}`.toLowerCase().includes(searchQuery)
            ) ||
            event.DataPoczatek.includes(searchQuery) ||
            event.DataKoniec.includes(searchQuery)
        );
    });
    displayEvents(filteredEvents);
}

document.getElementById('search-input').addEventListener('input', (event) => {
    const searchQuery = event.target.value;
    filterEvents(searchQuery);
});


function displayEvents(events) {
    const eventsContainer = document.getElementById('events-container');
    eventsContainer.innerHTML = '';
    //console.log(events[0]);


    events.forEach(event => {
        const employeesHtml = event.ListaPracownikow && Array.isArray(event.ListaPracownikow)
            ? event.ListaPracownikow.map(employee => `
            <div style="background-color: ${employee.kolor};" class="employee-chip" onclick="location.href='../pages/profil.php?id=${employee.IdOsoba}';">
                ${employee.Imie} ${employee.Nazwisko}
            </div>
        `).join('')
            : '';

        eventsContainer.innerHTML += `
            <div class="event-card">
                <div class="event-header" onclick="location.href='../pages/wydarzenie.php?id=${event.IdWydarzenia}';">
                    ${event.NazwaWydarzenia} </br>
                    ${event.Miejsce} - ${event.NazwaFirmy}
                </div>
                <div class="event-dates">
                    Od: ${event.DataPoczatek} &nbsp; Do: ${event.DataKoniec}
                </div>
                <div class="event-employees">
                    <div>Lista pracowników:</div>
                    ${employeesHtml}
                </div>
                <div class="event-comment">
                    Komentarz: ${event.Komentarz || 'Brak'}
                </div>
            </div>
        `;
    });
}

window.onload = fetchEvents;