const API_URL = "http://192.168.1.74:8000"; // URL base del servidor PHP

// Manejo del login
document.getElementById("login-form").addEventListener("submit", async function (event) {
    event.preventDefault(); // Evita que el formulario recargue la página

    const username = document.getElementById("username").value;
    const password = document.getElementById("password").value;

    try {
        const userData = await login(username, password);
        if (userData) {
            displayDashboard(userData); // Muestra el dashboard
        }
    } catch (error) {
        document.getElementById("error-message").innerText = error;
    }
});

// Función para iniciar sesión
async function login(username, password) {
    const url = `${API_URL}/students?username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`;
    const response = await fetch(url, { method: "GET" });

    if (!response.ok) {
        throw new Error("Login failed");
    }

    const userDataArray = await response.json(); // Procesa la respuesta JSON

    if (userDataArray.length === 0) {
        throw new Error("Invalid username or password");
    }

    const userData = userDataArray[0];

    return userData; // Devuelve los datos del usuario autenticado
}

// Mostrar el dashboard después de un login exitoso
function displayDashboard(userData) {
    document.getElementById("login-container").classList.add("hidden"); // Oculta el contenedor de login
    document.getElementById("dashboard-container").classList.remove("hidden"); // Muestra el dashboard
    document.getElementById("user-name").textContent = userData.name; // Muestra el nombre del usuario
    const campusPhoto = document.getElementById("campus-photo");
    if (campusPhoto) {
        campusPhoto.classList.add("hidden"); // Ocultar completamente el contenedor
        campusPhoto.style.visibility = 'hidden'; // No reservar espacio
        campusPhoto.style.display = 'none'; // No ocupar espacio en el layout
    }
    window.currentUser = userData; // Guarda los datos del usuario para futuras solicitudes
}

// Manejo de la búsqueda de datos
document.getElementById("search-button").addEventListener("click", async function () {
    const query = document.getElementById("query").value.toLowerCase(); // Obtiene la consulta del usuari
    const studentId = window.currentUser.uid; // ID del usuario autenticado
    const table = document.getElementById("table-selector").value; // Obtiene la tabla seleccionada

    console.log("Selected table:", table); // Verificar el valor en consola
    if (!table) {
        document.getElementById("results-container").innerText = "Please select a table.";
        return; // Salir si no se seleccionó ninguna tabla
    }

    try {
        const data = await fetchTableData(table, studentId, query);
        displayTable(data);
    } catch (error) {
        document.getElementById("results-container").innerText = `Error loading data for.` + error;
    }
});

// Función para obtener los datos de la tabla seleccionada
async function fetchTableData(table, studentId, query) {

    const url = `${API_URL}/${encodeURIComponent(table)}?uid=${encodeURIComponent(studentId)}&${encodeURIComponent(query)}`;

    const response = await fetch(url, { method: "GET" });

    return await response.json(); // Procesa la respuesta JSON
}

// Función para mostrar las tablas
function displayTable(data) {
    const container = document.getElementById("results-container");
    container.innerHTML = ""; // Limpia cualquier contenido previo

    if (Array.isArray(data) && data.length > 0) {
        const table = document.createElement("table");
        table.border = "1";

        // Crear encabezados
        const thead = document.createElement("thead");
        const headerRow = document.createElement("tr");

        // Utiliza las claves del primer objeto como encabezados, excluiendo la columna 'uid' (no nos interesa mostrarla)
        const keys = Object.keys(data[0]).filter((key) => key !== 'uid');
        keys.forEach((key) => {
            const th = document.createElement("th");
            th.textContent = key.toUpperCase();
            headerRow.appendChild(th);
        });

        thead.appendChild(headerRow);
        table.appendChild(thead);

        // Crear filas de datos
        const tbody = document.createElement("tbody");

        data.forEach((row) => {
            const tr = document.createElement("tr");
            keys.forEach((key) => {
                const td = document.createElement("td");
                td.textContent = row[key];
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });

        table.appendChild(tbody);
        container.appendChild(table);
    } else {
        const th = document.createElement("th");
        th.textContent = "No data available";
        container.appendChild(th);
    }
}

// Manejo del logout
document.getElementById("logout-button").addEventListener("click", function () {
    window.currentUser = null; // Limpia los datos del usuario actual
    document.getElementById("dashboard-container").classList.add("hidden"); // Oculta el dashboard
    document.getElementById("login-container").classList.remove("hidden"); // Muestra el contenedor de login
    document.getElementById("query").value = ""; // Limpia el campo de consulta
    document.getElementById("results-container").innerHTML = ""; // Limpia los resultados
    const campusPhoto = document.getElementById("campus-photo");
    if (campusPhoto) {
        campusPhoto.classList.remove("hidden");
        campusPhoto.style.visibility = 'visible'; // Restaurar la visibilidad
        campusPhoto.style.display = 'flex'; // Restaurar espacio
    }
});