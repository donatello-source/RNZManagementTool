// Funkcja do pobierania firm
async function fetchFirms() {
    try {
        const response = await fetch('/RNZManagementTool/getAllFirms');
        const firms = await response.json();
        displayFirms(firms);
    } catch (error) {
        console.error('Błąd podczas ładowania firm:', error);
    }
}

// Funkcja do wyświetlania firm
function displayFirms(data) {
    const firmContainer = document.getElementById('firm-container');
    if (firmContainer) {
        firmContainer.innerHTML = ''; // Wyczyść zawartość
        data.forEach(firm => {
            firmContainer.innerHTML += `
                <div class="firm-card" onclick="location.href='profil_firma.php?id=${firm.idfirma}';">
                    <div class='firm-name'>${firm.nazwafirmy}</div>
                    <div class='firm-phone'>Telefon: ${firm.telefon || 'Brak numeru'}</div>
                </div>
            `;
        });
    } else {
        console.error('Element #firm-container nie został znaleziony.');
    }
}

window.onload = fetchFirms;