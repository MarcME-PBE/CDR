<?php
$servername = "localhost";  //Configuracion inicial
$username = "root";
$password = "1234";
$dbname = "atenea";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500); //Error de servidor - Conexion Fallida
    die(json_encode(["error" => "Conexion fallida: " . $conn->connect_error]));
}


session_start();    // Inicio de sesion
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) { //15 minutos de inactividad
    session_unset();
    session_destroy();
    http_response_code(401);    //Error de cliente - Tiempo Maximo de inactividad superado
    die(json_encode(["error" => "Sesion expirada."]));
}
$_SESSION['last_activity'] = time();


$uid = $_GET['uid'] ?? null;    //Validar UID
if (!$uid) {
    http_response_code(400);    //Error de cliente - UID no encontrada
    die(json_encode(["error" => "UID no proporcionado."]));
}

$student_check = $conn->query("SELECT name FROM students WHERE uid = '" . $conn->real_escape_string($uid) . "'");
if ($student_check->num_rows === 0) {
    http_response_code(404);    //Error de cliente - UID no registrada
    die(json_encode(["error" => "UID no encontrado en la base de datos."]));
}
$student_name = $student_check->fetch_assoc()['name'];
echo "Bienvenido, $student_name.\n";

$query = $_SERVER['QUERY_STRING'] ?? null;  //Comprobar si QUERY_STRING esta definida

$request_uri = $_SERVER['REQUEST_URI'] ?? '';   //Separar la tabla de las restricciones
$request_parts = explode('?', $request_uri, 2);     //Partir el string central con el '?'
$table = ltrim($request_parts[0], '/');     //La primera parte es la tabla

$allowed_tables = ['marks', 'tasks', 'timetables'];     //Validar tabla
if (!$table || !in_array($table, $allowed_tables)) {
    http_response_code(400);            //Error de Cliente - Tabla Introducida Invalida
    die(json_encode(["error" => "Tabla no valida."]));
}

$params = [];   //Analizar las restricciones si existen
if (!empty($request_parts[1])) {    //La segunda parte son las restricciones
    parse_str($request_parts[1], $params);      //Dividimos las restricciones
}

$operator_map = [   //Mapeo de operadores soportados
    'lt'  => '<',
    'lte' => '<=',
    'gt'  => '>',
    'gte' => '>=',
    'eq'  => '='
];

$constraints = [];  //Procesar restricciones
$order_by = "";
$limit = "";

foreach ($params as $column => $conditions) {   //Analisis de cada condicion o restriccion
    if (is_array($conditions)) {                //Separar las restricciones directas
        foreach ($conditions as $operator => $value) {
            if (isset($operator_map[$operator])) {
                if ($value === 'now') {     //Manejar valores especiales como 'now'
                    $value = (strpos($column, 'date') !== false) ? date('Y-m-d') : date('H:i');
                }
                $constraints[] = $column . " " . $operator_map[$operator] . " '" . $conn->real_escape_string($value) . "'";
            }
        }
    } else {
        $constraints[] = $column . " = '" . $conn->real_escape_string($conditions) . "'";   //Restriccion directa
    }
}

if (isset($params['limit']) && is_numeric($params['limit'])) {  //Procesar el parametro limit
    $limit = "LIMIT " . intval($params['limit']);
}



switch ($table) {   //Ordenamiento por defecto (marks en subjects, tasks por date i timetables por dia/hora)
    case 'marks':
        $order_by = "ORDER BY subject";
        break;
    case 'tasks':
        $order_by = "ORDER BY date";
        break;
    case 'timetables':          //Agregar la ordenacion correcta por dia de la semana i hora
        $order_by = "ORDER BY 
            CASE day                
                WHEN 'Mon' THEN 1
                WHEN 'Tue' THEN 2
                WHEN 'Wed' THEN 3
                WHEN 'Thu' THEN 4
                WHEN 'Fri' THEN 5
                WHEN 'Sat' THEN 6
                WHEN 'Sun' THEN 7
                ELSE 8 
            END, hour"; //Primero ordena por dia, luego por hora
        break;
}

if (isset($params['limit']) && is_numeric($params['limit'])) {  //Procesar el parametro limit
    $limit = "LIMIT " . intval($params['limit']);
}

$sql = "SELECT * FROM $table";  //Construir la consulta SQL

if (!empty($constraints)) {     //Comprobar si hay restricciones y procesarlas
    if($table !== 'marks'){     //Si no es la tabla 'marks'
        $constraints = array_filter($constraints, function($constraint) {
            return !str_contains($constraint, 'uid');       //Eliminar 'uid' de las restricciones
        });
    }
    
    $constraints = array_filter($constraints, function($constraint) {
        return !str_contains($constraint, 'limit');         //Eliminar 'limit' de las restricciones
    });

    
    if (!empty($constraints)) {
        $sql .= " WHERE " . implode(" AND ", $constraints); //Si hay otras restricciones, agregarlas
    }
}

$sql .= " $order_by";   //Ordenar como es pedido

$sql .= " $limit";      //Poner el limite (si se necessita)

//echo "SQL Query: " . $sql . "\n";     //Verificar codigo SQL

$result = $conn->query($sql);   //Ejecutar la codigo SQL

if (!$result) {
    http_response_code(501); //Error de servidor - Mensaje Fallido
    die(json_encode(["error" => "Consulta SQL fallida: " . $conn->error]));
}

$data = [];         
while ($row = $result->fetch_assoc()) {     //Recoger resultados
    $data[] = $row;
}

header('Content-Type: application/json');   //Resultados en JaSON Derulo
echo json_encode($data, JSON_PRETTY_PRINT);

$conn->close();
?>

