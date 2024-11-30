// Funkcja do pobierania pracowników z fetch_pracownicy.php
async function fetchEmployees() {
    try {
        const response = await fetch('http://localhost/RNZManagementTool/php/pracownicy.php');
        const employees = await response.json();
        displayEmployees(employees);
    } catch (error) {
        console.error('Błąd podczas ładowania pracowników:', error);
    }
}

// Funkcja do wyświetlania pracowników
function displayEmployees(data) {
    const employeeContainer = document.getElementById('employee-container');
    if (employeeContainer) {  // Dodaj sprawdzenie, czy element istnieje
        employeeContainer.innerHTML = ''; // Wyczyść zawartość
        data.forEach(employee => {
            employeeContainer.innerHTML += `
            <div onclick="location.href='../pages/profil.html?id=${employee.idOsoba}';" class="employee-card" style='background-color: ${employee.kolor}'>
                <div class='employee-name'>${employee.imie} ${employee.nazwisko}</div>
                
                <div class='employee-phone'>Numer telefonu: ${employee.numertelefonu}</div>
            </div>
        `;
        });
    } else {
        console.error('Element #employee-container nie został znaleziony.');
    }
}


// Wywołaj funkcję fetchEmployees, aby załadować dane po załadowaniu strony
window.onload = fetchEmployees;