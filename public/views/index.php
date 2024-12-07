<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie</title>
    <link href='https://fonts.googleapis.com/css?family=Playfair Display' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">
</head>

<body class="bodyLoginPage">
    <header>
        <img width="512" height="512" src="https://robimynazywo.pl/wp-content/uploads/2023/07/cropped-Logo_1080.png"
            class="custom-logo" alt="ROBIMY NA ŻYWO" decoding="async" fetchpriority="high"
            srcset="https://robimynazywo.pl/wp-content/uploads/2023/07/cropped-Logo_1080.png 512w, https://robimynazywo.pl/wp-content/uploads/2023/07/cropped-Logo_1080-300x300.png 300w, https://robimynazywo.pl/wp-content/uploads/2023/07/cropped-Logo_1080-150x150.png 150w, https://robimynazywo.pl/wp-content/uploads/2023/07/cropped-Logo_1080-270x270.png 270w, https://robimynazywo.pl/wp-content/uploads/2023/07/cropped-Logo_1080-192x192.png 192w, https://robimynazywo.pl/wp-content/uploads/2023/07/cropped-Logo_1080-180x180.png 180w, https://robimynazywo.pl/wp-content/uploads/2023/07/cropped-Logo_1080-32x32.png 32w"
            sizes="(max-width: 512px) 100vw, 512px">
        <div class="RNZ-Header-text">
            <a href="http://www.robimynazywo.pl">ROBIMY NA ŻYWO</a>
            <div>Nie ma problemów, są tylko wyzwania do rozwiązania</div>
        </div>
    </header>
    <div class="bodyLogin">
        <div class="login-container">
            <div class="login-title">LOGOWANIE</div>
            <?php if (isset($messages)) { 
            foreach ($messages as $message) {
                echo "<p style='color: red;'>$message</p>";
            }
            } ?>
            <form class="login-form" action="login" method="POST">
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

        </div>
    </div>
</body>
<script>
const loginContainer = document.querySelector('.login-container');

function changeToCreateForm() {
    loginContainer.innerHTML = `
        <div class="login-title">REJESTRACJA</div>
        <form class="register-form" action="register" method="POST" id="register-from">
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
            <button type="button" class="signup-btn" onclick="changeToLoginForm()">Zaloguj</button>
        </form>
    `;
    document.getElementById('register-from').addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(event.target);
        const eventData = Object.fromEntries(formData.entries());
        try {
            const response = await fetch('http://localhost/RNZManagementTool/php/add_user.php', {
                method: 'POST',
                body: JSON.stringify(eventData),
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            const result = await response.json();
            alert(result.error || result.message || 'Konto zostało stworzone!');
        } catch (error) {
            console.error('Błąd podczas tworzenia konta:', error);
        }

    });
}

function changeToLoginForm() {
    loginContainer.innerHTML = `
        <div class="login-title">LOGOWANIE</div>
        <form class="login-form" action="login" method="POST">
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
            <button type="button" class="signup-btn" onclick="changeToCreateForm()">Stwórz konto</button>
        </form>
    `;
}

document.querySelector('.signup-btn').addEventListener('click', changeToCreateForm);
</script>


</html>