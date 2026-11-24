-- ============================================================
-- Caso 1 - Sistema de Inventario TecnoStock
-- Base de datos: MySQL 8 / MariaDB compatible
-- ============================================================

DROP DATABASE IF EXISTS TecnoStock;
CREATE DATABASE TecnoStock
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE TecnoStock;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuarios (
  id_usuario INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  correo VARCHAR(150) NOT NULL,
  clave VARCHAR(255) NOT NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY uq_usuarios_correo (correo),
  CONSTRAINT ck_usuarios_estado CHECK (estado IN (0, 1))
) ENGINE=InnoDB;

CREATE TABLE categorias (
  id_categoria INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255) NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_categoria),
  UNIQUE KEY uq_categorias_nombre (nombre),
  CONSTRAINT ck_categorias_estado CHECK (estado IN (0, 1))
) ENGINE=InnoDB;

CREATE TABLE productos (
  id_producto INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(30) NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  descripcion TEXT NULL,
  precio DECIMAL(12,2) NOT NULL,
  stock_actual INT UNSIGNED NOT NULL DEFAULT 0,
  stock_minimo INT UNSIGNED NOT NULL DEFAULT 0,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  id_categoria INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_producto),
  UNIQUE KEY uq_productos_codigo (codigo),
  CONSTRAINT fk_productos_categoria
    FOREIGN KEY (id_categoria)
    REFERENCES categorias (id_categoria)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT ck_productos_precio CHECK (precio >= 0),
  CONSTRAINT ck_productos_stock_actual CHECK (stock_actual >= 0),
  CONSTRAINT ck_productos_stock_minimo CHECK (stock_minimo >= 0),
  CONSTRAINT ck_productos_estado CHECK (estado IN (0, 1))
) ENGINE=InnoDB;

CREATE TABLE movimientos (
  id_movimiento INT UNSIGNED NOT NULL AUTO_INCREMENT,
  fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tipo ENUM('ENTRADA', 'SALIDA') NOT NULL,
  cantidad INT UNSIGNED NOT NULL,
  observacion VARCHAR(255) NULL,
  id_producto INT UNSIGNED NOT NULL,
  id_usuario INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_movimiento),
  CONSTRAINT fk_movimientos_producto
    FOREIGN KEY (id_producto)
    REFERENCES productos (id_producto)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_movimientos_usuario
    FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id_usuario)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT ck_movimientos_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB;

CREATE INDEX idx_productos_nombre ON productos (nombre);
CREATE INDEX idx_productos_categoria ON productos (id_categoria);
CREATE INDEX idx_movimientos_producto ON movimientos (id_producto);
CREATE INDEX idx_movimientos_usuario ON movimientos (id_usuario);
CREATE INDEX idx_movimientos_fecha ON movimientos (fecha_hora);

-- Datos de prueba.
-- La clave demo es la siguiente: password
-- Debe generarse con password_hash() en PHP si se reemplaza este dato.
INSERT INTO usuarios (nombre, correo, clave, estado) VALUES
  ('Administrador TecnoStock', 'admin@tecnostock.local',
   '$2y$10$I6jY0v/dM847wXyDxI1.1u9kxTkatGojuRRb//HwB/xtvShNcevP2', 1);

INSERT INTO categorias (nombre, descripcion, estado) VALUES
  ('Accesorios', 'Accesorios generales para dispositivos tecnológicos', 1),
  ('Audio', 'Audífonos, parlantes y accesorios de sonido', 1),
  ('Almacenamiento', 'Memorias, discos y unidades externas', 1),
  ('Conectividad', 'Adaptadores, cables y dispositivos de red', 1);

INSERT INTO productos
  (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, estado, id_categoria)
VALUES
  ('TS-AUD-001', 'Audífonos inalámbricos', 'Audífonos Bluetooth con micrófono', 39990.00, 15, 5, 1, 2),
  ('TS-ALM-001', 'Pendrive 64 GB', 'Memoria USB 3.0 de 64 GB', 12990.00, 30, 8, 1, 3),
  ('TS-CON-001', 'Cable HDMI 2 metros', 'Cable HDMI compatible con resoluciones 4K', 8990.00, 3, 5, 1, 4),
  ('TS-ACC-001', 'Mouse óptico', 'Mouse USB de 1200 dpi', 7990.00, 20, 6, 1, 1),
  ('TS-AUD-002', 'Parlante portátil', 'Parlante Bluetooth recargable', 24990.00, 8, 4, 1, 2);

