-- Creazione Database (decommentare se serve)
-- CREATE DATABASE IF NOT EXISTS my_db_telefonia;
-- USE my_db_telefonia;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS Telefonata;
DROP TABLE IF EXISTS SIMAttiva;
DROP TABLE IF EXISTS SIMDisattiva;
DROP TABLE IF EXISTS SIMNonAttiva;
DROP TABLE IF EXISTS ContrattoTelefonico;
SET FOREIGN_KEY_CHECKS = 1;

-- Tabella: ContrattoTelefonico
CREATE TABLE IF NOT EXISTS ContrattoTelefonico (
    numero VARCHAR(20) PRIMARY KEY,
    dataAttivazione DATE NOT NULL,
    tipo ENUM('ricarica', 'consumo') NOT NULL,
    minutiResidui INT DEFAULT NULL,
    creditoResiduo DECIMAL(10, 2) DEFAULT NULL,
    CONSTRAINT chk_tipo_contratto CHECK (
        (tipo = 'consumo' AND minutiResidui IS NOT NULL AND creditoResiduo IS NULL) 
        OR 
        (tipo = 'ricarica' AND minutiResidui IS NULL AND creditoResiduo IS NOT NULL)
    )
);

-- Tabella: Telefonata
CREATE TABLE IF NOT EXISTS Telefonata (
    id INT NOT NULL,
    effettuataDa VARCHAR(20) NOT NULL,
    data DATE NOT NULL,
    ora TIME NOT NULL,
    durata INT NOT NULL COMMENT 'Durata in secondi',
    costo DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (effettuataDa, id),
    FOREIGN KEY (effettuataDa) REFERENCES ContrattoTelefonico(numero) ON DELETE CASCADE
);

-- Tabella: SIMAttiva
CREATE TABLE IF NOT EXISTS SIMAttiva (
    codice VARCHAR(50) PRIMARY KEY,
    tipoSIM VARCHAR(20) NOT NULL,
    associataA VARCHAR(20) NOT NULL,
    dataAttivazione DATE NOT NULL,
    UNIQUE KEY uni_associataA (associataA),
    FOREIGN KEY (associataA) REFERENCES ContrattoTelefonico(numero) ON DELETE CASCADE
);

-- Tabella: SIMDisattiva
CREATE TABLE IF NOT EXISTS SIMDisattiva (
    codice VARCHAR(50) PRIMARY KEY,
    tipoSIM VARCHAR(20) NOT NULL,
    eraAssociataA VARCHAR(20) NOT NULL,
    dataAttivazione DATE NOT NULL,
    dataDisattivazione DATE NOT NULL,
    FOREIGN KEY (eraAssociataA) REFERENCES ContrattoTelefonico(numero) ON DELETE CASCADE
);

-- Tabella: SIMNonAttiva
CREATE TABLE IF NOT EXISTS SIMNonAttiva (
    codice VARCHAR(50) PRIMARY KEY,
    tipoSIM VARCHAR(20) NOT NULL
);

-- Popolamento Dati Fittizi
INSERT INTO ContrattoTelefonico (numero, dataAttivazione, tipo, minutiResidui, creditoResiduo) VALUES
('3331234567', '2023-01-15', 'ricarica', NULL, 15.50),
('3409876543', '2022-11-20', 'consumo', 450, NULL),
('3284561239', '2023-05-10', 'ricarica', NULL, 5.00),
('3928887776', '2021-08-05', 'consumo', 120, NULL),
('3510001112', '2024-02-28', 'ricarica', NULL, 25.00);

INSERT INTO Telefonata (id, effettuataDa, data, ora, durata, costo) VALUES
(1, '3331234567', '2024-04-01', '10:15:00', 120, 0.50),
(1, '3409876543', '2024-04-02', '14:30:00', 300, 0.00), -- a consumo potrebbe costare 0
(1, '3284561239', '2024-04-03', '09:00:00', 60, 0.20),
(2, '3331234567', '2024-04-04', '18:45:00', 450, 1.50);

INSERT INTO SIMAttiva (codice, tipoSIM, associataA, dataAttivazione) VALUES
('SIMA-1001', 'Nano', '3331234567', '2023-01-15'),
('SIMA-1002', 'Micro', '3409876543', '2022-11-20'),
('SIMA-1003', 'eSIM', '3284561239', '2023-05-10');

INSERT INTO SIMDisattiva (codice, tipoSIM, eraAssociataA, dataAttivazione, dataDisattivazione) VALUES
('SIMD-2001', 'Standard', '3928887776', '2021-08-05', '2023-12-31');

INSERT INTO SIMNonAttiva (codice, tipoSIM) VALUES
('SIMN-3001', 'Nano'),
('SIMN-3002', 'eSIM');
