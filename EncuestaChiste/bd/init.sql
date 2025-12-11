CREATE DATABASE IF NOT EXISTS survey;

USE survey;

-- tabla para la encuesta
CREATE TABLE IF NOT EXISTS votos (
  id INT PRIMARY KEY AUTO_INCREMENT,
  si INT DEFAULT 0,
  no INT DEFAULT 0
);

INSERT INTO votos (id, si, no)
VALUES (1, 0, 0)
ON DUPLICATE KEY UPDATE id = id;

CREATE TABLE IF NOT EXISTS chistes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  texto VARCHAR(255) NOT NULL
);

INSERT INTO chistes (texto) VALUES
  ('¡Paparr, llévame al circorr! No, no, el que quiera verte que venga a casa'),
  ('¡Feliz año nuevo a todos, señores! ¿Cómo que Feliz Año nuevo a todos si estamos en agosto? Uy qué bronca me va a echar mi mujer, nunca me he retrasado tanto..'),
  ('Mamarr, mamarr, ha venido papá borracho y se ha caído en el water. ¡Quitale la cartera y tira de la cisterna!'),
  ('Le dice un padre a un niño: Dime una mentira, hijo. ¡¡¡Paparr, paparr, paparr!!!');
