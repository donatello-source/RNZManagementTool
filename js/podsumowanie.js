document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('filter');
    const container = document.getElementById('summary-container');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(form);
        const type = formData.get('type');
        const month = formData.get('month').split("-")[1];
        const year = formData.get('month').split("-")[0];
        try {
            const response = await fetch(`/RNZManagementTool/getSummary?type=${type}&month=${month}&year=${year}`);
            const data = await response.json();
            if (data.error) {
                container.innerHTML = `<div class="no-summary">${data.error}</div>`;
                return;
            }

            renderSummary(type, data, container);
        } catch (error) {
            console.error(error);
            alert('Wystąpił błąd podczas ładowania danych.');
        }
    });

    function renderSummary(type, data, container) {
        let html = '';
        switch (type) {
            case 'firms':
                html = renderFirms(data);
                break;
            case 'events':
                html = renderEvents(data);
                break;
            case 'employees':
                html = renderEmployees(data);
                break;
        }
        container.innerHTML = html;
    }

    function renderFirms(data) {
        const firms = data.data;
        let html = '<h2>Podsumowanie Firm</h2>';

        for (const [firmName, details] of Object.entries(firms)) {
            html += `<div class="summary-card"><h3>${firmName} - Suma: ${details.suma.toFixed(2)} zł</h3>`;
            details.wydarzenia.forEach(event => {
                html += `<div>${event.nazwa} - ${event.suma.toFixed(2)} zł</div> `;
            });
            html += '</div>';
        }
        const totalSum = Object.values(firms).reduce((acc, firm) => acc + firm.suma, 0);
        html += `<div class="total-summary">Podsumowanie: ${totalSum.toFixed(2)} zł</div>`;

        return html;
    }
    function renderEvents(data) {
        console.log(data.data);
        let html = '<h2>Podsumowanie Wydarzeń</h2>';
        for (const [event, details] of Object.entries(data.events)) {
            html += `<h3>${event} (${details.data}) - Suma: ${details.suma} zł</h3>`;
            details.pracownicy.forEach(pracownik => {
                html += `<div>${pracownik.pracownik} - ${pracownik.suma} zł</div>`;
            });
        }
        return html;
    }

    function renderEmployees(data) {
        let html = '<h2>Podsumowanie Pracowników</h2>';
        data.employees.forEach(employee => {
            html += `<div>${employee.pracownik} - ${employee.suma} zł</div>`;
        });
        html += `<div><strong>Suma ogółem: ${data.summary} zł</strong></div>`;
        return html;
    }
});
