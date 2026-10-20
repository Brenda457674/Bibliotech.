-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-10-2026 a las 16:55:44
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `bibliotech`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `created_at`, `updated_at`) VALUES
(1, 'Ingeniería de Software', 'Libros sobre desarrollo, arquitectura y metodologías.', '2026-10-12 00:40:14', '2026-10-12 00:40:14'),
(2, 'Bases de Datos', 'Diseño, administración y optimización de BD.', '2026-10-12 00:40:14', '2026-10-12 02:38:42'),
(3, 'Redes y Seguridad', 'Infraestructura, protocolos y ciberseguridad.', '2026-10-12 00:40:14', '2026-10-12 00:40:14'),
(4, 'Matemáticas', 'Álgebra, cálculo, estadística y discretas.', '2026-10-12 00:40:14', '2026-10-12 00:40:14'),
(5, 'Literatura', 'Novela, poesía, ensayo y teatro.', '2026-10-12 00:40:14', '2026-10-12 00:40:14'),
(6, 'Historia', 'Historia universal, latinoamericana y del Perú.', '2026-10-12 00:40:14', '2026-10-12 00:40:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `libros`
--

CREATE TABLE `libros` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `autor` varchar(180) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `id_categoria` bigint(20) UNSIGNED DEFAULT NULL,
  `editorial` varchar(150) DEFAULT NULL,
  `anio` smallint(5) UNSIGNED DEFAULT NULL,
  `portada_url` varchar(500) DEFAULT NULL,
  `sinopsis` text DEFAULT NULL,
  `stock_total` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `stock_disponible` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Volcado de datos para la tabla `libros`
--

INSERT INTO `libros` (`id`, `titulo`, `autor`, `isbn`, `id_categoria`, `editorial`, `anio`, `portada_url`, `sinopsis`, `stock_total`, `stock_disponible`, `created_at`, `updated_at`) VALUES
(1, 'Clean Code', 'Robert C. Martin', '9780132350884', 1, 'Prentice Hall', 2008, 'https://covers.openlibrary.org/b/isbn/9780132350884-L.jpg', 'Principios y buenas prácticas para escribir código limpio y mantenible.', 5, 5, '2026-10-12 00:40:14', '2026-10-12 01:55:38'),
(2, 'The Pragmatic Programmer', 'Andrew Hunt y David Thomas', '9780201616224', 1, 'Addison-Wesley', 1999, 'https://covers.openlibrary.org/b/isbn/9780201616224-L.jpg', 'Consejos prácticos para el desarrollo profesional de software.', 4, 4, '2026-10-12 00:40:14', '2026-10-12 00:40:14'),
(3, 'Design Patterns', 'Erich Gamma et al.', '9780201633610', 1, 'Addison-Wesley', 1994, 'https://covers.openlibrary.org/b/isbn/9780201633610-L.jpg', 'Catálogo clásico de patrones de diseño orientado a objetos.', 3, 3, '2026-10-12 00:40:14', '2026-10-12 00:40:14'),
(4, 'Database System Concepts', 'Abraham Silberschatz', '9780078022159', 2, 'McGraw-Hill', 2019, 'https://covers.openlibrary.org/b/isbn/9780078022159-L.jpg', 'Fundamentos de sistemas de bases de datos relacionales y distribuidas.', 6, 6, '2026-10-12 00:40:14', '2026-10-12 00:40:14'),
(5, 'SQL Antipatterns', 'Bill Karwin', '9781934356555', 2, 'Pragmatic Bookshelf', 2010, 'https://covers.openlibrary.org/b/isbn/9781934356555-L.jpg', 'Errores comunes al diseñar bases de datos y cómo evitarlos.', 3, 3, '2026-10-12 00:40:14', '2026-10-12 00:40:14'),
(6, 'Computer Networking: A Top-Down Approach', 'James Kurose y Keith Ross', '9780133594140', 3, 'Pearson', 2017, 'https://covers.openlibrary.org/b/isbn/9780133594140-L.jpg', 'Introducción a redes desde la capa de aplicación hasta la física.', 4, 4, '2026-10-12 00:40:14', '2026-10-12 00:40:14'),
(7, 'The Web Application Hacker\'s Handbook', 'Dafydd Stuttard y Marcus Pinto', '9781118026472', 3, 'Wiley', 2011, 'https://covers.openlibrary.org/b/isbn/9781118026472-L.jpg', 'Guía práctica para encontrar y explotar vulnerabilidades web.', 2, 2, '2026-10-12 00:40:14', '2026-10-12 00:40:14'),
(8, 'Cálculo de Una Variable', 'James Stewart', '9781285740621', 4, 'Cengage', 2015, 'https://covers.openlibrary.org/b/isbn/9781285740621-L.jpg', 'Introducción al cálculo diferencial e integral.', 8, 8, '2026-10-12 00:40:14', '2026-10-12 01:14:35'),
(10, 'Cien Años de Soledad', 'Gabriel García Márquez', '9780307474728', 5, 'Vintage Español', 1967, 'https://covers.openlibrary.org/b/isbn/9780307474728-L.jpg', 'Novela cumbre del realismo mágico latinoamericano.', 6, 6, '2026-10-12 00:40:14', '2026-10-12 00:40:14'),
(11, 'La Ciudad y los Perros', 'Mario Vargas Llosa', '9788420471839', 5, 'Alfaguara', 1963, 'https://covers.openlibrary.org/b/isbn/9788420471839-L.jpg', 'Retrato crudo de la vida en un colegio militar peruano.', 4, 4, '2026-10-12 00:40:14', '2026-10-12 00:40:14'),
(12, 'Historia del Tahuantinsuyo', 'María Rostworowski', '9789972510953', 6, 'IEP', 1988, 'https://covers.openlibrary.org/b/isbn/9789972510953-L.jpg', 'Estudio fundamental sobre el Imperio Inca.', 3, 3, '2026-10-12 00:40:14', '2026-10-12 00:40:14'),
(13, 'Refactoring', 'Martin Fowler', '9780134757599', 1, 'Addison-Wesley', 2018, 'https://covers.openlibrary.org/b/isbn/9780134757599-L.jpg', 'Técnicas para mejorar el diseño de código existente sin cambiar su comportamiento.', 4, 4, '2026-10-12 14:42:24', '2026-10-12 14:42:24'),
(14, 'Working Effectively with Legacy Code', 'Michael C. Feathers', '9780131177055', 1, 'Prentice Hall', 2004, 'https://covers.openlibrary.org/b/isbn/9780131177055-L.jpg', 'Estrategias para modificar y probar código heredado sin romperlo.', 3, 3, '2026-10-12 14:42:24', '2026-10-12 14:42:24'),
(15, 'The Clean Coder', 'Robert C. Martin', '9780137081073', 1, 'Prentice Hall', 2011, 'https://covers.openlibrary.org/b/isbn/9780137081073-L.jpg', 'Disciplina, ética y profesionalismo en el desarrollo de software.', 4, 4, '2026-10-12 14:42:24', '2026-10-12 14:42:24'),
(16, 'Domain-Driven Design', 'Eric Evans', '9780321125217', 1, 'Addison-Wesley', 2003, 'https://covers.openlibrary.org/b/isbn/9780321125217-L.jpg', 'Modelado del dominio para resolver problemas complejos de negocio.', 2, 2, '2026-10-12 14:42:24', '2026-10-12 14:42:24'),
(17, 'Learning SQL', 'Alan Beaulieu', '9781492057611', 2, 'O\'Reilly Media', 2020, 'https://covers.openlibrary.org/b/isbn/9781492057611-L.jpg', 'Guía práctica para dominar SQL desde cero hasta consultas avanzadas.', 5, 5, '2026-10-12 14:42:24', '2026-10-12 14:42:24'),
(18, 'High Performance MySQL', 'Baron Schwartz', '9781492098768', 2, 'O\'Reilly Media', 2021, 'https://covers.openlibrary.org/b/isbn/9781492098768-L.jpg', 'Optimización, replicación y ajuste de rendimiento en MySQL.', 3, 3, '2026-10-12 14:42:24', '2026-10-12 14:42:24'),
(19, 'NoSQL Distilled', 'Pramod J. Sadalage y Martin Fowler', '9780321826626', 2, 'Addison-Wesley', 2012, 'https://covers.openlibrary.org/b/isbn/9780321826626-L.jpg', 'Introducción a las bases de datos NoSQL y cuándo usarlas.', 2, 2, '2026-10-12 14:42:24', '2026-10-12 14:42:24'),
(20, 'Computer Security: Principles and Practice', 'William Stallings', '9780134794105', 3, 'Pearson', 2018, 'https://covers.openlibrary.org/b/isbn/9780134794105-L.jpg', 'Principios y prácticas fundamentales de seguridad informática.', 4, 4, '2026-10-12 14:42:24', '2026-10-12 14:42:24'),
(21, 'Network Security Essentials', 'William Stallings', '9780134527338', 3, 'Pearson', 2017, 'https://covers.openlibrary.org/b/isbn/9780134527338-L.jpg', 'Fundamentos de seguridad en redes, criptografía y protocolos.', 3, 3, '2026-10-12 14:42:24', '2026-10-12 14:42:24'),
(22, 'Hacking Exposed 7', 'Stuart McClure', '9780071780285', 3, 'McGraw-Hill', 2012, 'https://covers.openlibrary.org/b/isbn/9780071780285-L.jpg', 'Técnicas de hacking ético y defensa de sistemas.', 2, 2, '2026-10-12 14:42:24', '2026-10-12 14:42:24'),
(23, 'Discrete Mathematics and Its Applications', 'Kenneth H. Rosen', '9781259676512', 4, 'McGraw-Hill', 2018, 'https://covers.openlibrary.org/b/isbn/9781259676512-L.jpg', 'Matemáticas discretas con aplicaciones a ciencias de la computación.', 6, 6, '2026-10-12 14:42:24', '2026-10-12 14:42:24'),
(24, 'Linear Algebra Done Right', 'Sheldon Axler', '9783319110790', 4, 'Springer', 2015, 'https://covers.openlibrary.org/b/isbn/9783319110790-L.jpg', 'Enfoque moderno y riguroso del álgebra lineal.', 4, 4, '2026-10-12 14:42:24', '2026-10-12 14:42:24'),
(25, 'Introduction to Probability', 'Joseph K. Blitzstein y Jessica Hwang', '9781138369917', 4, 'Chapman & Hall', 2019, 'https://covers.openlibrary.org/b/isbn/9781138369917-L.jpg', 'Introducción a la probabilidad con ejemplos prácticos y simulaciones.', 3, 3, '2026-10-12 14:42:24', '2026-10-12 14:42:24'),
(26, 'Don Quijote de la Mancha', 'Miguel de Cervantes', '9788420412146', 5, 'Alfaguara', 1605, 'https://covers.openlibrary.org/b/isbn/9788420412146-L.jpg', 'La obra cumbre de la literatura española y precursora de la novela moderna.', 5, 5, '2026-10-12 14:42:24', '2026-10-12 14:42:24'),
(27, 'Ficciones', 'Jorge Luis Borges', '9780802130303', 5, 'Grove Press', 1944, 'https://covers.openlibrary.org/b/isbn/9780802130303-L.jpg', 'Colección de cuentos que revolucionaron la narrativa del siglo XX.', 4, 4, '2026-10-12 14:42:24', '2026-10-12 14:42:24'),
(28, 'Pedro Páramo', 'Juan Rulfo', '9780802133908', 5, 'Grove Press', 1955, 'https://covers.openlibrary.org/b/isbn/9780802133908-L.jpg', 'Novela fundamental de la literatura mexicana y del realismo mágico.', 4, 4, '2026-10-12 14:42:24', '2026-10-12 14:42:24'),
(29, 'Sapiens: De animales a dioses', 'Yuval Noah Harari', '9788499926223', 6, 'Debate', 2014, 'https://covers.openlibrary.org/b/isbn/9788499926223-L.jpg', 'Breve historia de la humanidad desde la prehistoria hasta la actualidad.', 6, 6, '2026-10-12 14:42:24', '2026-10-12 14:42:24'),
(30, 'Los Incas', 'Terence N. D\'Altroy', '9788420690124', 6, 'Ariel', 2015, 'https://covers.openlibrary.org/b/isbn/9788420690124-L.jpg', 'Análisis histórico y arqueológico del Imperio Inca.', 3, 3, '2026-10-12 14:42:24', '2026-10-12 14:42:24'),
(31, 'Breve Historia del Perú', 'Carlos Contreras y Marcos Cueto', '9786124345114', 6, 'IEP', 2013, 'https://covers.openlibrary.org/b/isbn/9786124345114-L.jpg', 'Síntesis de la historia peruana desde la época prehispánica hasta hoy.', 4, 4, '2026-10-12 14:42:24', '2026-10-12 14:42:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `multas`
--

CREATE TABLE `multas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_prestamo` bigint(20) UNSIGNED NOT NULL,
  `monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('pendiente','pagada','condonada') NOT NULL DEFAULT 'pendiente',
  `fecha_generacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_pago` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `multas`
--

INSERT INTO `multas` (`id`, `id_prestamo`, `monto`, `estado`, `fecha_generacion`, `fecha_pago`, `created_at`, `updated_at`) VALUES
(3, 1, 9.00, 'pagada', '2026-10-11 21:03:05', '2026-10-11 21:20:29', '2026-10-12 02:03:05', '2026-10-12 02:20:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamos`
--

CREATE TABLE `prestamos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `id_libro` bigint(20) UNSIGNED NOT NULL,
  `id_reserva` bigint(20) UNSIGNED DEFAULT NULL,
  `fecha_prestamo` date NOT NULL,
  `fecha_devolucion_esperada` date NOT NULL,
  `fecha_devolucion_real` date DEFAULT NULL,
  `estado` enum('activo','devuelto','vencido','cancelado') NOT NULL DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `prestamos`
--

INSERT INTO `prestamos` (`id`, `id_usuario`, `id_libro`, `id_reserva`, `fecha_prestamo`, `fecha_devolucion_esperada`, `fecha_devolucion_real`, `estado`, `created_at`, `updated_at`) VALUES
(1, 3, 1, NULL, '2026-10-12', '2026-10-06', '2026-10-12', 'devuelto', '2026-10-12 01:53:26', '2026-10-12 02:27:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

CREATE TABLE `reservas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_usuario` bigint(20) UNSIGNED NOT NULL,
  `id_libro` bigint(20) UNSIGNED NOT NULL,
  `fecha_reserva` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_expiracion` datetime NOT NULL,
  `estado` enum('pendiente','confirmada','expirada','cancelada') NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reservas`
--

INSERT INTO `reservas` (`id`, `id_usuario`, `id_libro`, `fecha_reserva`, `fecha_expiracion`, `estado`, `created_at`, `updated_at`) VALUES
(1, 3, 1, '2026-10-11 20:26:40', '2026-10-14 03:26:40', 'cancelada', '2026-10-12 01:26:40', '2026-10-12 01:27:53'),
(4, 6, 8, '2026-10-12 08:54:41', '2026-10-14 15:54:41', 'cancelada', '2026-10-12 13:54:41', '2026-10-12 13:56:39'),
(5, 8, 12, '2026-10-12 09:42:37', '2026-10-14 16:42:37', 'pendiente', '2026-10-12 14:42:37', '2026-10-12 14:42:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `email` varchar(180) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('estudiante','bibliotecario','administrador') NOT NULL DEFAULT 'estudiante',
  `estado` enum('activo','bloqueado') NOT NULL DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password_hash`, `rol`, `estado`, `created_at`, `updated_at`) VALUES
(1, 'Administrador BiblioTech', 'admin@bibliotech.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrador', 'activo', '2026-10-12 00:40:14', '2026-10-12 00:40:14'),
(2, 'Lucía Fernández', 'biblio@bibliotech.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'bibliotecario', 'activo', '2026-10-12 00:40:14', '2026-10-12 00:40:14'),
(3, 'Carlos Ramírez', 'carlos@bibliotech.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'estudiante', 'activo', '2026-10-12 00:40:14', '2026-10-12 00:40:14'),
(4, 'Ana Torres', 'ana@bibliotech.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'estudiante', 'activo', '2026-10-12 00:40:14', '2026-10-12 00:40:14'),
(5, 'Christian Huayta Q49', 'christianhuaytaq@gmail.com', '$2y$10$T1pVOgh.vKUqGS6GwiYpWunNhKeK1HizurBR2y0UTqfziLcYT.OAe', 'estudiante', 'activo', '2026-10-12 13:40:34', '2026-10-12 13:40:34'),
(6, 'jhonady', 'jhonady@gmail.com', '$2y$10$cqhW3orcMuZaww0rEe7xcu08Ujv6OSXTrljMj8Y6.HfOwRAmXtISG', 'estudiante', 'activo', '2026-10-12 13:54:07', '2026-10-12 13:54:07'),
(7, 'David Delacruz', 'lh588589@gmail.com', '$2y$10$merhSEXEXBpAJs2IH8L6JOSaYVMAdhvy7EZgrlCjkI6SgGb2HU3N6', 'bibliotecario', 'activo', '2026-10-12 14:40:16', '2026-10-12 14:43:21'),
(8, 'Waldo Rusbel Lopez', 'rwaldorusbel@gmail.com', '$2y$10$f/0nNFJHqssOwiijj7W4q.IgViV5.vPfN3BbXgVRhTImDd2bY5MXm', 'estudiante', 'activo', '2026-10-12 14:41:36', '2026-10-12 14:41:36');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_categorias_nombre` (`nombre`);

--
-- Indices de la tabla `libros`
--
ALTER TABLE `libros`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_libros_isbn` (`isbn`),
  ADD KEY `idx_libros_titulo` (`titulo`),
  ADD KEY `idx_libros_autor` (`autor`),
  ADD KEY `idx_libros_categoria` (`id_categoria`);

--
-- Indices de la tabla `multas`
--
ALTER TABLE `multas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_multas_prestamo` (`id_prestamo`),
  ADD KEY `idx_multas_estado` (`estado`);

--
-- Indices de la tabla `prestamos`
--
ALTER TABLE `prestamos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prestamos_usuario` (`id_usuario`),
  ADD KEY `idx_prestamos_libro` (`id_libro`),
  ADD KEY `idx_prestamos_estado` (`estado`),
  ADD KEY `idx_prestamos_reserva` (`id_reserva`);

--
-- Indices de la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reservas_usuario` (`id_usuario`),
  ADD KEY `idx_reservas_libro` (`id_libro`),
  ADD KEY `idx_reservas_estado` (`estado`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usuarios_email` (`email`),
  ADD KEY `idx_usuarios_rol` (`rol`),
  ADD KEY `idx_usuarios_estado` (`estado`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `libros`
--
ALTER TABLE `libros`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `multas`
--
ALTER TABLE `multas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `prestamos`
--
ALTER TABLE `prestamos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `libros`
--
ALTER TABLE `libros`
  ADD CONSTRAINT `fk_libros_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `multas`
--
ALTER TABLE `multas`
  ADD CONSTRAINT `fk_multas_prestamo` FOREIGN KEY (`id_prestamo`) REFERENCES `prestamos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `prestamos`
--
ALTER TABLE `prestamos`
  ADD CONSTRAINT `fk_prestamos_libro` FOREIGN KEY (`id_libro`) REFERENCES `libros` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prestamos_reserva` FOREIGN KEY (`id_reserva`) REFERENCES `reservas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prestamos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `fk_reservas_libro` FOREIGN KEY (`id_libro`) REFERENCES `libros` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reservas_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
