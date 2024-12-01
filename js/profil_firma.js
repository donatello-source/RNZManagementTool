// Funkcja do odczytywania parametrów z URL
function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param); // Zwróci wartość parametru 'id'
}
// Odczytanie id z URL
const firmId = getQueryParam('id');
// Sprawdzenie, czy istnieje ID i pobranie danych
if (firmId) {
    fetchFirmData(firmId);
} else {
    console.error('Brak parametru ID w URL');
}

// Funkcja do pobrania danych pracownika z API
function fetchFirmData(id) {
    fetch(`http://localhost/RNZManagementTool/php/get_firm.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Błąd:', data.error);
            } else {
                displayFirmProfile(data); // Wyświetl dane pracownika
                console.log(data);
            }
        })
        .catch(error => console.error('Błąd podczas ładowania danych:', error));
}



function displayFirmProfile(firm) {
    const profileContainer = document.getElementById('firm-profile');
    if (profileContainer) {

        profileContainer.innerHTML = `
<div class="firm-card">
    <div class="firm-name">
        <label for="firm-name">Nazwa Firmy:</label>
        <input type="text" id="firm-name" value="${firm.NazwaFirmy}" readonly>
    </div>
    <div class="firm-phone">
        <label for="firm-phone">Numer telefonu:</label>
        <input type="text" id="firm-phone" value="${firm.Telefon}" readonly>
    </div>
    <div class="profil-addres">
        <label for="firm-address">Adres:</label>
        <input type="text" id="firm-address" value="${firm.AdresFirmy}" readonly>
    </div>
    <div class="profil-state">
        <label for="firm-position">NIP:</label>
        <input type="text" id="firm-position" value="${firm.NIP}" readonly>
    </div>
</div>
`;
    } else {
        console.error('Element #firm-profile nie został znaleziony.');
    }
}

