const API_URL = "http://192.168.1.74:8000";                                                                                 //URL del servidor PHP

document.getElementById("login-form").addEventListener("submit", async function (event) {                                   //Quan s'envia el submit del formulari del login
event.preventDefault();                                                                                                     //Evita que es refresqui la pestanya

    const username = document.getElementById("username").value;                                                             //Obtenir els valors de username 
    const password = document.getElementById("password").value;                                                             //i password

    try {
        const userData = await login(username, password);                                                                   //Executa la funcio login i guardem a userData el seu return
        if (userData) {                                                                                                     //Si la hi ha algo a la userData
            displayDashboard(userData);                                                                                     //Mostra el dashboard (Pantalla inicial)
        }
    } catch (error) {                                                                                                       //En cas de haver algun problema amb el inici de funcio es retorna el error adient
        document.getElementById("error-message").innerText = error;
    }
});

async function login(username, password) {                                                                                  //Funcio encarregada de verificar el username i password
    const url = `${API_URL}/students?username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`;    //Creem la url que contindra la pregunta de forma adequada perque el servidor PHP pugui entendre
    const response = await fetch(url, { method: "GET" });                                                                   //Guardem la resposta a response

    if (!response.ok) {                                                                                                     //En cas de que la resposta no estigui correcte
        throw new Error("Login failed");                                                                                    //Enviar Error - Login incorrecte
    }

    const userDataArray = await response.json();                                                                            //Processa la resposta .JSON

    if (userDataArray.length === 0) {
        throw new Error("Invalid username or password");                                                                    //Enviar Error - Username o Password incorrecte
    }

    const userData = userDataArray[0];                                                                                      //Ens quedem el primer element del array (El nostre usuari)

    return userData;                                                                                                        //Retorna el userData del usuari
}

function displayDashboard(userData) {                                                                                       //Mostrar dashboard despres de fer un login correcte
    document.getElementById("login-container").classList.add("hidden");                                                     //Amaga el login container
    document.getElementById("dashboard-container").classList.remove("hidden");                                              //Apareix el dashboard container
    document.getElementById("user-name").textContent = userData.name;                                                       //Donem el nom de usuari al text de Welcome
    const campusPhoto = document.getElementById("campus-photo");                    
    if (campusPhoto) {                                                                                                      //Ocultem la foto del campus     
        campusPhoto.classList.add("hidden");                                    
        campusPhoto.style.visibility = 'hidden';                                                                            //No reservar espai per aquest
        campusPhoto.style.display = 'none';                                                                                 //No ocupar espai en el layout
    }
    window.currentUser = userData;                                                                                          //Guarda les dades del usuario
}

document.getElementById("search-button").addEventListener("click", async function () {                                      //Quan es clica Search
    const query = document.getElementById("query").value.toLowerCase();                                                     //Busca el query
    const studentId = window.currentUser.uid;                                                                               //Agafem la uid del currentUser que hem guardat abans
    const table = document.getElementById("table-selector").value;                                                          //Busca la taula selecionada en la finestra desplegable

    console.log("Selected table:", table);                                                                                  // Verificar el valor en consola
    if (!table) {
        document.getElementById("results-container").innerText = "Please select a table.";                                  //Si no es troba una taula seleccionada enviar el error
        return;                                                                                                             //Sortir i no executar res mes.
    }

    try {
        const data = await fetchTableData(table, studentId, query);                                                         //Funcio per enviar la query de dades
        displayTable(data);                                                                                                 //Funcio per ensenyar les dades en una taula 
    } catch (error) {
        document.getElementById("results-container").innerText = `Error loading data for.` + error;                         //Si es detecta un error es mostra en lloc dels resultats
    }
});

async function fetchTableData(table, studentId, query) {                                                                    //Funcio que donad la taula, el uid i la query, envia la URL i retorna les dades resultants
    const url = `${API_URL}/${encodeURIComponent(table)}?uid=${encodeURIComponent(studentId)}&${(query)}`;                  //Creacio de la URL

    const response = await fetch(url, { method: "GET" });                                                                   //Espera la resposta

    return await response.json();                                                                                           //Retorna la resposta en format JSON 
}

function displayTable(data) {                                                                                               //Funcio que crea i emplena les taules de dades
    const container = document.getElementById("results-container");                                                         //Adquereix la variable del dashboard on volem la taula
    container.innerHTML = "";                                                                                               //S'assegura que estigui buit

    if (Array.isArray(data) && data.length > 0) {                                                                           //S'assegura que les dades son un array i es no null
        const table = document.createElement("table");                                                                      //Creem la taula 'table'
        table.border = "1";

        const thead = document.createElement("thead");                                                                      //Creem el table header 'thead'
        const headerRow = document.createElement("tr");                                                                     //Creem el headerRow                                                 

        const keys = Object.keys(data[0]).filter((key) => key !== 'uid');                                                   //Emplenem la variable keys
        keys.forEach((key) => {                                                                                             //Per cada key crea un th amb el seu valor i afegeix los a headerRow
            const th = document.createElement("th");
            th.textContent = key.toUpperCase();
            headerRow.appendChild(th);
        });

        thead.appendChild(headerRow);                                                                                       //Afegeix headerRow a thead
        table.appendChild(thead);                                                                                           //Afegeix thead a table

        const tbody = document.createElement("tbody");                                                                      //Crear filas de datos

        data.forEach((row) => {                                                                                             //Per cada fila crea un tr (table row)
            const tr = document.createElement("tr");
            keys.forEach((key) => {                                                                                         //Per cada key de cada fila crea un td amb el valor pertinent 
                const td = document.createElement("td");
                td.textContent = row[key];
                tr.appendChild(td);                                                                                         //Afegeix els td al tr de cada fila
            });
            tbody.appendChild(tr);                                                                                          //Afegeix el tr de cada fila a el tbody
        });

        table.appendChild(tbody);                                                                                           //Afegeix el tbody a la table
        container.appendChild(table);                                                                                       //Afegeix la table al container principal

    } else {                                                                                                                //En cas que dades no sigui un array o sigui null
        const th = document.createElement("th");
        th.textContent = "No data available";                                                                               //Apareix aquest error
        container.appendChild(th);                                                                                          //Afegeix el error en el container principal
    }
}

document.getElementById("logout-button").addEventListener("click", function () {                                            //Logout button (tornar a la login-form)
    window.currentUser = null;                                                                                              //Eliminem les dades del user que estava utilitzant la sessio
    document.getElementById("dashboard-container").classList.add("hidden");                                                 //Amaga el dashboard container
    document.getElementById("login-container").classList.remove("hidden");                                                  //Apareix el login container
    document.getElementById("query").value = "";                                                                            //Resetejem el query
    document.getElementById("table-selector").value = "";                                                                   //Resetejem la finestra desplagable
    document.getElementById("results-container").innerHTML = "";                                                            //Resetejem el results container
    const campusPhoto = document.getElementById("campus-photo");
    if (campusPhoto) {                                                                                                      //Resetejar els valors de la imatge perque es torni a veure com al principi
        campusPhoto.classList.remove("hidden");
        campusPhoto.style.visibility = 'visible';
        campusPhoto.style.display = 'flex';
    }
});