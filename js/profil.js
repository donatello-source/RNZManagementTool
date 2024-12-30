// Funkcja do odczytywania parametrów z URL
function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param); // Zwróci wartość parametru 'id'
}
// Odczytanie id z URL
const employeeId = getQueryParam('id');
// Sprawdzenie, czy istnieje ID i pobranie danych
if (employeeId) {
    fetchEmployeeData(employeeId);
} else {
    console.error('Brak parametru ID w URL');
}

// Funkcja do pobrania danych pracownika z API
function fetchEmployeeData(id) {
    fetch(`/RNZManagementTool/getEmployee?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Błąd:', data.error);
            } else {
                displayEmployeeProfile(data); // Wyświetl dane pracownika
            }
        })
        .catch(error => console.error('Błąd podczas ładowania danych:', error));
}

function getComplementaryColor(color) {
    // Tworzymy ukryty element do zamiany dowolnego formatu koloru na RGB
    const dummyDiv = document.createElement('div');
    dummyDiv.style.color = color; // Ustawienie koloru
    document.body.appendChild(dummyDiv);
    // Pobranie rzeczywistego koloru w formacie RGB
    const computedColor = window.getComputedStyle(dummyDiv).color; // Wynik w 'rgb(r, g, b)'
    document.body.removeChild(dummyDiv); // Usunięcie elementu po użyciu
    // Wyciągnięcie składowych RGB
    const rgbMatch = computedColor.match(/rgb\((\d+), (\d+), (\d+)\)/);
    if (!rgbMatch) {
        console.error('Nie można obliczyć koloru dla:', color);
        return '#000000'; // Domyślny kolor: czarny
    }

    const r = parseInt(rgbMatch[1]);
    const g = parseInt(rgbMatch[2]);
    const b = parseInt(rgbMatch[3]);

    // Obliczenie koloru dopełniającego
    const compR = 255 - r;
    const compG = 255 - g;
    const compB = 255 - b;

    // Konwersja na format RGB
    if (r + g + b == 0 && color != 'black' && color != '#000000') {
        return `rgb(0, 0, 0)`
    }
    return `rgb(${compR}, ${compG}, ${compB})`;
}


function displayEmployeeProfile(employee) {
    console.log(employee)
    const profileContainer = document.getElementById('employee-profile');
    if (profileContainer) {
        const complementaryColor = getComplementaryColor(employee.kolor);

        let profileHTML = `
    <div class="employee-card">
        <div class="profil-name">
            <label for="employee-name">Imię i nazwisko:</label>
            <input type="text" id="employee-name" value="${employee.imie} ${employee.nazwisko}" readonly>
        </div>
        <div class="profil-phone">
            <label for="employee-phone">Numer telefonu:</label>
            <input type="text" id="employee-phone" value="${employee.numertelefonu}" readonly>
        </div>
        <div class="profil-mail">
            <label for="employee-mail">Email:</label>
            <input type="email" id="employee-mail" value="${employee.email}" readonly>
        </div>
        <div class="profil-addres">
            <label for="employee-address">Adres zamieszkania:</label>
            <input type="text" id="employee-address" value="${employee.adreszamieszkania}" readonly>
        </div>
        <div class="profil-state">
            <label for="employee-position">Status:</label>
            <input type="text" id="employee-position" value="${employee.status}" readonly>
        </div>
        <div class="profil-color">
            <label for="employee-position">Kolor:</label>
            <input type="text" id="employee-color" value="${employee.kolor}" readonly>
        </div>
    `;

        if (employee.stanowiska.length > 0) {
            profileHTML += `<div class="profil-position">`;
            employee.stanowiska.forEach((stanowisko) => {
                if (stanowisko.stawka == null) {
                    stanowisko.stawka = 0;
                }
                profileHTML += `
                <div class="position-row">
                    <label for="position-salary-${stanowisko.idstanowiska}">${stanowisko.nazwastanowiska} stawka:</label>
                    <input type="text" id="position-salary-${stanowisko.idstanowiska}" value="${stanowisko.stawka}" readonly>
                </div>
            `;
            });
            profileHTML += `
                    </div>
            </div>`;
        }

        profileHTML += `
        <button type="button" id="edit-profil-btn">Edytuj Pracownika</button>
        <style>
            #employee-profile {
                background-color: ${employee.kolor};
            }
            #employee-profile label {
                color: ${complementaryColor};
            }
        </style>
    `;

        profileContainer.innerHTML = profileHTML;
    } else {
        console.error('Element #employee-profile nie został znaleziony.');
    }
    const editProfilButton = document.getElementById("edit-profil-btn");
    editProfilButton.addEventListener("click", handleEditProfile);
}


function enableFormEditing() {
    const deleteBtn = document.createElement("button");
    deleteBtn.textContent = `Usuń Pracownika`;
    deleteBtn.id = "remove-profil-btn";
    deleteBtn.type = "button";
    deleteBtn.addEventListener("click", handleDeleteProfile);
    document.getElementById('employee-profile').appendChild(deleteBtn);

    document.querySelectorAll("#employee-profile input").forEach(input => {
        input.disabled = false;
        input.readOnly = false;
    });
}
function handleDeleteProfile() {
    if (employeeId && confirm("Czy na pewno chcesz usunąć tego pracownika?")) {
        fetch(`/RNZManagementTool/deleteEmployee?id=${employeeId}`, {
            method: "POST",
            headers: { "Content-Type": "application/json" }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Pracownik został usunięty!");
                    window.location.href = "/public/views/pages/pracownicy.php";
                } else {
                    alert("Błąd podczas usuwania pracownika: " + (data.message || 'Nieznany błąd'));
                }
            })
            .catch(error => console.error("Błąd:", error));
    }
}

const handleEditProfile = () => {
    enableFormEditing();
    const editProfilButton = document.getElementById("edit-profil-btn");
    editProfilButton.textContent = "Zapisz Pracownika";
    editProfilButton.id = "save-profil-btn";
    editProfilButton.removeEventListener("click", handleEditProfile);
    editProfilButton.addEventListener("click", handleSaveProfile);
};
const handleSaveProfile = async () => {
    const employeeId = getQueryParam('id');
    const updatedData = {
        imie: document.getElementById("employee-name").value.split(" ")[0],
        nazwisko: document.getElementById("employee-name").value.split(" ")[1],
        numertelefonu: document.getElementById("employee-phone").value,
        email: document.getElementById("employee-mail").value,
        adreszamieszkania: document.getElementById("employee-address").value,
        status: document.getElementById("employee-position").value,
        kolor: document.getElementById("employee-color").value,
        stanowiska: [],
    };

    document.querySelectorAll(".position-row input").forEach((input, index) => {
        updatedData.stanowiska.push({
            idstanowiska: input.id.split('-').pop(),
            stawka: input.value,
        });
    });
    //console.log(updatedData)
    try {
        const response = await fetch(`/updateEmployee?id=${employeeId}`, {
            method: "POST",
            body: JSON.stringify(updatedData),
            headers: {
                "Content-Type": "application/json"
            }
        });

        const result = await response.json();
        alert(result.message);
        window.location.reload();

    } catch (error) {
        console.error("Błąd podczas aktualizacji pracownika:", error);
    }
};