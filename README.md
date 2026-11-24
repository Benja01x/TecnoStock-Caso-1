# TecnoStock - Caso 1

Aplicación web en PHP 8 y MySQL para administrar productos, categorías y movimientos de inventario. La interfaz usa HTML5 y Bootstrap 5, con JavaScript para validaciones y confirmaciones en el navegador.

Tecnologías: PHP 8, MySQL/MariaDB, HTML5, Bootstrap 5 y JavaScript.

## Requisitos

- XAMPP con Apache, PHP 8 y MySQL/MariaDB.
- Extensiones PHP `pdo_mysql` y `mbstring` habilitadas.

## Instalación local

1. Copia esta carpeta dentro de `C:\xampp\htdocs\`.
2. Inicia Apache y MySQL desde el panel de XAMPP.
3. En phpMyAdmin, importa `TecnoStock_Caso1.sql`. **El script elimina y vuelve a crear la base `TecnoStock`.**
4. Si tu usuario/contraseña MySQL no son `root`/vacía, actualízalos en `config.php`.
5. Abre `http://localhost/TecnoStock_Caso1/`.
6. Ingresa con `admin@tecnostock.local` y la clave `password`.

## Archivos de interfaz

Las páginas php generan el HTML5 y conectan la interfaz con la base de datos; no se deben abrir haciendo doble clic ni reemplazar por páginas HTML estáticas.

- `includes/header.php` y `includes/footer.php`: estructura HTML común.
- `assets/vendor/bootstrap/`: archivos locales de Bootstrap 5.3.3 (CSS y JavaScript).
- `assets/js/validaciones.js`: validación y confirmaciones en formularios.

## Prueba rápida

Con Apache y MySQL activos y después de iniciar sesión:

    Correo: admin@tecnostock.local
    Contraseña: password

1. En **Categorías**, crea una categoría de prueba.
2. En **Productos**, crea un producto asociado, con stock inicial `10` y stock mínimo `2`.
3. En **Movimientos**, registra una **Entrada** de `3` unidades para ese producto.
4. Vuelve a **Productos**: el stock debe ser `13`. En **Movimientos** debe aparecer el registro.
5. Prueba una **Salida** de `2`: el stock debe quedar en `11`. Una salida mayor que el stock disponible debe rechazarse.

El script SQL de instalación elimina y vuelve a crear `TecnoStock`; no lo importes de nuevo si ya tienes datos que quieras conservar.

## Funcionalidades

- Inicio/cierre de sesión, usando `password_verify`.
- Alta, consulta, búsqueda, edición y desactivación de productos.
- Alta, edición y desactivación de categorías.
- Entradas/salidas con historial y actualización automática del stock por trigger SQL.
- Alerta de productos activos bajo el stock mínimo.
- Consultas PDO preparadas y validaciones en el navegador y servidor.

Los ajustes de stock realizados desde el formulario de producto también se guardan como movimientos. Los movimientos se conservan; no se editan ni eliminan desde la aplicación.