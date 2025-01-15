class EmployeeProfileManager {
    constructor(profileSelector) {
        this.profileContainer = document.querySelector(profileSelector);
        this.employeeId = this.getQueryParam('id');

        if (!this.profileContainer || !this.employeeId) {
            console.error('Nie znaleziono kontenera profilu lub brakuje parametru ID w URL.');
            return;
        }

        this.init();
    }

    getQueryParam(param) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
    }

    async init() {
        try {
            const employeeData = await this.fetchEmployeeData(this.employeeId);
            if (employeeData.error) {
                console.error('Błąd podczas pobierania danych:', employeeData.error);
                return;
            }
            this.displayEmployeeProfile(employeeData);
        } catch (error) {
            console.error('Błąd podczas inicjalizacji profilu pracownika:', error);
        }
    }

    async fetchEmployeeData(id) {
        const response = await fetch(`/RNZManagementTool/getEmployee?id=${id}`);
        return await response.json();
    }

    getComplementaryColor(color) {
        const dummyDiv = document.createElement('div');
        dummyDiv.style.color = color; // Ustawienie koloru
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

        if (r + g + b == 0 && color != 'black' && color != '#000000') {
            return 'rgb(0, 0, 0)';
        }
        return 'rgb(compR, compG, compB)';
    }

    displayEmployeeProfile(employee) {
        const complementaryColor = this.getComplementaryColor(employee.kolor);

        let profileHTML = `
            <div class="employee-card">
                ${this.createProfileField('Imię i nazwisko', 'employee-name', `${employee.imie} ${employee.nazwisko}`)}
                ${this.createProfileField('Numer telefonu', 'employee-phone', employee.numertelefonu)}
                ${this.createProfileField('Email', 'employee-mail', employee.email)}
                ${this.createProfileField('Adres zamieszkania', 'employee-address', employee.adreszamieszkania)}
                ${this.createProfileField('Status', 'employee-position', employee.status)}
                ${this.createProfileField('Kolor', 'employee-color', employee.kolor)}
            `;

        if (employee.stanowiska.length > 0) {
            profileHTML += `<div class="profil-position">`;
            employee.stanowiska.forEach((stanowisko) => {
                profileHTML += this.createProfileField(
                    `${stanowisko.nazwastanowiska} stawka`,
                    `position-salary-${stanowisko.idstanowiska}`,
                    stanowisko.stawka || 0
                );
            });
            profileHTML += `</div>`;
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

        this.profileContainer.innerHTML = profileHTML;

        document.getElementById('edit-profil-btn').addEventListener('click', () => this.handleEditProfile());
    }

    createProfileField(labelText, id, value) {
        return `
            <div class="profil-field">
                <label for="${id}">${labelText}:</label>
                <input type="text" id="${id}" value="${value}" readonly>
            </div>
        `;
    }

    enableFormEditing() {
        document.querySelectorAll("#employee-profile input").forEach(input => {
            input.readOnly = false;
        });

        const deleteBtn = document.createElement("button");
        deleteBtn.textContent = "Usuń Pracownika";
        deleteBtn.id = "remove-profil-btn";
        deleteBtn.addEventListener("click", () => this.handleDeleteProfile());
        this.profileContainer.appendChild(deleteBtn);
    }

    async handleDeleteProfile() {
        if (confirm("Czy na pewno chcesz usunąć tego pracownika?")) {
            try {
                const response = await fetch(`/RNZManagementTool/deleteEmployee?id=${this.employeeId}`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" }
                });
                const result = await response.json();
                if (result.success) {
                    alert("Pracownik został usunięty!");
                    window.location.href = "/public/views/pages/pracownicy.php";
                } else {
                    alert("Błąd podczas usuwania: " + (result.message || 'Nieznany błąd'));
                }
            } catch (error) {
                console.error("Błąd:", error);
            }
        }
    }

    handleEditProfile() {
        const editButton = document.getElementById("edit-profil-btn");
        if (editButton != null) {
            this.enableFormEditing();
            editButton.textContent = "Zapisz Pracownika";
            editButton.id = "save-profil-btn";
            editButton.addEventListener("click", () => this.handleSaveProfile());
        }
    }

    async handleSaveProfile() {
        const updatedData = {
            imie: document.getElementById("employee-name").value.split(" ")[0],
            nazwisko: document.getElementById("employee-name").value.split(" ")[1],
            numertelefonu: document.getElementById("employee-phone").value,
            email: document.getElementById("employee-mail").value,
            adreszamieszkania: document.getElementById("employee-address").value,
            status: document.getElementById("employee-position").value,
            kolor: document.getElementById("employee-color").value,
            stanowiska: Array.from(document.querySelectorAll(".profil-position input")).map(input => ({
                idstanowiska: input.id.split('-').pop(),
                stawka: input.value
            }))
        };

        try {
            console.log(updatedData);
            const response = await fetch(`/updateEmployee?id=${this.employeeId}`, {
                method: "POST",
                body: JSON.stringify(updatedData),
                headers: { "Content-Type": "application/json" }
            });
            const result = await response.json();
            alert(result.message);
            //window.location.reload();
        } catch (error) {
            console.error("Błąd podczas zapisu:", error);
        }
    }
}

new EmployeeProfileManager('#employee-profile');
