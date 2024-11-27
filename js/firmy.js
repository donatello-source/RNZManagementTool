// Funkcja do pobierania firm
async function fetchFirms() {
    try {
        const response = await fetch('http://localhost/RNZManagementTool/php/get_firms.php');
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
                <div class="firm-card" onclick="location.href='../pages/profil_firma.html?id=${firm.IdFirma}';">
                    <div class='firm-name'>${firm.NazwaFirmy}</div>
                    <div class='firm-phone'>Telefon: ${firm.Telefon || 'Brak numeru'}</div>
                </div>
            `;
        });
    } else {
        console.error('Element #firm-container nie został znaleziony.');
    }
}

window.onload = fetchFirms;