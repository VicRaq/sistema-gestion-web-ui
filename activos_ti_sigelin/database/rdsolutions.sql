-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 19-11-2025 a las 05:23:08
-- Versión del servidor: 8.0.40
-- Versión de PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `activosti`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignacion`
--

DROP TABLE IF EXISTS `asignacion`;
CREATE TABLE IF NOT EXISTS `asignacion` (
  `id_asignacion` int NOT NULL AUTO_INCREMENT,
  `fecha_inicio` date NOT NULL,
  `fecha_fin_estimada` date DEFAULT NULL,
  `fecha_fin_real` date DEFAULT NULL,
  `estado` enum('Activo','Finalizado','Pendiente') NOT NULL,
  `tipo_servicio` varchar(100) DEFAULT NULL,
  `id_cliente` int DEFAULT NULL,
  PRIMARY KEY (`id_asignacion`),
  KEY `id_cliente` (`id_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `asignacion`
--

INSERT INTO `asignacion` (`id_asignacion`, `fecha_inicio`, `fecha_fin_estimada`, `fecha_fin_real`, `estado`, `tipo_servicio`, `id_cliente`) VALUES
(9, '2025-11-15', '2025-11-21', '2025-11-16', 'Finalizado', 'Prestamo_Interno', NULL),
(10, '2025-11-15', '2025-11-23', '2025-11-16', 'Finalizado', 'Prestamo_Interno', NULL),
(11, '2025-11-19', '2025-11-23', NULL, 'Finalizado', 'Prestamo_Interno', 5),
(12, '2025-11-19', '2025-11-21', NULL, 'Activo', 'Prestamo_Interno', 6);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

