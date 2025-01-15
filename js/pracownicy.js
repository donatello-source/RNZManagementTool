class EmployeeManager {
    constructor(containerSelector, userStatusSelector) {
        this.employeeContainer = document.querySelector(containerSelector);
        this.userStatus = document.querySelector(userStatusSelector);
        this.isBoss = false;

        if (!this.employeeContainer) {
            throw new Error('Element kontenera pracowników nie został znaleziony.');
        }

        if (this.userStatus) {
            const status = this.userStatus.textContent;
            this.isBoss = status === 'administrator' || status === 'szef';
        }
    }

    async fetchEmployees() {
        try {
            const response = await fetch('/RNZManagementTool/getAllEmployees');
            const employees = await response.json();
            this.displayEmployees(employees);
        } catch (error) {
            console.error('Błąd podczas ładowania pracowników:', error);
        }
    }

    displayEmployees(data) {
        this.employeeContainer.innerHTML = '';
        data.forEach(employee => {
            const employeeCard = this.createEmployeeCard(employee);
            this.employeeContainer.appendChild(employeeCard);
        });
    }

    createEmployeeCard(employee) {
        const card = document.createElement('div');
        card.className = 'employee-card';
        card.style.backgroundColor = employee.kolor;

        const name = document.createElement('div');
        name.className = 'employee-name';
        name.textContent = `${employee.imie} ${employee.nazwisko}`;

        const phone = document.createElement('div');
        phone.className = 'employee-phone';
        phone.textContent = `Numer telefonu: ${employee.numertelefonu}`;

        card.appendChild(name);
        card.appendChild(phone);

        if (this.isBoss) {
            card.addEventListener('click', () => {
                location.href = `profil.php?id=${employee.idosoba}`;
            });
        }

        return card;
    }

    init() {
        window.onload = () => this.fetchEmployees();
    }
}

const employeeManager = new EmployeeManager('#employee-container', '#userStatus');
employeeManager.init();
