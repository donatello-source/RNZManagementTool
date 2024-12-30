
fetchEmployeeData();


function fetchEmployeeData() {
    fetch(`/RNZManagementTool/getEmployeeProfile`)
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
    const dummyDiv = document.createElement('div');
    dummyDiv.style.color = color;
    document.body.appendChild(dummyDiv);
    const computedColor = window.getComputedStyle(dummyDiv).color;
    document.body.removeChild(dummyDiv);
    const rgbMatch = computedColor.match(/rgb\((\d+), (\d+), (\d+)\)/);
    if (!rgbMatch) {
        console.error('Nie można obliczyć koloru dla:', color);
        return '#000000';
    }

    const r = parseInt(rgbMatch[1]);
    const g = parseInt(rgbMatch[2]);
    const b = parseInt(rgbMatch[3]);

    const compR = 255 - r;
    const compG = 255 - g;
    const compB = 255 - b;

    return `rgb(${compR}, ${compG}, ${compB})`;
}

function displayEmployeeProfile(employee) {
    // console.log(employee);
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
        <div class="profil-color">
            <label for="employee-color">Kolor:</label>
            <input type="text" id="employee-color" value="${employee.kolor}" readonly>
        </div>
    </div>
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
    document.querySelectorAll("#employee-profile input").forEach(input => {
        input.disabled = false;
        input.readOnly = false;
    });
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
    const updatedData = {
        imie: document.getElementById("employee-name").value.split(" ")[0],
        nazwisko: document.getElementById("employee-name").value.split(" ")[1],
        numertelefonu: document.getElementById("employee-phone").value,
        email: document.getElementById("employee-mail").value,
        adreszamieszkania: document.getElementById("employee-address").value,
        kolor: document.getElementById("employee-color").value,
    };

    try {
        const response = await fetch(`/updateEmployeeProfile`, {
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
