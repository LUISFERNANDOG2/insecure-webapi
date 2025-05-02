/*
###-
create database webapps;
GRANT ALL PRIVILEGES ON webapps.* TO 'udbwebaps'@'localhost' IDENTIFIED BY 'ku>;k8ND4CN4';
FLUSH PRIVILEGES
*/

/*use webapps;*/

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS Usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,            -- Clave primaria
    uname VARCHAR(30) NOT NULL,                   -- Usuario: nombre corto, no nulo
    email VARCHAR(100) NOT NULL,                  -- Email realista, no nulo
    password VARCHAR(72) NOT NULL,                -- Hash bcrypt = 60-72 caracteres, no nulo
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP, -- Timestamp de creación, por defecto es el momento actual
    CONSTRAINT U_UNAME UNIQUE (uname),            -- Asegura que 'uname' sea único
    CONSTRAINT U_EMAIL UNIQUE (email),            -- Asegura que 'email' sea único
    CONSTRAINT email_format CHECK (email LIKE '%@%.%') -- Verifica que el email tenga formato válido
) ENGINE=InnoDB;

-- Tabla de tokens de sesión
CREATE TABLE IF NOT EXISTS AccesoToken (
    id INT AUTO_INCREMENT PRIMARY KEY,                       -- Clave primaria
    id_Usuario INT NOT NULL,                                  -- Clave foránea, no nula
    token CHAR(64) NOT NULL,                                  -- Token hexadecimal de 64 caracteres, no nulo
    fecha DATETIME NOT NULL,                                  -- Fecha de creación del token, no nula
    expiracion DATETIME NOT NULL,                             -- Fecha de expiración del token, no nula
    CONSTRAINT FK_AT_U FOREIGN KEY (id_Usuario) REFERENCES Usuario(id) ON DELETE CASCADE, -- Relación con la tabla Usuario, eliminar en cascada
    CONSTRAINT token_expiration CHECK (expiracion > fecha) -- Verifica que la fecha de expiración sea posterior a la fecha de creación
) ENGINE=InnoDB;

-- Tabla de imágenes
CREATE TABLE IF NOT EXISTS Imagen (
    id INT AUTO_INCREMENT PRIMARY KEY,            -- Clave primaria
    name VARCHAR(100) NOT NULL,                    -- Nombre de la imagen, no nulo
    ruta VARCHAR(255) NOT NULL,                    -- Ruta o URL de la imagen, no nula
    id_Usuario INT NOT NULL,                       -- Clave foránea, no nula
    CONSTRAINT FK_I_U FOREIGN KEY (id_Usuario) REFERENCES Usuario(id) ON DELETE CASCADE, -- Relación con la tabla Usuario, eliminar en cascada
    CONSTRAINT ruta_format CHECK (ruta LIKE 'http%' OR ruta LIKE '/%') -- Verifica que la ruta sea un URL o ruta válida
) ENGINE=InnoDB;



