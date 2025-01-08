class WorkTimeManager {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.events = [];
        this.positions = [];
    }

    async init() {
        try {
            await this.loadData();
            if (this.events.message) {
                this.displayNoEventsMessage();
            } else {
                this.renderEvents();
                this.addGlobalEventListeners();
            }
        } catch (error) {
            console.error('Błąd podczas inicjalizacji:', error);
        }
    }

    async loadData() {
        const [events, positions] = await Promise.all([
            fetch('/getEmployeeEvents').then(res => res.json()),
            fetch('/getEmployeePositions').then(res => res.json()),
        ]);
        this.events = events;
        this.positions = positions;
    }

    displayNoEventsMessage() {
        const noEventsMessage = document.createElement('div');
        noEventsMessage.classList.add('no-events-message');
        noEventsMessage.textContent = "Nie znaleziono wydarzeń, jeżeli to pomyłka poinformuj administratora strony.";
        this.container.appendChild(noEventsMessage);
    }

    renderEvents() {
        this.events.forEach(event => {
            const eventCard = this.createEventCard(event);
            this.container.appendChild(eventCard);
        });
    }

    createEventCard(event) {
        const eventCard = document.createElement('div');
        eventCard.classList.add('event-card');
        eventCard.setAttribute('data-id-wydarzenia', event.idwydarzenia);

        const header = `
            <div class="event-header">${event.nazwawydarzenia}</div>
            <div class="event-details">
                ${event.nazwafirmy} - ${event.miejsce}<br>
                ${event.datapoczatek} - ${event.datakoniec}
            </div>
        `;

        let workDays = '';
        event.dnipracy.forEach(day => {
            const options = this.positions.map(position => `
                <option value="${position.idstanowiska}" 
                        ${day.idstanowiska === position.idstanowiska ? 'selected' : ''}>
                    ${position.nazwastanowiska}
                </option>
            `).join('');

            workDays += `
                <label>${day.dzien}</label>
                <div class="work-day">
                    Obecność:
                    <input type="checkbox" class="presence" ${day.stawkadzienna == 1 ? 'checked' : ''}>
                    Stanowisko:
                    <select disabled>
                        ${options}
                    </select>
                    Nadgodziny:
                    <select class="overtime" disabled>
                        ${Array.from({ length: 31 }, (_, i) => `<option value="${i}" ${day.nadgodziny == i ? 'selected' : ''}>${i}</option>`).join('')}
                    </select>
                </div>
            `;
        });

        eventCard.innerHTML = header + workDays +
            `<button class="save-button">Zapisz</button>`;
        return eventCard;
    }

    addGlobalEventListeners() {
        this.container.addEventListener('change', (e) => {
            if (e.target.classList.contains('presence')) {
                this.toggleWorkDayInputs(e.target);
            }
        });

        this.container.addEventListener('click', (e) => {
            if (e.target.classList.contains('save-button')) {
                this.saveEventData(e.target);
            }
        });
    }

    toggleWorkDayInputs(checkbox) {
        const parent = checkbox.closest('.work-day');
        const select = parent.querySelector('select');
        const input = parent.querySelector('.overtime');
        select.disabled = !checkbox.checked;
        input.disabled = !checkbox.checked;
    }

    saveEventData(button) {
        const eventCard = button.closest('.event-card');
        const eventId = eventCard.getAttribute('data-id-wydarzenia');

        const workDays = Array.from(eventCard.querySelectorAll('.work-day')).map(day => {
            const checkbox = day.querySelector('.presence');
            const select = day.querySelector('select');
            const overtimeInput = day.querySelector('.overtime');

            const label = day.previousElementSibling;
            const dzien = label ? label.textContent : null;

            return {
                obecność: checkbox.checked ? 1 : 0,
                dzień: dzien,
                idstanowiska: checkbox.checked ? parseInt(select.value, 10) : null,
                nadgodziny: checkbox.checked ? parseInt(overtimeInput.value, 10) || 0 : 0,
            };
        });

        const payload = {
            idWydarzenia: eventId,
            dniPracy: workDays,
        };

        fetch('/saveEmployeeEventDays', {
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
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const manager = new WorkTimeManager('work-time-container');
    manager.init();
});
