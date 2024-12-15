<?php
$servername = "localhost";  //Parametres de la base de dades
$username = "root";
$password = "1234";
$dbname = "nemesis";

header("Content-Type: application/json");           //Capçalera per evitar el CORS error 
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$conn = new mysqli($servername, $username, $password, $dbname);     //Connexio amb la base de dades
if ($conn->connect_error) {
    http_response_code(500);    //Error de servidor - Conexio Fallida
    die(json_encode(["error" => "Conexion fallida: " . $conn->connect_error]));
}


if($_SERVER['REQUEST_METHOD'] === 'OPTIONS'){   //Quan demana el tipus OPTIONS que faci un redirecionament 
    http_response_code(204);
    exit;
}

session_start();        //Inici de sessio
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {     //15 minutos de inactividad
    session_unset();
    session_destroy();
    http_response_code(401);    //Error de client - Temps Maxim de inactivitat
    die(json_encode(["error" => "Sesion expirada."]));
}
$_SESSION['last_activity'] = time();

$uid = $_GET['uid'] ?? null;    //Validar UID
if (!$uid) {
    $username = $_GET['username'] ?? null;
    if (!$username) {
        http_response_code(402);    //Error de client - UID o Username no trobat
        die(json_encode(["error" => "UID no proporcionado."]));
    }
}

$query = $_SERVER['QUERY_STRING'] ?? null;  //Comprobar si QUERY_STRING esta definida

$request_uri = $_SERVER['REQUEST_URI'] ?? '';   //Separar la taula de las restriccions
$request_parts = explode('?', $request_uri, 2);     //Partir el string central a partir del '?'
$table = ltrim($request_parts[0], '/');     //La primera part es la taula

$allowed_tables = ['marks', 'tasks', 'timetables', 'students'];     //Validar taula
if (!$table || !in_array($table, $allowed_tables)) {
    http_response_code(403);            //Error de Client - Taula donada invalida
    die(json_encode(["error" => "Tabla no valida."]));
}

$params = [];   //Inicialitzar la variable que contindra les restriccions
if (!empty($request_parts[1])) {    //La segona part son les restriccions
    parse_str($request_parts[1], $params);      //Dividim les restriccions individualment en el params
}

$operator_map = [   //Mapejar els operadors
    'lt'  => '<',
    'lte' => '<=',
    'gt'  => '>',
    'gte' => '>=',
    'eq'  => '='
];

$constraints = [];  //Inicialitzacio de la variable que tindra els constraints
$order_by = "";     //Inicialitzacio de la variable que tindra el ordre
$limit = "";        //Inicialitzacio de la variable que tindra el limit

foreach ($params as $column => $conditions) {   //Analisis de cada condicio
    if (is_array($conditions)) {                //Separar las restricciones directas
        foreach ($conditions as $operator => $value) {
            if (isset($operator_map[$operator])) {      //Comprovar els operadors
                if ($value === 'now') {     //Manejar valores especiales como 'now'
                    $value = (strpos($column, 'date') !== false) ? date('Y-m-d') : date('H:i'); 
                }
                $constraints[] = $column . " " . $operator_map[$operator] . " '" . $conn->real_escape_string($value) . "'";   //Definicio del constrait
            }
        }
    } else {
        $constraints[] = $column . " = '" . $conn->real_escape_string($conditions) . "'";   //Definicio del contrait en condicio directe
    }
}

if (isset($params['limit']) && is_numeric($params['limit'])) {  //Procesar el parametre limit
    $limit = "LIMIT " . intval($params['limit']);
}


switch ($table) {   //Ordre per defecte de cada taula
    case 'marks':
        $order_by = "ORDER BY subject";     //Taula marks - Ordre per subject
        break;
    case 'tasks':
        $order_by = "ORDER BY date";        //Taula tasks - Ordre per dia
        break;
    case 'timetables':                      //Taula timetables - Ordre per dia/hora a partir de la actual
        $current_day = date('D');           //Formato: Mon, Tue, Wed...
        $current_time = date('H:i:s');      //Formato: HH:MM:SS
        $hora = explode(':', $current_time, 3);     //Separar la hora 
        if($hora[1] > '00'){                        //Mirem si el minut es mes gran de 00
            $hora_actual = $hora[0] + '02';         //+2 Per truncar la hora
        } else {
            $hora_actual = $hora[0] + '01';         //+1 Per la hora desfassada
        }
        $days_map = [       //Mapejar els dies
            'Mon' => 1,
            'Tue' => 2,
            'Wed' => 3,
            'Thu' => 4,
            'Fri' => 5,
            'Sat' => 6,
            'Sun' => 7
        ];

        $current_day_index = $days_map[$current_day];       //Dia Actual (Numerico)
        $order_by = "ORDER BY           
        CASE 
            WHEN day = '$current_day' AND hour >= '$hora_actual' THEN 0
            WHEN day = '$current_day' AND hour < '$hora_actual' THEN 7
            ELSE (
                CASE day
                    WHEN 'Mon' THEN (7 + 1 - $days_map[$current_day]) % 7
                    WHEN 'Tue' THEN (7 + 2 - $days_map[$current_day]) % 7
                    WHEN 'Wed' THEN (7 + 3 - $days_map[$current_day]) % 7
                    WHEN 'Thu' THEN (7 + 4 - $days_map[$current_day]) % 7
                    WHEN 'Fri' THEN (7 + 5 - $days_map[$current_day]) % 7
                    WHEN 'Sat' THEN (7 + 6 - $days_map[$current_day]) % 7
                    WHEN 'Sun' THEN (7 + 7 - $days_map[$current_day]) % 7
                END
            )
        END,
        hour";
        break;
    case 'students':    //Taula students - No s'ordena
        break;
}

if (isset($params['limit']) && is_numeric($params['limit'])) {  //Procesar el parametre limit
    $limit = "LIMIT " . intval($params['limit']);
}

$sql = "SELECT * FROM $table";  //Construir la consulta SQL

if (!empty($constraints)) {     //Comprobar si hi han restriccions
    $constraints = array_filter($constraints, function($constraint) {
        return !str_contains($constraint, 'limit');         //Eliminar 'limit' de les restriccions
    });

    if (!empty($constraints)) {
        $sql .= " WHERE " . implode(" AND ", $constraints); //Afegir les restriccions que encara no s'han afegit
    }
}

$sql .= " $order_by";   //Implementar el ORDER

$sql .= " $limit";      //Implementar el LIMIT

//echo "SQL Query: " . $sql . "\n";     //Per poder veure per pantalla la consulta realitzada

$result = $conn->query($sql);   //Ejecutar la consulta

if (!$result) {
    http_response_code(501);        //Error de servidor - Missatge Erroni
    die(json_encode(["error" => "Consulta SQL fallida: " . $conn->error]));
}

$data = [];         
while ($row = $result->fetch_assoc()) {     //Recull les dades
    $data[] = $row;
}

header('Content-Type: application/json');   //Resultados en JaSON Derulo
echo json_encode($data, JSON_PRETTY_PRINT);

$conn->close();
?>