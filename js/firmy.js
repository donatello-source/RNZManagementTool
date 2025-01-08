class FirmManager {
    constructor(containerId, fetchUrl) {
        this.container = document.getElementById(containerId);
        this.fetchUrl = fetchUrl;
    }

    async init() {
        try {
            const firms = await this.fetchFirms();
            this.displayFirms(firms);
        } catch (error) {
            console.error('Błąd podczas ładowania firm:', error);
        }
    }

    async fetchFirms() {
        const response = await fetch(this.fetchUrl);
        return response.json();
    }

    displayFirms(data) {
        if (!this.container) {
            console.error('Element #firm-container nie został znaleziony.');
            return;
        }

        this.container.innerHTML = '';
        data.forEach(firm => {
            const firmCard = this.createFirmCard(firm);
            this.container.appendChild(firmCard);
        });
    }

    createFirmCard(firm) {
        const firmCard = document.createElement('div');
        firmCard.className = 'firm-card';
        firmCard.style.backgroundColor = firm.kolor || '#ffffff';
        firmCard.onclick = () => {
            location.href = `profil_firma.php?id=${firm.idfirma}`;
        };

        firmCard.innerHTML = `
            <div class="firm-name">${firm.nazwafirmy}</div>
            <div class="firm-phone">Telefon: ${firm.telefon || 'Brak numeru'}</div>
        `;

        return firmCard;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const manager = new FirmManager('firm-container', '/RNZManagementTool/getAllFirms');
    manager.init();
});
