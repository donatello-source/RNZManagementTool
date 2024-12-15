document.addEventListener("DOMContentLoaded", () => {
    const positionContainer = document.getElementById("position-container");
    const table = document.getElementById("positions-table");
    const saveButton = document.getElementById("save-positions");

    let data = {};

    fetch("/RNZManagementTool/getEmployeesPositions")
        .then(response => response.json())
        .then(responseData => {
            data = responseData;
            //console.log(data)
            renderTable(data);
        });

    function renderTable(data) {
        const {
            pracownicy,
            stanowiska,
            powiazania
        } = data;
        const thead = table.querySelector("thead tr");
        const tbody = table.querySelector("tbody");

        stanowiska.forEach(stanowisko => {
            const th = document.createElement("th");
            th.textContent = stanowisko.NazwaStanowiska;
            thead.appendChild(th);
        });

        pracownicy.forEach(pracownik => {
            const row = document.createElement("tr");
            const nameCell = document.createElement("td");
            const link = document.createElement("a");
            link.href =
                `profil.php?id=${pracownik.IdOsoba}`;
            link.textContent = `${pracownik.Imie} ${pracownik.Nazwisko}`;
            link.style.textDecoration = "none";
            link.style.color = "inherit";
            nameCell.appendChild(link);
            row.appendChild(nameCell);

            stanowiska.forEach(stanowisko => {
                const cell = document.createElement("td");
                cell.classList.add("clickable");
                cell.dataset.idOsoba = pracownik.IdOsoba;
                cell.dataset.idStanowiska = stanowisko.IdStanowiska;

                const isAssigned = powiazania.some(
                    p => p.IdOsoba === pracownik.IdOsoba && p.IdStanowiska ===
                        stanowisko.IdStanowiska
                );

                if (isAssigned) {
                    cell.classList.add("selected");
                }

                cell.addEventListener("click", () => {
                    cell.classList.toggle("selected");
                });

                row.appendChild(cell);
            });

            tbody.appendChild(row);
        });
    }

    saveButton.addEventListener("click", () => {
        const updatedPowiazania = [];
        const removedPowiazania = [];
        const cells = table.querySelectorAll("td.clickable");

        cells.forEach(cell => {
            const powiazanie = {
                IdOsoba: cell.dataset.idOsoba,
                IdStanowiska: cell.dataset.idStanowiska,
            };

            if (cell.classList.contains("selected")) {
                updatedPowiazania.push(powiazanie);
            } else {
                removedPowiazania.push(powiazanie);
            }
        });

        fetch("/RNZManagementTool/updateEmployeesPositions", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                powiazania: updatedPowiazania,
                usunPowiazania: removedPowiazania,
            }),
        })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
            })
            .catch(error => {
                console.error('Błąd podczas zapisywania zmian:', error);
            });
    });


});