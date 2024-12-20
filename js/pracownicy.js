async function fetchEmployees() {
    try {
        const response = await fetch('/RNZManagementTool/getAllEmployees');
        const employees = await response.json();
        displayEmployees(employees);
    } catch (error) {
        console.error('Błąd podczas ładowania pracowników:', error);
    }
}

function displayEmployees(data) {
    const employeeContainer = document.getElementById('employee-container');
    if (employeeContainer) {
        employeeContainer.innerHTML = '';
        data.forEach(employee => {
            console.log(employee)
            employeeContainer.innerHTML += `
            <div onclick="location.href='profil.php?id=${employee.idosoba}';" class="employee-card" style='background-color: ${employee.kolor}'>
                <div class='employee-name'>${employee.imie} ${employee.nazwisko}</div>
                
                <div class='employee-phone'>Numer telefonu: ${employee.numertelefonu}</div>
            </div>
        `;
        });
    } else {
        console.error('Element #employee-container nie został znaleziony.');
    }
}


window.onload = fetchEmployees;