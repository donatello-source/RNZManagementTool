document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('month-filter');
    const container = document.getElementById('payout-container');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(form);
        const month = formData.get('month').split("-")[1];
        const year = formData.get('month').split("-")[0];

        try {
            const response = await fetch(
                `/RNZManagementTool/getEmployeePayouts?month=${month}&year=${year}`);
            const data = await response.json();
            console.log(data);
            if (data.error) {
                container.innerHTML = `<div class="no-payouts">${data.error}</div>`;
                return;
            }
            let html =
                `<div class="summary-header">Podsumowanie: ${new Date(year, month - 1).toLocaleString('pl-PL', { month: 'long', year: 'numeric' })}</div>`;
            const payoutsArray = Object.values(data.payouts);
            payoutsArray.forEach(event => {
                html += `
                <div class="payout-card">
                    <div class="payout-header">
                        ${event.nazwa} - Suma: ${event.suma} zł
                    </div>
                    <div class="payout-days">
                        ${event.dni.map(day => `
                            <div class="work-day">
                                <div>Data: ${day.dzien}</div>
                                <div>Zarobek: ${day.zarobek} zł</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                `;
            });

            html +=
                `<div class="total-sum">Suma za wszystkie wydarzenia: ${data.summary} zł</div>`;

            container.innerHTML = html;
        } catch (error) {
            console.error(error);
            alert('Wystąpił błąd podczas ładowania danych.');
        }
    });
});