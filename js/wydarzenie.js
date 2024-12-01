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

    if (r + g + b == 0 && color != 'black' && color != '#000000') {
        return `rgb(0, 0, 0)`
    }
    return `rgb(${compR}, ${compG}, ${compB})`;
}

document.addEventListener("DOMContentLoaded", () => {
    const firmList = document.getElementById('firm-list');
    const pracownicyContainer = document.getElementById("pracownicy-container");
    const pracownicyDropdown = document.createElement("div");
    const addPracownikBtn = document.createElement("span");

    let pracownicyList = [];
    let selectedPracownicy = [];
    pracownicyDropdown.id = "pracownicy-dropdown";
    pracownicyDropdown.classList.add("hidden");


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
        pracownicyList.forEach(pracownik => {
            const pracownikDiv = document.createElement("div");
            pracownikDiv.textContent = `${pracownik.Imie} ${pracownik.Nazwisko}`;
            pracownikDiv.dataset.id = pracownik.IdOsoba;
            pracownikDiv.style.backgroundColor = pracownik.kolor;
            pracownikDiv.style.color = getComplementaryColor(pracownik.kolor);

            pracownikDiv.addEventListener("click", () => addPracownik(pracownik));
            pracownicyDropdown.appendChild(pracownikDiv);
            pracownicyContainer.appendChild(pracownicyDropdown);
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
        pracownicyDropdown.classList.remove('active');
    }
    function updateDropdownPosition() {
        const rect = addPracownikBtn.getBoundingClientRect();
        pracownicyDropdown.style.top = `${rect.bottom + window.scrollY + 5}px`;
        pracownicyDropdown.style.left = `${rect.left + window.scrollX}px`;
    }

    addPracownikBtn.addEventListener("click", () => {
        pracownicyDropdown.classList.toggle("active");
        if (pracownicyDropdown.classList.contains("active")) {
            updateDropdownPosition();
        }
    });


    document.addEventListener('click', (event) => {
        if (!pracownicyContainer.contains(event.target) && !pracownicyDropdown.contains(event.target)) {
            pracownicyDropdown.classList.remove('active');
        }
    });

    document.getElementById("event-form").addEventListener("submit", async (event) => {
        event.preventDefault();
        const formData = new FormData(event.target);
        const eventData = Object.fromEntries(formData.entries());
        eventData.pracownicy = selectedPracownicy.map(p => p.IdOsoba);
        console.log(eventData);
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

    function getQueryParam(param) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
    }
    const eventId = getQueryParam('id');



    function enableFormEditing() {
        const pracownicyContainer = document.getElementById("pracownicy-container");
        const pracownikItems = pracownicyContainer.querySelectorAll(".pracownik-item");
        pracownikItems.forEach(pracownikItem => {
            if (!pracownikItem.querySelector("span.remove-btn")) {
                const removeBtn = document.createElement("span");
                removeBtn.textContent = "x";
                removeBtn.className = "remove-btn";
                removeBtn.addEventListener("click", () => {
                    pracownicyContainer.removeChild(pracownikItem);
                    const pracownikId = pracownikItem.dataset.id;
                    selectedPracownicy = selectedPracownicy.filter(p => p.IdOsoba !== pracownikId);
                });
                pracownikItem.appendChild(removeBtn);
            }
        });

        addPracownikBtn.id = "add-pracownik-btn";
        addPracownikBtn.textContent = "+";
        addPracownikBtn.classList.add("add-btn");
        document.getElementById("pracownicy-container").appendChild(addPracownikBtn);
        document.querySelectorAll("#event-form input, #event-form textarea").forEach(input => input.disabled = false);
        addPracownikBtn.style.display = "inline-block";
        fetchPracownicy();
        fetchFirms();
    }

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

    async function fetchEventDetails(id) {
        try {
            const response = await fetch(`http://localhost/RNZManagementTool/php/get_event.php?id=${id}`);
            const data = await response.json();
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

    function populateEventDetails(event) {
        document.getElementById("firma").value = event.NazwaFirmy || "";
        document.getElementById("miejsce").value = event.Miejsce || "";
        document.getElementById("data-poczatek").value = event.DataPoczatek || "";
        document.getElementById("data-koniec").value = event.DataKoniec || "";
        document.getElementById("hotel").value = event.Hotel || "";
        document.getElementById("osoba-zarzadzajaca").value = event.OsobaZarzadzajaca || "";
        document.getElementById("komentarz").value = event.Komentarz || "";

        const pracownicyContainer = document.getElementById("pracownicy-container");
        pracownicyContainer.innerHTML = "";


        event.ListaPracownikow.forEach(pracownik => {
            selectedPracownicy.push(pracownik);
            const pracownikElement = document.createElement("div");
            pracownikElement.textContent = `${pracownik.Imie} ${pracownik.Nazwisko}`;
            pracownikElement.className = "pracownik-item";
            pracownikElement.dataset.id = pracownik.IdOsoba;
            pracownikElement.style.backgroundColor = pracownik.kolor;
            pracownikElement.style.color = getComplementaryColor(pracownik.kolor);
            pracownicyContainer.appendChild(pracownikElement);
        });
    }

    const editEventButton = document.getElementById("edit-event-btn");
    const handleEditEvent = () => {
        enableFormEditing();
        editEventButton.textContent = "Zapisz";
        editEventButton.id = "save-event-btn";
        editEventButton.removeEventListener("click", handleEditEvent);
        editEventButton.addEventListener("click", handleSaveEvent);
    };
    const handleSaveEvent = () => {
        const eventForm = document.getElementById('event-form');
        eventForm.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    };

    editEventButton.addEventListener("click", handleEditEvent);


    if (eventId) {
        fetchEventDetails(eventId);
    } else {
        console.error('Brak parametru ID w URL');
    }
});


