-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS atenea;
USE atenea;

-- Creación de las tabla que usaremos
CREATE TABLE IF NOT EXISTS students(
    uid VARCHAR(8) NOT NULL,
    name VARCHAR(30),
    PRIMARY KEY(uid)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS tasks(
    date DATE NOT NULL,
    subject VARCHAR(30),
    name VARCHAR(30)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS timetables(
    day VARCHAR(3),
    hour INT,
    subject VARCHAR(30) NOT NULL,
    room VARCHAR(7)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS marks(
    subject VARCHAR(10),
    name VARCHAR(30),
    mark INT,
    uid VARCHAR(8) NOT NULL,
    CONSTRAINT fk_id
    FOREIGN KEY(uid)
    REFERENCES students(uid)
) ENGINE = INNODB;



-- Cambiar el método de autenticación del usuario root y establecer contraseña
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '1234';
-- Aplicar los cambios de permisos
FLUSH PRIVILEGES;

-- Insertar datos de estudiantes
INSERT INTO students(uid, name)
VALUES 
    ('A2198D27', 'Eloi Saballs'),
    ('169C9314', 'Èric Lozano'),
    ('13503125', 'Marc Muñoz'),
    ('66269414', 'Anna Llamas'),
    ('06F3E0B0', 'Alejandro de Alvarado');

-- Insertar datos de tareas
INSERT INTO tasks (date, subject, name)
VALUES 
    ('2024-12-10', 'PBE', 'Puzzle 1'),
    ('2024-12-15', 'PSAVC', 'Practica 2'),
    ('2024-12-17', 'RP', 'Practica 3'),
    ('2024-12-22', 'TD', 'Entrega 2'),
    ('2024-12-28', 'DSBM', 'Practica 3'),
    ('2024-12-30', 'PBE', 'Critical Design Report');

-- Insertar datos del horario
INSERT INTO timetables(day, hour, subject, room)
VALUES 
    ('Mon', 10, 'RP', 'A4105'),
    ('Mon', 16, 'TD', 'A4105'),
    ('Tue', 10, 'DSBM', 'A4105'),
    ('Tue', 12, 'LAB DSBM', 'C5S101A'),
    ('Tue', 16, 'PSAVC', 'A4105'),
    ('Wed', 08, 'PBE', 'A4105'),
    ('Wed', 17, 'LAB RP', 'D3006'),
    ('Thu', 08, 'PBE', 'A4105'),
    ('Thu', 10, 'RP', 'A4105'),
    ('Thu', 14, 'TD', 'A4105'),
    ('Fri', 12, 'DSBM', 'A4105'),
    ('Fri', 15, 'PSAVC', 'A4105');

-- Insertar calificaciones para cada estudiante
INSERT INTO marks(subject, name, mark, uid)
VALUES 
    ('EIM', 'Examen Parcial', 8.4, 'A2198D27'),
    ('TCGI', 'Examen Final', 7.1, 'A2198D27'),
    ('PBE', 'CDR', 8.6, 'A2198D27'),
    ('PSAVC', 'Examen Final', 5.6, 'A2198D27'),
    ('RP', 'Examen Lab', 9.3, 'A2198D27');

INSERT INTO marks(subject, name, mark, uid)
VALUES 
    ('CAL', 'Examen Parcial', 1.5, '169C9314'),
    ('ALG', 'Examen Parcial', 0.2, '169C9314'),
    ('FDF', 'Examen Parcial', 5.1, '169C9314'),
    ('FDE', 'Examen Parcial', 7.5, '169C9314'),
    ('FO', 'Examen Parcial', 9.7, '169C9314'),
    ('TFG', 'Entrega Final', 10.0, '169C9314');



INSERT INTO marks(subject, name, mark, uid)
VALUES 
    ('CAL', 'Examen Final', 9.2, '13503125'),
    ('EM', 'Examen Parcial', 3.1, '13503125'),
    ('FISE', 'Examen Parcial', 7.5, '13503125'),
    ('RP', 'Examen Parcial', 4.3, '13503125'),
    ('EIM', 'Examen Final', 9.6, '13503125');

INSERT INTO marks(subject, name, mark, uid)
VALUES 
    ('FDE', 'Examen Parcial', 8.2, '66269414'),
    ('ALG', 'Examen Final', 3.4, '66269414'),
    ('FO', 'Examen Final', 10.0, '66269414'),
    ('ENTIC', 'Projecte Final', 9.1, '66269414'),
    ('POO', 'Projecte Final', 6.5, '66269414');

INSERT INTO marks(subject, name, mark, uid)
VALUES 
    ('POO', 'Projecte', 9.9, '06F3E0B0'),
    ('PIE', 'Examen Final', 4.7, '06F3E0B0'),
    ('IPAV', 'Examen Final', 9.7, '06F3E0B0'),
    ('ICOM', 'Examen Parcial', 6.5, '06F3E0B0'),
    ('DSBM', 'Examen Lab', 1.8, '06F3E0B0');
