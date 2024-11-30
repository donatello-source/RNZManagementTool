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
    eventsContainer.innerHTML = ''; // Wyczyść kontener
    const now = new Date(); // Obecna data

    events.forEach(event => {
        const startDate = new Date(event.DataPoczatek); // Data początku wydarzenia
        const diffInTime = startDate - now; // Różnica w milisekundach
        const diffInDays = Math.ceil(diffInTime / (1000 * 60 * 60 * 24)); // Różnica w dniach

        // Wybieramy odpowiednią klasę na podstawie różnicy dni
        let colorClass = '';
        if (diffInDays <= 7) {
            colorClass = 'red'; // Pudrowy czerwony dla <= 7 dni
        } else if (diffInDays <= 21) {
            colorClass = 'orange'; // Pudrowy pomarańczowy dla > 7 i <= 21 dni
        }

        // Generowanie HTML wydarzenia z odpowiednią klasą
        eventsContainer.innerHTML += `
            <div class="event-card ${colorClass}">
                <div class="event-header" onclick="location.href='../pages/wydarzenie.html?id=${event.IdWydarzenia}';">
                    ${event.Miejsce} - ${event.NazwaFirmy}
                </div>
                <div class="event-dates">
                    Od: ${event.DataPoczatek} &nbsp; Do: ${event.DataKoniec}
                </div>
            </div>
        `;
    });
}


window.onload = fetchEvents;