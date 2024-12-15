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

    if (r + g + b == 0 && color != 'black' && color != '#000000' && color != "#808080") {
        return `rgb(0, 0, 0)`
    }
    return `rgb(${compR}, ${compG}, ${compB})`;
}

function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}
const firmId = getQueryParam('id');
if (firmId) {
    fetchFirmData(firmId);
} else {
    console.error('Brak parametru ID w URL');
}

function fetchFirmData(id) {
    fetch(`/RNZManagementTool/getFirm?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Błąd:', data.error);
            } else {
                displayFirmProfile(data);
                //console.log(data);
            }
        })
        .catch(error => console.error('Błąd podczas ładowania danych:', error));
}



function displayFirmProfile(firm) {
    const firmContainer = document.getElementById('firm-profile');
    if (firmContainer) {
        const complementaryColor = getComplementaryColor(firm.kolor);
        firmContainer.innerHTML = `
<div class="firm-card">
    <div class="firm-name">
        <label for="firm-name">Nazwa Firmy:</label>
        <input type="text" id="firm-name" value="${firm.NazwaFirmy}" readonly>
    </div>
    <div class="firm-phone">
        <label for="firm-phone">Numer telefonu:</label>
        <input type="text" id="firm-phone" value="${firm.Telefon}" readonly>
    </div>
    <div class="firm-address">
        <label for="firm-address">Adres:</label>
        <input type="text" id="firm-address" value="${firm.AdresFirmy}" readonly>
    </div>
    <div class="firm-NIP">
        <label for="firm-NIP">NIP:</label>
        <input type="text" id="firm-NIP" value="${firm.NIP}" readonly>
    </div>
    <div class="firm-color">
        <label for="firm-color">Kolor:</label>
        <input type="text" id="firm-color" value="${firm.kolor}" readonly>
    </div>
</div>
<button type="button" id="edit-profil-btn">Edytuj Firmę</button>
<style>
    #firm-profile {
        background-color: ${firm.kolor};
    }
    #firm-profile label {
        color: ${complementaryColor};
    }
</style>`;

    } else {
        console.error('Element #firm-profile nie został znaleziony.');
    }
    const editProfilButton = document.getElementById("edit-profil-btn");
    editProfilButton.addEventListener("click", handleEditProfile);
}


function enableFormEditing() {
    const deleteBtn = document.createElement("button");
    deleteBtn.textContent = `Usuń Firmę`;
    deleteBtn.id = "remove-profil-btn";
    deleteBtn.type = "button";
    deleteBtn.addEventListener("click", handleDeleteProfile);
    document.getElementById('firm-profile').appendChild(deleteBtn);

    document.querySelectorAll("#firm-profile input").forEach(input => {
        input.disabled = false;
        input.readOnly = false;
    });
}
function handleDeleteProfile() {
    if (firmId && confirm("Czy na pewno chcesz usunąć tą firmę?")) {
        fetch(`/RNZManagementTool/deleteFirm?id=${firmId}`, {
            method: "POST",
            headers: { "Content-Type": "application/json" }
        })
            .then(response => response.json())
            .then(data => {
                if (!data.error) {
                    alert("Firma została usunięta!");
                    window.location.href = "/RNZManagementTool/public/views/pages/firmy.php";
                } else {
                    alert("Błąd podczas usuwania firmy: " + (data.error || 'Nieznany błąd'));
                }
            })
            .catch(error => console.error("Błąd:", error));
    }
}

const handleEditProfile = () => {
    enableFormEditing();
    const editProfilButton = document.getElementById("edit-profil-btn");
    editProfilButton.textContent = "Zapisz Firmę";
    editProfilButton.id = "save-profil-btn";
    editProfilButton.removeEventListener("click", handleEditProfile);
    editProfilButton.addEventListener("click", handleSaveProfile);
};
const handleSaveProfile = async () => {
    const updatedData = {
        NazwaFirmy: document.getElementById("firm-name").value,
        Telefon: document.getElementById("firm-phone").value,
        AdresFirmy: document.getElementById("firm-address").value,
        NIP: document.getElementById("firm-NIP").value,
        kolor: document.getElementById("firm-color").value
    };
    //console.log(updatedData);

    try {
        const response = await fetch(`/RNZManagementTool/updateFirm?id=${firmId}`, {
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