-- Un movimiento registra el responsable y actualiza el stock automáticamente.
DELIMITER //

CREATE TRIGGER tr_movimientos_before_insert
BEFORE INSERT ON movimientos
FOR EACH ROW
BEGIN
  DECLARE v_stock INT UNSIGNED;
  DECLARE v_estado TINYINT(1);
  DECLARE v_usuario_estado TINYINT(1);

  IF NEW.cantidad <= 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La cantidad del movimiento debe ser mayor que cero.';
  END IF;

  SELECT estado, stock_actual
    INTO v_estado, v_stock
  FROM productos
  WHERE id_producto = NEW.id_producto
  FOR UPDATE;

  IF v_estado IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El producto no existe.';
  END IF;

  IF v_estado = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'No se pueden registrar movimientos para un producto desactivado.';
  END IF;

  SELECT estado
    INTO v_usuario_estado
  FROM usuarios
  WHERE id_usuario = NEW.id_usuario
  FOR UPDATE;

  IF v_usuario_estado IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El usuario responsable no existe.';
  END IF;

  IF v_usuario_estado = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El usuario responsable está desactivado.';
  END IF;

  IF NEW.tipo = 'SALIDA' AND NEW.cantidad > v_stock THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La salida no puede dejar el stock bajo cero.';
  END IF;

  IF NEW.fecha_hora IS NULL THEN
    SET NEW.fecha_hora = CURRENT_TIMESTAMP;
  END IF;
END //

CREATE TRIGGER tr_movimientos_after_insert
AFTER INSERT ON movimientos
FOR EACH ROW
BEGIN
  IF NEW.tipo = 'ENTRADA' THEN
    UPDATE productos
    SET stock_actual = stock_actual + NEW.cantidad
    WHERE id_producto = NEW.id_producto;
  ELSE
    UPDATE productos
    SET stock_actual = stock_actual - NEW.cantidad
    WHERE id_producto = NEW.id_producto;
  END IF;
END //

CREATE TRIGGER tr_movimientos_before_update
BEFORE UPDATE ON movimientos
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Los movimientos no pueden modificarse; registre un movimiento inverso.';
END //

CREATE TRIGGER tr_movimientos_before_delete
BEFORE DELETE ON movimientos
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Los movimientos no pueden eliminarse para conservar el historial.';
END //

CREATE TRIGGER tr_productos_before_delete
BEFORE DELETE ON productos
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Los productos deben desactivarse; no pueden eliminarse físicamente.';
END //

CREATE PROCEDURE sp_registrar_movimiento (
  IN p_tipo ENUM('ENTRADA', 'SALIDA'),
  IN p_cantidad INT UNSIGNED,
  IN p_observacion VARCHAR(255),
  IN p_id_producto INT UNSIGNED,
  IN p_id_usuario INT UNSIGNED
)
BEGIN
  INSERT INTO movimientos
    (fecha_hora, tipo, cantidad, observacion, id_producto, id_usuario)
  VALUES
    (CURRENT_TIMESTAMP, p_tipo, p_cantidad, p_observacion, p_id_producto, p_id_usuario);
END //

DELIMITER ;

-- Movimientos de ejemplo: evidencia del registro y actualización automática.
CALL sp_registrar_movimiento('ENTRADA', 10, 'Ingreso inicial de mercancía', 1, 1);
CALL sp_registrar_movimiento('SALIDA', 2, 'Venta de mostrador', 1, 1);
CALL sp_registrar_movimiento('ENTRADA', 5, 'Reposición de pendrives', 2, 1);

CREATE VIEW vw_inventario AS
SELECT
  p.id_producto,
  p.codigo,
  p.nombre,
  p.descripcion,
  p.precio,
  p.stock_actual,
  p.stock_minimo,
  p.estado,
  c.id_categoria,
  c.nombre AS categoria,
  CASE
    WHEN p.stock_actual < p.stock_minimo THEN 'BAJO'
    ELSE 'NORMAL'
  END AS nivel_stock
FROM productos p
INNER JOIN categorias c ON c.id_categoria = p.id_categoria;

CREATE VIEW vw_stock_bajo AS
SELECT *
FROM vw_inventario
WHERE nivel_stock = 'BAJO'
  AND estado = 1;
