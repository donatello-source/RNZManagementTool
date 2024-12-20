class EventManager {
    constructor() {
        this.allEvents = [];
        this.activeFilters = {
            places: [],
            companies: [],
            dateStart: null,
            dateEnd: null,
            employees: [],
            employeeMode: "all"
        };
        this.init();
    }

    async init() {
        await this.fetchFirms();
        await this.fetchEmployees();
        await this.fetchEvents();
        this.setupListeners();
    }

    async fetchFirms() {
        try {
            const response = await fetch("/getAllFirms");
            const companies = await response.json();
            const dataList = document.getElementById('companies-datalist');
            dataList.innerHTML = companies.map(company => `<option value="${company.nazwafirmy}">`).join('');
        } catch (error) {
            console.error('Error fetching firms:', error);
        }
    }

    async fetchEmployees() {
        try {
            const response = await fetch('/getAllEmployees');
            const employees = await response.json();
            const dataList = document.getElementById('employees-datalist');
            dataList.innerHTML = employees.map(employee => `<option value="${employee.imie} ${employee.nazwisko}">`).join('');
        } catch (error) {
            console.error("Error fetching employees:", error);
        }
    }

    async fetchEvents() {
        try {
            const response = await fetch('/getDetailedEvents');
            this.allEvents = await response.json();
            this.displayEvents(this.allEvents);
        } catch (error) {
            console.error('Error fetching events:', error);
        }
    }

    setupListeners() {
        document.getElementById('filter-button').addEventListener('click', this.toggleFiltersPanel);
        document.getElementById('add-place-button').addEventListener('click', () => this.addChip('filter-place-input', 'filter-places-list', 'places'));
        document.getElementById('add-company-button').addEventListener('click', () => this.addChip('filter-company-input', 'filter-companies-list', 'companies'));
        document.getElementById('add-employee-button').addEventListener('click', () => this.addChip('filter-employee-input', 'filter-employees-list', 'employees'));

        document.querySelectorAll('input[name="employee-mode"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.activeFilters.employeeMode = e.target.value;
            });
        });

        document.getElementById('apply-filters-button').addEventListener('click', () => {
            this.activeFilters.dateStart = document.getElementById('filter-date-start').value;
            this.activeFilters.dateEnd = document.getElementById('filter-date-end').value;
            this.filterAndDisplayEvents();
        });

        document.getElementById('clear-filters-button').addEventListener('click', () => {
            this.resetFilters();
        });

        document.getElementById('search-input').addEventListener('input', (event) => {
            const searchQuery = event.target.value;
            this.filterEvents(searchQuery);
        });
    }

    toggleFiltersPanel() {
        const panel = document.getElementById('filters-panel');
        panel.classList.toggle('active');
    }

    addChip(inputId, containerId, filterKey) {
        const input = document.getElementById(inputId);
        const value = input.value.trim();
        if (value && !this.activeFilters[filterKey].includes(value)) {
            this.activeFilters[filterKey].push(value);
            const chip = document.createElement('span');
            chip.className = 'chip';
            chip.innerHTML = `${value} <button onclick="eventManager.removeChip(this, '${value}', '${containerId}', '${filterKey}')">X</button>`;
            document.getElementById(containerId).appendChild(chip);
            input.value = '';
        }
    }

    removeChip(button, value, containerId, filterKey) {
        const container = document.getElementById(containerId);
        this.activeFilters[filterKey] = this.activeFilters[filterKey].filter(item => item !== value);
        container.removeChild(button.parentElement);
    }

    resetFilters() {
        this.activeFilters = {
            places: [],
            companies: [],
            dateStart: null,
            dateEnd: null,
            employees: [],
            employeeMode: "all"
        };
        this.updateFilterList('filter-places-list', []);
        this.updateFilterList('filter-companies-list', []);
        this.updateFilterList('filter-employees-list', []);
        this.fetchEvents();
    }

    updateFilterList(elementId, items) {
        const listElement = document.getElementById(elementId);
        listElement.innerHTML = '';
    }

    filterAndDisplayEvents() {
        const filteredEvents = this.allEvents.filter(event => {
            const matchesPlace = !this.activeFilters.places.length || this.activeFilters.places.includes(event.miejsce);
            const matchesCompany = !this.activeFilters.companies.length || this.activeFilters.companies.includes(event.nazwafirmy);

            const eventStart = new Date(event.datapoczatek);
            const eventEnd = new Date(event.datakoniec);
            const filterStart = this.activeFilters.dateStart ? new Date(this.activeFilters.dateStart) : null;
            const filterEnd = this.activeFilters.dateEnd ? new Date(this.activeFilters.dateEnd) : null;
            const matchesDate = (!filterStart || eventEnd >= filterStart) && (!filterEnd || eventStart <= filterEnd);

            const employeeIds = event.listapracownikow.map(e => e.imie + ' ' + e.nazwisko);
            const matchesEmployees = this.activeFilters.employeeMode === 'all'
                ? this.activeFilters.employees.every(emp => employeeIds.includes(emp))
                : this.activeFilters.employees.some(emp => employeeIds.includes(emp));

            return matchesPlace && matchesCompany && matchesDate && matchesEmployees;
        });

        this.displayEvents(filteredEvents);
    }

    filterEvents(query) {
        const searchQuery = query.toLowerCase();
        const filteredEvents = this.allEvents.filter(event => {
            return (
                event.nazwawydarzenia.toLowerCase().includes(searchQuery) ||
                event.miejsce.toLowerCase().includes(searchQuery) ||
                event.nazwafirmy.toLowerCase().includes(searchQuery) ||
                event.komentarz?.toLowerCase().includes(searchQuery) ||
                event.listapracownikow.some(employee =>
                    `${employee.imie} ${employee.nazwisko}`.toLowerCase().includes(searchQuery)
                ) ||
                event.datapoczatek.includes(searchQuery) ||
                event.datakoniec.includes(searchQuery)
            );
        });
        this.displayEvents(filteredEvents);
    }

    displayEvents(events) {
        console.log(events);
        const eventsContainer = document.getElementById('events-container');
        eventsContainer.innerHTML = '';

        events.forEach(event => {
            const employeesHtml = event.listapracownikow && Array.isArray(event.listapracownikow)
                ? event.listapracownikow.map(employee => `
                <div style="background-color: ${employee.kolor};" class="employee-chip" onclick="location.href='../pages/profil.php?id=${employee.idosoba}';">
                    ${employee.imie} ${employee.nazwisko}
                </div>
            `).join('')
                : '';

            eventsContainer.innerHTML += `
                <div class="event-card">
                    <div class="event-header" onclick="location.href='../pages/wydarzenie.php?id=${event.idwydarzenia}';">
                        ${event.nazwawydarzenia} </br>
                        ${event.miejsce} - ${event.nazwafirmy}
                    </div>
                    <div class="event-dates">
                        Od: ${event.datapoczatek} &nbsp; Do: ${event.datakoniec}
                    </div>
                    <div class="event-employees">
                        <div>Lista pracowników:</div>
                        ${employeesHtml}
                    </div>
                    <div class="event-comment">
                        komentarz: ${event.komentarz || 'Brak'}
                    </div>
                </div>
            `;
        });
    }
}

const eventManager = new EventManager();
