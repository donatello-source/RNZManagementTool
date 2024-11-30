async function fetchEvents() {
    try {
        const response = await fetch('http://localhost/RNZManagementTool/php/get_events.php');
        const events = await response.json();
        displayEvents(events);
    } catch (error) {
        console.error('Błąd podczas ładowania wydarzeń:', error);
    }
}

function displayEvents(events) {
    const eventsContainer = document.getElementById('events-container');
    eventsContainer.innerHTML = ''; // Wyczyść kontener
    console.log(events[0]);


    events.forEach(event => {
        const employeesHtml = event.ListaPracownikow && Array.isArray(event.ListaPracownikow)
            ? event.ListaPracownikow.map(employee => `
            <div style="background-color: ${employee.kolor};" class="employee-chip" onclick="location.href='../pages/profil.html?id=${employee.IdOsoba}';">
                ${employee.Imie} ${employee.Nazwisko}
            </div>
        `).join('')
            : '';

        eventsContainer.innerHTML += `
            <div class="event-card">
                <div class="event-header" onclick="location.href='../pages/wydarzenie.html?id=${event.IdWydarzenia}';">
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