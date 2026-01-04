<?php
// 1. GUARDIA DE AUTENTICACIÓN (¿Estás logueado?)
require_once 'includes/auth_check.php'; 

// 2. Cargar Clases (Necesarias para RBAC y Conexión)
require_once 'classes/user.php'; 
require_once 'classes/database.php';

// 3. Instanciar Clases
$objUsuarioSistema = new UsuarioSistema(); 
$db = new Database();
$conn = $db->dbConnection();

// 4. GUARDIA DE AUTORIZACIÓN (RBAC)
// (Según el sidebar, 'administrador', 'tecnico' y 'compras' pueden exportar)
if (!in_array($CURRENT_USER_ROL, ['administrador', 'tecnico', 'compras'])) {
    $objUsuarioSistema->redirect('index.php?error=acceso_denegado_accion');
    exit;
}

// --- Si pasó la seguridad, proceder con la exportación ---

// Cabeceras para forzar la descarga de un archivo CSV 
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=transacciones_exportadas_' . date('Ymd_His') . '.csv');

// Abrir el buffer de salida
$output = fopen('php://output', 'w');

// Obtener los datos de las transacciones 
$stmt = $conn->prepare("
    SELECT
        t.id_transaccion, t.fecha, t.tipo_transaccion, dt.nombre_item, dt.cantidad, dt.precio_unitario, t.total, t.id_usuario_sistema
    FROM
        detalletransaccion dt
    INNER JOIN transaccion t ON dt.id_transaccion = t.id_transaccion
    ORDER BY t.fecha DESC
");
$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Escribir el encabezado del archivo CSV
$encabezado = [
    'ID_Transaccion',
    'Fecha',
    'Tipo_Transaccion',
    'Item/Servicio',
    'Cantidad',
    'Precio_Unitario',
    'Total',
    'Registrado_Por_ID'
];
fputcsv($output, $encabezado);

// Escribir las filas de datos
foreach ($resultados as $fila) {
    // Formateo de números para asegurar compatibilidad CSV (punto decimal)
    $fila['precio_unitario'] = number_format($fila['precio_unitario'], 2, '.', '');
    $fila['total'] = number_format($fila['total'], 2, '.', '');
    fputcsv($output, $fila);
}

// Cerrar el buffer
fclose($output);
exit();
?>