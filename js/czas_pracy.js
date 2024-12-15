document.addEventListener('DOMContentLoaded', async () => {
    const container = document.getElementById('work-time-container');

    const [events, positions] = await Promise.all([
        fetch('/RNZManagementTool/getEmployeeEvents').then(res => res.json()),
        fetch('/RNZManagementTool/getEmployeePositions').then(res => res.json()),
    ]);
    if (events.message) {
        const noEventsMessage = document.createElement('div');
        noEventsMessage.classList.add('no-events-message');
        noEventsMessage.textContent = "Nie znaleziono wydarzeń, jeżeli to pomyłka poinformuj administratora strony.";
        container.appendChild(noEventsMessage);
        return;
    }
    events.forEach(event => {
        const eventCard = document.createElement('div');
        eventCard.classList.add('event-card');
        eventCard.setAttribute('data-id-wydarzenia', event.IdWydarzenia);

        const header = `
            <div class="event-header">${event.NazwaWydarzenia}</div>
            <div class="event-details">
                ${event.NazwaFirmy} - ${event.Miejsce}<br>
                ${event.DataPoczatek} - ${event.DataKoniec}
            </div>
        `;

        let workDays = '';
        event.DniPracy.forEach(day => {
            const options = positions.map(position => `
                <option value="${position.IdStanowiska}" 
                        ${day.IdStanowiska === position.IdStanowiska ? 'selected' : ''}>
                    ${position.NazwaStanowiska}
                </option>
            `).join('');

            workDays += `
                <div class="work-day">
                    <label>${day.Dzien}</label>
                    Obecność:
                    <input type="checkbox" class="presence" ${day.StawkaDzienna == 1 ? 'checked' : ''}>
                    Stanowisko:
                    <select disabled>
                    ${options}
                    </select>
                    Nadgodziny:
                    <input type="number" class="overtime" value="${day.Nadgodziny || 0}" disabled>
                </div>
            `;
        });

        eventCard.innerHTML = header + workDays +
            `<button class="save-button">Zapisz</button>`;
        container.appendChild(eventCard);
    });

    addEventListeners();
});
function addEventListeners() {
    document.querySelectorAll('.presence').forEach(checkbox => {
        checkbox.addEventListener('change', (e) => {
            const parent = e.target.closest('.work-day');
            const select = parent.querySelector('select');
            const input = parent.querySelector('.overtime');
            select.disabled = !e.target.checked;
            input.disabled = !e.target.checked;
        });
    });

    document.querySelectorAll('.save-button').forEach(button => {
        button.addEventListener('click', (e) => {
            const eventCard = e.target.closest('.event-card');
            const eventId = eventCard.getAttribute('data-id-wydarzenia');

            const workDays = Array.from(eventCard.querySelectorAll('.work-day')).map(day => {
                const checkbox = day.querySelector('.presence');
                const select = day.querySelector('select');
                const overtimeInput = day.querySelector('.overtime');

                return {
                    obecność: checkbox.checked ? 1 : 0,
                    dzień: day.querySelector('label').textContent,
                    idStanowiska: checkbox.checked ? parseInt(select.value, 10) : null,
                    nadgodziny: checkbox.checked ? parseInt(overtimeInput.value, 10) || 0 : 0,
                };
            });

            const payload = {
                idWydarzenia: eventId,
                dniPracy: workDays,
            };
            console.log(payload);

            fetch('/RNZManagementTool/saveEmployeeEventDays', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Dane zapisano pomyślnie');
                    } else {
                        alert('Wystąpił błąd: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Błąd podczas zapisywania:', error);
                });
        });
    });
}


