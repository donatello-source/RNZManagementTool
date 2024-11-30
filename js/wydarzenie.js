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

document.addEventListener("DOMContentLoaded", () => {
    const pracownicyContainer = document.getElementById("pracownicy-container");
    const pracownicyDropdown = document.createElement("div");
    const addPracownikBtn = document.createElement("span");

    let pracownicyList = [];
    let selectedPracownicy = [];

    pracownicyDropdown.id = "pracownicy-dropdown";
    pracownicyDropdown.classList.add("hidden");
    pracownicyContainer.appendChild(pracownicyDropdown);

    addPracownikBtn.id = "add-pracownik-btn";
    addPracownikBtn.textContent = "+";
    addPracownikBtn.classList.add("add-btn");
    pracownicyContainer.appendChild(addPracownikBtn);

    async function fetchPracownicy() {
        try {
            const response = await fetch('http://localhost/RNZManagementTool/php/get_pracownicy.php');
            pracownicyList = await response.json();
            populateDropdown();
        } catch (error) {
            console.error("Błąd podczas pobierania pracowników:", error);
        }
    }

    function populateDropdown() {
        pracownicyDropdown.innerHTML = "";
        pracownicyList.forEach(pracownik => {
            const pracownikDiv = document.createElement("div");
            pracownikDiv.textContent = `${pracownik.Imie} ${pracownik.Nazwisko}`;
            pracownikDiv.dataset.id = pracownik.IdOsoba;
            pracownikDiv.style.backgroundColor = pracownik.kolor;
            pracownikDiv.style.color = getComplementaryColor(pracownik.kolor);

            pracownikDiv.addEventListener("click", () => addPracownik(pracownik));
            pracownicyDropdown.appendChild(pracownikDiv);
        });
    }

    function addPracownik(pracownik) {
        if (selectedPracownicy.some(p => p.IdOsoba === pracownik.IdOsoba)) {
            alert("Ten pracownik już został dodany!");
            return;
        }

        selectedPracownicy.push(pracownik);
        const pracownikItem = document.createElement("div");
        pracownikItem.className = "pracownik-item";
        pracownikItem.textContent = `${pracownik.Imie} ${pracownik.Nazwisko}`;
        pracownikItem.style.backgroundColor = pracownik.kolor;
        pracownikItem.style.color = getComplementaryColor(pracownik.kolor);

        const removeBtn = document.createElement("span");
        removeBtn.textContent = "x";
        removeBtn.addEventListener("click", () => {
            pracownicyContainer.removeChild(pracownikItem);
            selectedPracownicy = selectedPracownicy.filter(p => p.IdOsoba !== pracownik.IdOsoba);
        });

        pracownikItem.appendChild(removeBtn);
        pracownicyContainer.insertBefore(pracownikItem, addPracownikBtn);
        pracownicyDropdown.classList.add("hidden");
    }

    addPracownikBtn.addEventListener("click", () => {
        pracownicyDropdown.classList.toggle("hidden");
    });

    document.addEventListener("click", event => {
        if (!pracownicyContainer.contains(event.target) && !pracownicyDropdown.contains(event.target)) {
            pracownicyDropdown.classList.add("hidden");
        }
    });

    document.getElementById("event-form").addEventListener("submit", async (event) => {
        event.preventDefault();

        const formData = new FormData(event.target);
        const eventData = Object.fromEntries(formData.entries());
        eventData.pracownicy = selectedPracownicy.map(p => p.IdOsoba);

        try {
            const response = await fetch(`http://localhost/RNZManagementTool/php/update_event.php?id=${eventId}`, {
                method: "POST",
                body: JSON.stringify(eventData),
                headers: { "Content-Type": "application/json" }
            });

            const result = await response.json();
            alert(result.message || "Wydarzenie zostało zaktualizowane!");
        } catch (error) {
            console.error("Błąd podczas aktualizacji wydarzenia:", error);
        }
    });

    // Pobierz ID wydarzenia z URL
    function getQueryParam(param) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param); // Zwróci wartość parametru 'id'
    }
    const eventId = getQueryParam('id');


    document.getElementById("edit-event-btn").addEventListener("click", () => enableFormEditing());

    function enableFormEditing() {
        document.querySelectorAll("#event-form input, #event-form textarea").forEach(input => input.disabled = false);
        addPracownikBtn.style.display = "inline-block";
        fetchPracownicy();
        fetchEventDetails(eventId);
    }

    // Funkcja pobierająca dane wydarzenia z serwera
    async function fetchEventDetails(id) {
        try {
            const response = await fetch(`http://localhost/RNZManagementTool/php/get_event.php?id=${id}`);
            const data = await response.json();
            //console.log(data)
            if (response.ok && data.length > 0) {
                populateEventDetails(data[0]);
            } else {
                alert(data.message || "Błąd podczas pobierania danych");
            }
        } catch (error) {
            console.error("Błąd sieci:", error);
            alert("Nie udało się pobrać danych wydarzenia.");
        }
    }

    // Funkcja uzupełniająca pola danymi wydarzenia
    function populateEventDetails(event) {
        document.getElementById("firma").value = event.NazwaFirmy || "";
        document.getElementById("miejsce").value = event.Miejsce || "";
        document.getElementById("data-poczatek").value = event.DataPoczatek || "";
        document.getElementById("data-koniec").value = event.DataKoniec || "";
        document.getElementById("hotel").value = event.Hotel || ""; // Jeśli masz pole hotel w danych
        document.getElementById("osoba-zarzadzajaca").value = event.OsobaZarzadzajaca || ""; // Jeśli masz pole OsobaZarzadzajaca w danych
        document.getElementById("komentarz").value = event.Komentarz || "";

        const pracownicyContainer = document.getElementById("pracownicy-container");
        pracownicyContainer.innerHTML = "";

        event.ListaPracownikow.forEach(pracownik => {
            const pracownikElement = document.createElement("div");
            pracownikElement.textContent = `${pracownik.Imie} ${pracownik.Nazwisko}`;
            pracownikElement.className = "pracownik-item";
            pracownikElement.style.backgroundColor = pracownik.kolor;
            pracownikElement.style.color = getComplementaryColor(pracownik.kolor);
            pracownicyContainer.appendChild(pracownikElement);
        });
    }
    // Funkcja odblokowująca pola w formularzu
    function enableFormEditing() {
        const inputs = document.querySelectorAll("#event-form input, #event-form textarea");
        inputs.forEach(input => input.disabled = false);
    }
    const editEventButton = document.getElementById("edit-event-btn");
    // Obsługa kliknięcia na przycisk "Edytuj wydarzenie"
    const handleEditEvent = () => {
        enableFormEditing(); // Odblokuj formularz
        editEventButton.textContent = "Zapisz";
        editEventButton.id = "save-event-btn";

        // Usuń listener "Edytuj wydarzenie" i dodaj nowy listener do zapisu
        editEventButton.removeEventListener("click", handleEditEvent);
        editEventButton.addEventListener("click", handleSaveEvent);
    };
    const handleSaveEvent = () => {
        console.log("save");
        // Tutaj możesz dodać logikę do zapisania zmian, np. wysłanie danych do API
    };

    editEventButton.addEventListener("click", handleEditEvent);


    if (eventId) {
        console.log(eventId);
        fetchEventDetails(eventId);
    } else {
        console.error('Brak parametru ID w URL');
    }
});


