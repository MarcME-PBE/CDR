-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS nemesis;
USE nemesis;

-- Crear la tabla IF NOT EXISTS Timetables
CREATE TABLE Timetables (
    day VARCHAR(10),
    hour INT,
    subject VARCHAR(50),
    room VARCHAR(10),
    uid VARCHAR(10)
);

-- Crear la tabla Marks
CREATE TABLE IF NOT EXISTS Marks (
    subject VARCHAR(50),
    name VARCHAR(50),
    mark DECIMAL(4, 2),
    uid VARCHAR(10)
);

-- Crear la tabla Tasks
CREATE TABLE IF NOT EXISTS Tasks (
    date DATE,
    subject VARCHAR(50),
    name VARCHAR(50),
    uid VARCHAR(10)
);

-- Crear la tabla Students
CREATE TABLE IF NOT EXISTS Students (
    uid VARCHAR(10),
    name VARCHAR(100),
    username VARCHAR(100),
    password VARCHAR(50)
);
