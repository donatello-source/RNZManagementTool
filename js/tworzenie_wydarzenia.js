document.addEventListener('DOMContentLoaded', () => {
    const datePoczatek = document.getElementById('data-poczatek');
    const dateKoniec = document.getElementById('data-koniec');
    const jednodnioweCheckbox = document.getElementById('jednodniowe');
    const firmList = document.getElementById('firm-list');
    const pracownicyContainer = document.getElementById('pracownicy-container');
    const pracownicyDropdown = document.getElementById('pracownicy-dropdown');
    const addPracownikBtn = document.getElementById('add-pracownik-btn');

    let pracownicyList = []; // Lista pracowników z bazy
    let selectedPracownicy = []; // Wybrani pracownicy

    // Ustaw dzisiejszą datę jako minimalną
    const today = new Date().toISOString().split('T')[0];
    datePoczatek.min = today;

    // Obsługa checkboxa "Wydarzenie jednodniowe"
    jednodnioweCheckbox.addEventListener('change', () => {
        if (jednodnioweCheckbox.checked) {
            dateKoniec.value = '';
            dateKoniec.disabled = true;
        } else {
            dateKoniec.disabled = false;
        }
    });

    // Pobierz firmy z bazy
    async function fetchFirms() {
        try {
            const response = await fetch("http://localhost/RNZManagementTool/php/get_firms.php");
            const firms = await response.json();
            firms.forEach(firm => {
                const option = document.createElement('option');
                option.value = firm.NazwaFirmy;
                firmList.appendChild(option);
            });
        } catch (error) {
            console.error('Błąd podczas ładowania firm:', error);
        }
    }

    async function fetchPracownicy() {
        try {
            const response = await fetch('http://localhost/RNZManagementTool/php/get_pracownicy.php');
            const pracownicy = await response.json();
            pracownicyList = pracownicy;
            pracownicyDropdown.innerHTML = '';
            pracownicy.forEach(pracownik => {
                const pracownikDiv = document.createElement('div');
                pracownikDiv.textContent = `${pracownik.Imie} ${pracownik.Nazwisko}`;
                pracownikDiv.style.backgroundColor = pracownik.kolor; // Ustaw kolor tła
                pracownikDiv.style.color = getComplementaryColor(pracownik.kolor); // Ustaw kolor tła
                pracownikDiv.dataset.id = pracownik.IdOsoba;

                pracownikDiv.addEventListener('click', () => {
                    addPracownikToList(pracownik);
                });

                pracownicyDropdown.appendChild(pracownikDiv);
            });
        } catch (error) {
            console.error('Błąd podczas ładowania pracowników:', error);
        }
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

        const compR = 255 - r;
        const compG = 255 - g;
        const compB = 255 - b;

        if (r + g + b == 0 && color != 'black' && color != '#000000') {
            return `rgb(0, 0, 0)`
        }
        return `rgb(${compR}, ${compG}, ${compB})`;
    }

    // Funkcja dodająca pracownika do listy
    function addPracownikToList(pracownik) {
        if (selectedPracownicy.some(p => p.IdOsoba === pracownik.IdOsoba)) {
            alert('Ten pracownik został już dodany!');
            return;
        }

        selectedPracownicy.push(pracownik);
        const pracownikItem = document.createElement('div');
        pracownikItem.classList.add('pracownik-item');
        pracownikItem.style.backgroundColor = pracownik.kolor;
        pracownikItem.textContent = `${pracownik.Imie} ${pracownik.Nazwisko}`;
        pracownikItem.dataset.id = pracownik.IdOsoba;

        const removeBtn = document.createElement('span');
        removeBtn.textContent = 'x';
        removeBtn.addEventListener('click', () => {
            pracownicyContainer.removeChild(pracownikItem);
            selectedPracownicy = selectedPracownicy.filter(p => p.IdOsoba !== pracownik.IdOsoba);
        });
        pracownikItem.style.color = getComplementaryColor(pracownik.kolor);
        removeBtn.style.color = getComplementaryColor(pracownik.kolor);
        pracownikItem.appendChild(removeBtn);


        pracownicyContainer.insertBefore(pracownikItem, addPracownikBtn);

        pracownicyDropdown.classList.remove('active'); // Schowaj dropdown
    }
    function updateDropdownPosition() {
        const rect = addPracownikBtn.getBoundingClientRect(); // Pobieramy pozycję przycisku "+" w oknie
        pracownicyDropdown.style.top = `${rect.bottom + window.scrollY + 5}px`; // Ustawiamy dropdown tuż pod przyciskiem
        pracownicyDropdown.style.left = `${rect.left + window.scrollX}px`; // Ustawiamy lewą krawędź dropdowna
    }

    // Obsługa kliknięcia "+" przycisku
    addPracownikBtn.addEventListener("click", () => {
        pracownicyDropdown.classList.toggle("active");
        if (pracownicyDropdown.classList.contains("active")) {
            updateDropdownPosition(); // Aktualizujemy pozycję dropdowna, gdy się pojawi
        }
    });


    // Ukryj dropdown, jeśli kliknięto poza nim
    document.addEventListener('click', (event) => {
        if (!pracownicyContainer.contains(event.target) && !pracownicyDropdown.contains(event.target)) {
            pracownicyDropdown.classList.remove('active');
        }
    });



    fetchFirms();
    fetchPracownicy();

    document.getElementById('event-form').addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(event.target);
        const eventData = Object.fromEntries(formData.entries());
        eventData.pracownicy = selectedPracownicy.map(pracownik => pracownik.IdOsoba);
        console.log(eventData);
        try {
            const response = await fetch('http://localhost/RNZManagementTool/php/add_event.php', {
                method: 'POST',
                body: JSON.stringify(eventData),
                headers: {
                    'Content-Type': 'application/json'
                }
            });


            const result = await response.json();
            alert(result.message || 'Wydarzenie zostało utworzone!');
        } catch (error) {
            console.error('Błąd podczas tworzenia wydarzenia:', error);
        }
    });
});
