class EventManager {
    constructor() {
        this.events = [];
        this.ongoingEvents = [];
        this.pastEvents = [];
        this.eventsContainer = document.getElementById('events');
    }

    async fetchEvents() {
        try {
            const response = await fetch('/getEvents');
            this.events = await response.json();
            this.processEvents();
        } catch (error) {
            console.error('Błąd podczas ładowania wydarzeń:', error);
        }
    }

    processEvents() {
        const now = new Date();

        this.ongoingEvents = [];
        this.pastEvents = [];
        this.events.forEach(event => {
            const endDate = new Date(event.datakoniec);
            endDate.setDate(endDate.getDate() + 1);

            if (endDate >= now) {
                this.ongoingEvents.push(event);
            } else {
                this.pastEvents.push(event);
            }
        });

        this.sortEvents();
        this.displayEvents();
    }

    sortEvents() {
        this.ongoingEvents.sort((a, b) => new Date(a.datapoczatek) - new Date(b.datapoczatek));
        this.pastEvents.sort((a, b) => new Date(b.datapoczatek) - new Date(a.datapoczatek));
    }

    generateEventHTML(event) {
        const now = new Date();
        const startDate = new Date(event.datapoczatek);
        const endDate = new Date(event.datakoniec);
        const diffInDays = Math.ceil((startDate - now) / (1000 * 60 * 60 * 24));
        const diffInDaysE = Math.ceil((endDate - now) / (1000 * 60 * 60 * 24));

        let colorClass = '';
        if (diffInDays <= 7) {
            colorClass = 'red';
        } else if (diffInDays <= 21) {
            colorClass = 'orange';
        }
        if (diffInDaysE < 0) {
            colorClass = 'grey';
        }

        return `
            <div class="event-card ${colorClass}">
                <div class="event-header" onclick="location.href='wydarzenie.php?id=${event.idwydarzenia}';">
                    ${event.nazwawydarzenia} </br>
                    ${event.miejsce} - ${event.nazwafirmy}
                </div>
                <div class="event-dates">
                    Od: ${event.datapoczatek} &nbsp; Do: ${event.datakoniec}
                </div>
            </div>
        `;
    }

    displayEvents() {
        this.eventsContainer.innerHTML = '';

        // Display ongoing events
        this.ongoingEvents.forEach(event => {
            this.eventsContainer.innerHTML += this.generateEventHTML(event);
        });

        // Display past events toggle section
        this.eventsContainer.innerHTML += `
            <div class="past-events-header" onclick="eventManager.togglePastEvents()">
                <span id="past-events-toggle">> </span>Zakończone wydarzenia
            </div>
            <div id="past-events-container" style="display: none;">
                ${this.pastEvents.map(event => this.generateEventHTML(event)).join('')}
            </div>
        `;
    }

    togglePastEvents() {
        const container = document.getElementById('past-events-container');
        const toggleIcon = document.getElementById('past-events-toggle');
        if (container.style.display === 'none') {
            container.style.display = 'block';
            toggleIcon.style.transform = 'rotate(90deg)';
        } else {
            container.style.display = 'none';
            toggleIcon.style.transform = 'rotate(0deg)';
        }
    }
}

const eventManager = new EventManager();
window.onload = () => eventManager.fetchEvents();
