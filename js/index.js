class LoginManager {
    constructor(containerSelector) {
        this.loginContainer = document.querySelector(containerSelector);

        if (!this.loginContainer) {
            throw new Error('Element loginContainer nie został znaleziony.');
        }

        this.init();
    }

    init() {
        this.changeToLoginForm();
    }

    changeToCreateForm() {
        this.loginContainer.innerHTML = `
            <div class="login-title">REJESTRACJA</div>
            <form class="register-form" id="register-form">
                <div class="form-group">
                    <input type="text" name="imie" id="firstName" placeholder=" " required>
                    <label for="firstName">Podaj imię</label>
                </div>
                <div class="form-group">
                    <input type="text" name="nazwisko" id="lastName" placeholder=" " required>
                    <label for="lastName">Podaj nazwisko</label>
                </div>
                <div class="form-group">
                    <input type="email" name="email" id="registerEmail" placeholder=" " required>
                    <label for="registerEmail">Podaj email</label>
                </div>
                <div class="form-group">
                    <input type="password" name="haslo" id="registerPassword" placeholder=" " required>
                    <label for="registerPassword">Podaj hasło</label>
                </div>
                <button type="submit" class="login-btn">Stwórz konto</button>
                <div class="alt-option">lub</div>
                <button type="button" class="signup-btn">Zaloguj</button>
            </form>
        `;

        const registerForm = document.getElementById('register-form');
        const loginButton = this.loginContainer.querySelector('.signup-btn');

        registerForm.addEventListener('submit', async (event) => this.handleRegister(event));
        loginButton.addEventListener('click', () => this.changeToLoginForm());
    }

    changeToLoginForm() {
        this.loginContainer.innerHTML = `
            <div class="login-title">LOGOWANIE</div>
            <form class="login-form" action="/login" method="POST">
                <div class="form-group">
                    <input type="email" name="email" id="email" placeholder=" " required>
                    <label for="email">Email</label>
                </div>
                <div class="form-group">
                    <input type="password" name="password" id="password" placeholder=" " required>
                    <label for="password">Hasło</label>
                </div>
                <button type="submit" class="login-btn">Zaloguj</button>
                <div class="alt-option">lub</div>
                <button type="button" class="signup-btn">Stwórz konto</button>
            </form>
        `;

        const signupButton = this.loginContainer.querySelector('.signup-btn');
        signupButton.addEventListener('click', () => this.changeToCreateForm());
    }

    async handleRegister(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        const userData = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('/RNZManagementTool/addUser', {
                method: 'POST',
                body: JSON.stringify(userData),
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            const result = await response.json();
            alert(result.error || result.message || 'Konto zostało stworzone!');
        } catch (error) {
            console.error('Błąd podczas tworzenia konta:', error);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new LoginManager('.login-container');
});
