async function fetchEvents() {
    try {
        const response = await fetch('http://localhost/RNZManagementTool/php/get_events_main.php');
        const events = await response.json();
        displayEvents(events);
    } catch (error) {
        console.error('Błąd podczas ładowania wydarzeń:', error);
    }
}

function displayEvents(events) {
    const eventsContainer = document.getElementById('events');
    eventsContainer.innerHTML = '';
    const now = new Date();

    const ongoingEvents = [];
    const pastEvents = [];

    events.forEach(event => {
        const endDate = new Date(event.DataKoniec);
        if (endDate >= now) {
            ongoingEvents.push(event);
        } else {
            pastEvents.push(event);
        }
    });

    ongoingEvents.sort((a, b) => new Date(a.DataPoczatek) - new Date(b.DataPoczatek));
    pastEvents.sort((a, b) => new Date(b.DataPoczatek) - new Date(a.DataPoczatek));

    ongoingEvents.forEach(event => {
        eventsContainer.innerHTML += generateEventHTML(event);
    });

    eventsContainer.innerHTML += `
        <div class="past-events-header" onclick="togglePastEvents()">
            <span id="past-events-toggle">> </span>Zakończone wydarzenia
        </div>
        <div id="past-events-container" style="display: none;">
            ${pastEvents.map(event => generateEventHTML(event)).join('')}
        </div>
    `;
}

function generateEventHTML(event) {
    const now = new Date();
    const startDate = new Date(event.DataPoczatek);
    const endDate = new Date(event.DataKoniec);
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
            <div class="event-header" onclick="location.href='wydarzenie.php?id=${event.IdWydarzenia}';">
                ${event.NazwaWydarzenia} </br>
                ${event.Miejsce} - ${event.NazwaFirmy}
            </div>
            <div class="event-dates">
                Od: ${event.DataPoczatek} &nbsp; Do: ${event.DataKoniec}
            </div>
        </div>
    `;
}

function togglePastEvents() {
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


window.onload = fetchEvents;