DROP TABLE IF EXISTS `cliente`;
CREATE TABLE IF NOT EXISTS `cliente` (
  `id_cliente` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  `tipo_cliente` varchar(100) DEFAULT NULL,
  `rut` varchar(12) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `rut` (`rut`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`id_cliente`, `nombre`, `tipo_cliente`, `rut`, `telefono`, `direccion`) VALUES
(5, 'Ana Pérez', 'Docente', '12.345.678-9', '+56911111111', 'null'),
(6, 'María González', 'Estudiante', '20.123.456-7', '+56922222222', 'null');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compra`
--

DROP TABLE IF EXISTS `compra`;
CREATE TABLE IF NOT EXISTS `compra` (
  `id_compra` int NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `producto` varchar(255) DEFAULT NULL,
  `cantidad` int DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_compra`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `confiabilidad`
--

DROP TABLE IF EXISTS `confiabilidad`;
CREATE TABLE IF NOT EXISTS `confiabilidad` (
  `id_confiabilidad` int NOT NULL AUTO_INCREMENT,
  `id_equipo` int NOT NULL,
  `porcentaje_fallas` decimal(5,2) DEFAULT NULL,
  `comparacion_hardware` text,
  PRIMARY KEY (`id_confiabilidad`),
  UNIQUE KEY `id_equipo` (`id_equipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalleasignacion`
--

DROP TABLE IF EXISTS `detalleasignacion`;
CREATE TABLE IF NOT EXISTS `detalleasignacion` (
  `id_detalle_asignacion` int NOT NULL AUTO_INCREMENT,
  `id_asignacion` int DEFAULT NULL,
  `id_activo` int DEFAULT NULL,
  PRIMARY KEY (`id_detalle_asignacion`),
  KEY `id_asignacion` (`id_asignacion`),
  KEY `id_activo` (`id_activo`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `detalleasignacion`
--

INSERT INTO `detalleasignacion` (`id_detalle_asignacion`, `id_asignacion`, `id_activo`) VALUES
(9, 9, 15),
(10, 10, 15),
(11, 11, 15),
(12, 12, 15);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalletransaccion`
--

DROP TABLE IF EXISTS `detalletransaccion`;
CREATE TABLE IF NOT EXISTS `detalletransaccion` (
  `id_detalle_transaccion` int NOT NULL AUTO_INCREMENT,
  `id_transaccion` int DEFAULT NULL,
  `nombre_item` varchar(255) DEFAULT NULL,
  `cantidad` int DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_detalle_transaccion`),
  KEY `id_transaccion` (`id_transaccion`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `detalletransaccion`
--

INSERT INTO `detalletransaccion` (`id_detalle_transaccion`, `id_transaccion`, `nombre_item`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 'ok', 21, 54564.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documento`
--

DROP TABLE IF EXISTS `documento`;
CREATE TABLE IF NOT EXISTS `documento` (
  `uuid` varchar(36) NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `ruta_storage` varchar(500) NOT NULL,
  `tipo_mime` varchar(100) DEFAULT NULL,
  `tamano_bytes` int DEFAULT NULL,
  `checksum` varchar(64) DEFAULT NULL,
  `entidad_relacionada_tipo` varchar(50) NOT NULL,
  `entidad_relacionada_id` int NOT NULL,
  `version` int DEFAULT '1',
  `nivel_acceso` enum('publico','interno','restringido') DEFAULT 'interno',
  `creado_por` int DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_eliminacion` datetime DEFAULT NULL,
  PRIMARY KEY (`uuid`),
  KEY `creado_por` (`creado_por`),
  KEY `idx_entidad_relacionada` (`entidad_relacionada_tipo`,`entidad_relacionada_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encargado`
--

DROP TABLE IF EXISTS `encargado`;
CREATE TABLE IF NOT EXISTS `encargado` (
  `id_encargado` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  `rut` varchar(12) NOT NULL,
  `piso_a_cargo` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_encargado`),
  UNIQUE KEY `rut` (`rut`),
  KEY `idx_rut_encargado` (`rut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipo`
--

DROP TABLE IF EXISTS `equipo`;
CREATE TABLE IF NOT EXISTS `equipo` (
  `id_equipo` int NOT NULL AUTO_INCREMENT,
  `tipo` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `estado` enum('Disponible','Asignado','Reparacion','Baja') NOT NULL,
  `lugar_asignado` varchar(255) DEFAULT NULL,
  `id_ubicacion` int DEFAULT NULL,
  `id_usuario` int DEFAULT NULL,
  PRIMARY KEY (`id_equipo`),
  KEY `id_ubicacion` (`id_ubicacion`),
  KEY `id_usuario` (`id_usuario`),
  KEY `idx_tipo_modelo_equipo` (`tipo`,`modelo`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `equipo`
--

INSERT INTO `equipo` (`id_equipo`, `tipo`, `modelo`, `marca`, `estado`, `lugar_asignado`, `id_ubicacion`, `id_usuario`) VALUES
(3, 'kjkjk', 'hj', 'jhj', 'Asignado', 'kjkjkj', NULL, NULL),
(5, 'oko9', 'hj', 'jhj', 'Asignado', 'kjkjkj', NULL, NULL),
(8, 'testing1', '431', '12', 'Reparacion', '123', NULL, NULL),
(15, 'testing1', '431', '12', 'Asignado', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logeliminaciontransaccion`
--

DROP TABLE IF EXISTS `logeliminaciontransaccion`;
CREATE TABLE IF NOT EXISTS `logeliminaciontransaccion` (
  `id_log` int NOT NULL AUTO_INCREMENT,
  `id_transaccion` int DEFAULT NULL,
  `nombre_item` varchar(255) DEFAULT NULL,
  `cantidad` int DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `eliminado_por` varchar(100) DEFAULT NULL,
  `fecha_eliminacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `logeliminaciontransaccion`
--

INSERT INTO `logeliminaciontransaccion` (`id_log`, `id_transaccion`, `nombre_item`, `cantidad`, `precio_unitario`, `total`, `motivo`, `eliminado_por`, `fecha_eliminacion`) VALUES
(1, 2, '1321', 121, 1231.00, 148951.00, 'Eliminado manualmente desde transacciones.php', 'admin', '2025-11-06 02:41:22'),
(2, 3, 'ok', 12, 12321.00, 147852.00, 'Eliminado manualmente desde transacciones.php', 'admin', '2025-11-14 02:16:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `piezahardware`
--

DROP TABLE IF EXISTS `piezahardware`;
CREATE TABLE IF NOT EXISTS `piezahardware` (
  `id_pieza` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  `cantidad` int NOT NULL,
  `estado` enum('Nuevo','Usado','Agotado') DEFAULT NULL,
  PRIMARY KEY (`id_pieza`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `piezahardware`
--

INSERT INTO `piezahardware` (`id_pieza`, `nombre`, `cantidad`, `estado`) VALUES
(2, '2', 12, 'Usado'),
(3, 'deded', 88, 'Agotado'),
(5, 'u99', 12, 'Nuevo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `qr`
--

DROP TABLE IF EXISTS `qr`;
CREATE TABLE IF NOT EXISTS `qr` (
  `id_qr` int NOT NULL AUTO_INCREMENT,
  `id_equipo` int NOT NULL,
  `codigo_qr` varchar(255) NOT NULL,
  PRIMARY KEY (`id_qr`),
  UNIQUE KEY `id_equipo` (`id_equipo`),
  UNIQUE KEY `codigo_qr` (`codigo_qr`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `qr`
--

INSERT INTO `qr` (`id_qr`, `id_equipo`, `codigo_qr`) VALUES
(6, 3, '657647'),
(8, 5, '657649'),
(16, 8, '1232'),
(24, 15, '87869');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reparacion`
--

DROP TABLE IF EXISTS `reparacion`;
CREATE TABLE IF NOT EXISTS `reparacion` (
  `id_reparacion` int NOT NULL AUTO_INCREMENT,
  `id_equipo` int NOT NULL,
  `id_encargado` int DEFAULT NULL,
  `fecha` date NOT NULL,
  `tipo_falla` varchar(100) DEFAULT NULL,
  `estado` enum('Pendiente','En Progreso','Finalizada','Cancelada') NOT NULL,
  `descripcion` text,
  PRIMARY KEY (`id_reparacion`),
  KEY `id_encargado` (`id_encargado`),
  KEY `idx_equipo_encargado_reparacion` (`id_equipo`,`id_encargado`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `reparacion`
--

INSERT INTO `reparacion` (`id_reparacion`, `id_equipo`, `id_encargado`, `fecha`, `tipo_falla`, `estado`, `descripcion`) VALUES
(8, 3, 9, '2025-11-28', 'KJ', 'Finalizada', 'J'),
(9, 3, 6, '2025-11-19', 'jhjh', 'Finalizada', 'kknk'),
(11, 8, 11, '2026-11-12', 'error de software', 'Pendiente', 'frfr');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transaccion`
--

DROP TABLE IF EXISTS `transaccion`;
CREATE TABLE IF NOT EXISTS `transaccion` (
  `id_transaccion` int NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `metodo_pago` enum('Efectivo','Tarjeta','Transferencia','Otro') DEFAULT NULL,
  `id_cliente` int DEFAULT NULL,
  `id_usuario_sistema` int DEFAULT NULL,
  `tipo_transaccion` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_transaccion`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_usuario_sistema` (`id_usuario_sistema`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `transaccion`
--

INSERT INTO `transaccion` (`id_transaccion`, `fecha`, `total`, `metodo_pago`, `id_cliente`, `id_usuario_sistema`, `tipo_transaccion`) VALUES
(1, '2025-11-05', 1145844.00, NULL, NULL, NULL, 'Servicio_Externo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ubicacion`
--

DROP TABLE IF EXISTS `ubicacion`;
CREATE TABLE IF NOT EXISTS `ubicacion` (
  `id_ubicacion` int NOT NULL AUTO_INCREMENT,
  `laboratorio` varchar(100) DEFAULT NULL,
  `piso` varchar(50) DEFAULT NULL,
  `sala` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_ubicacion`),
  UNIQUE KEY `idx_ubicacion_unica` (`laboratorio`,`piso`,`sala`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

DROP TABLE IF EXISTS `usuario`;
CREATE TABLE IF NOT EXISTS `usuario` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `rol` varchar(50) NOT NULL,
  `estado_infraestructura` enum('operativo','mantenimiento','incidente') DEFAULT 'operativo',
  PRIMARY KEY (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuariosistema`
--

DROP TABLE IF EXISTS `usuariosistema`;
CREATE TABLE IF NOT EXISTS `usuariosistema` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  `correo_electronico` varchar(200) NOT NULL,
  `contrasena_hash` varchar(255) NOT NULL,
  `rol` varchar(50) DEFAULT 'empleado',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `correo_electronico` (`correo_electronico`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuariosistema`
--

INSERT INTO `usuariosistema` (`id_usuario`, `nombre`, `correo_electronico`, `contrasena_hash`, `rol`) VALUES
(14, 'María López', 'maria.lopez@sigelin.com', '$2y$10$EH35/wcm5bOo8G0XZv9PH.ohdWxNALCb28GB3GDDQNLmxw.LHauUe', 'administrador'),
(15, 'Carlos Martínez', 'carlos.martinez@sigelin.com', '$2y$10$74IeWJI.JB6FWqh9XoWYOuQBi3Twd6KT6OhVfE8dFSqYimukbvKKm', 'tecnico'),
(16, 'Laura Rojas', 'laura.rojas@sigelin.com', '$2y$10$JOuFaR2CfryYcj83/MS6CODRbhJZ8t3SVL/ACPE4YZy6qHeESVcmy', 'compras'),
(17, 'Equipo DevQA', 'devops@sigelin.com', '$2y$10$Ms.usgrv6vDUKzxI3eHmr.8X/B1v4DnZEDfnKqWcgGL1HUsDd5Wim', 'devqa');

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asignacion`
--
ALTER TABLE `asignacion`
  ADD CONSTRAINT `asignacion_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE SET NULL;

--
-- Filtros para la tabla `confiabilidad`
--
ALTER TABLE `confiabilidad`
  ADD CONSTRAINT `confiabilidad_ibfk_1` FOREIGN KEY (`id_equipo`) REFERENCES `equipo` (`id_equipo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalleasignacion`
--
ALTER TABLE `detalleasignacion`
  ADD CONSTRAINT `detalleasignacion_ibfk_1` FOREIGN KEY (`id_asignacion`) REFERENCES `asignacion` (`id_asignacion`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalleasignacion_ibfk_2` FOREIGN KEY (`id_activo`) REFERENCES `equipo` (`id_equipo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalletransaccion`
--
ALTER TABLE `detalletransaccion`
  ADD CONSTRAINT `detalletransaccion_ibfk_1` FOREIGN KEY (`id_transaccion`) REFERENCES `transaccion` (`id_transaccion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `documento`
--
ALTER TABLE `documento`
  ADD CONSTRAINT `documento_ibfk_1` FOREIGN KEY (`creado_por`) REFERENCES `encargado` (`id_encargado`) ON DELETE SET NULL;

--
-- Filtros para la tabla `equipo`
--
ALTER TABLE `equipo`
  ADD CONSTRAINT `equipo_ibfk_1` FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicacion` (`id_ubicacion`) ON DELETE SET NULL,
  ADD CONSTRAINT `equipo_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL;

--
-- Filtros para la tabla `qr`
--
ALTER TABLE `qr`
  ADD CONSTRAINT `qr_ibfk_1` FOREIGN KEY (`id_equipo`) REFERENCES `equipo` (`id_equipo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reparacion`
--
ALTER TABLE `reparacion`
  ADD CONSTRAINT `reparacion_ibfk_1` FOREIGN KEY (`id_equipo`) REFERENCES `equipo` (`id_equipo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `transaccion`
--
ALTER TABLE `transaccion`
  ADD CONSTRAINT `transaccion_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE SET NULL,
  ADD CONSTRAINT `transaccion_ibfk_2` FOREIGN KEY (`id_usuario_sistema`) REFERENCES `usuariosistema` (`id_usuario`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
