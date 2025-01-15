class SummaryManager {
    constructor(formSelector, containerSelector) {
        this.form = document.querySelector(formSelector);
        this.container = document.querySelector(containerSelector);

        if (!this.form || !this.container) {
            throw new Error('Element formularza lub kontenera nie został znaleziony.');
        }

        this.init();
    }

    init() {
        this.form.addEventListener('submit', (event) => this.handleFormSubmit(event));
    }

    async handleFormSubmit(event) {
        event.preventDefault();
        const formData = new FormData(this.form);
        const type = formData.get('type');
        const [year, month] = formData.get('month').split("-");
        try {
            const response = await fetch(`/RNZManagementTool/getSummary?type=${type}&month=${month}&year=${year}`);
            const data = await response.json();

            if (data.error) {
                this.container.innerHTML = `<div class="no-summary">${data.error}</div>`;
                return;
            }

            this.renderSummary(type, data);
        } catch (error) {
            console.error('Błąd podczas ładowania danych:', error);
            alert('Wystąpił błąd podczas ładowania danych.');
        }
    }

    renderSummary(type, data) {
        let html = '';
        switch (type) {
            case 'firms':
                html = this.renderFirms(data);
                break;
            case 'events':
                html = this.renderEvents(data);
                break;
            case 'employees':
                html = this.renderEmployees(data);
                break;
            default:
                console.error('Nieznany typ podsumowania:', type);
                return;
        }
        this.container.innerHTML = html;
    }

    renderFirms(data) {
        const firms = data.data;
        let html = '<h2>Podsumowanie Firm</h2>';

        for (const [firmName, details] of Object.entries(firms)) {
            html += `<div class="summary-card">
                <h3>${firmName} - Suma: ${details.suma} zł</h3>`;
            details.wydarzenia.forEach(event => {
                html += `<div>${event.nazwa} - ${event.suma} zł</div>`;
            });
            html += '</div>';
        }
        const totalSum = Object.values(firms).reduce((acc, firm) => acc + firm.suma, 0);
        html += `<div class="total-summary">Podsumowanie: ${totalSum} zł</div>`;

        return html;
    }

    renderEvents(data) {
        const events = data.data;
        let html = '<h2>Podsumowanie Wydarzeń</h2>';
        for (const [eventName, details] of Object.entries(events)) {
            html += `<div class="summary-card">
                <h3>${eventName} (${details.data}) - Suma: ${details.suma} zł</h3>`;
            details.pracownicy.forEach(pracownik => {
                html += `<div>${pracownik.pracownik} - ${pracownik.suma} zł</div>`;
            });
            html += `<div>Dodatkowe koszta - ${details.dodatkowekoszta} zł</div>`;
            html += '</div>';
        }
        const totalSum = Object.values(events).reduce((acc, event) => acc + event.suma, 0);
        html += `<div class="total-summary">Łączna suma wydarzeń: ${totalSum} zł</div>`;

        return html;
    }

    renderEmployees(data) {
        let totalSum = 0;
        const employees = data.data;
        let html = '<h2>Podsumowanie Pracowników</h2>';
        employees.forEach(employee => {
            html += `<div class="summary-card">${employee.pracownik} - ${employee.suma} zł</div>`;
            totalSum += parseFloat(employee.suma);
        });
        html += `<div class="total-summary">Suma ogółem: ${totalSum} zł</div>`;
        return html;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new SummaryManager('#filter', '#summary-container');
});
