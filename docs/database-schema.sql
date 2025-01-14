-- Tabela de Condomínios
CREATE TABLE wp_g360_condominiums (
    condominium_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    cnpj VARCHAR(14) UNIQUE,
    total_units INT NOT NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES wp_users(ID) ON DELETE SET NULL
);

-- Tabela de Unidades
CREATE TABLE wp_g360_units (
    unit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    condominium_id BIGINT UNSIGNED NOT NULL,
    unit_number VARCHAR(50) NOT NULL,
    floor VARCHAR(50),
    area DECIMAL(10,2),
    owner_id BIGINT UNSIGNED,
    status ENUM('Occupied', 'Vacant', 'Rented') DEFAULT 'Vacant',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (condominium_id) REFERENCES wp_g360_condominiums(condominium_id),
    FOREIGN KEY (owner_id) REFERENCES wp_users(ID),
    UNIQUE KEY unit_condo (condominium_id, unit_number)
);

-- Tabela de Moradores
CREATE TABLE wp_g360_residents (
    resident_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    unit_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED,
    name VARCHAR(255) NOT NULL,
    cpf VARCHAR(11) UNIQUE,
    email VARCHAR(255),
    phone VARCHAR(20),
    type ENUM('Owner', 'Tenant', 'Family') NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (unit_id) REFERENCES wp_g360_units(unit_id),
    FOREIGN KEY (user_id) REFERENCES wp_users(ID)
);

-- Tabela de Taxas do Condomínio
CREATE TABLE wp_g360_fees (
    fee_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    condominium_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    frequency ENUM('Monthly', 'Annual', 'OneTime') NOT NULL,
    due_day INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (condominium_id) REFERENCES wp_g360_condominiums(condominium_id)
);

-- Tabela de Cobranças
CREATE TABLE wp_g360_charges (
    charge_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    unit_id BIGINT UNSIGNED NOT NULL,
    fee_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('Pending', 'Paid', 'Overdue', 'Cancelled') DEFAULT 'Pending',
    payment_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (unit_id) REFERENCES wp_g360_units(unit_id),
    FOREIGN KEY (fee_id) REFERENCES wp_g360_fees(fee_id)
);

-- Tabela de Áreas Comuns
CREATE TABLE wp_g360_common_areas (
    area_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    condominium_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    capacity INT,
    booking_required BOOLEAN DEFAULT FALSE,
    status ENUM('Available', 'Maintenance', 'Unavailable') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (condominium_id) REFERENCES wp_g360_condominiums(condominium_id)
);

-- Tabela de Reservas
CREATE TABLE wp_g360_bookings (
    booking_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    area_id BIGINT UNSIGNED NOT NULL,
    resident_id BIGINT UNSIGNED NOT NULL,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Cancelled') DEFAULT 'Pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (area_id) REFERENCES wp_g360_common_areas(area_id),
    FOREIGN KEY (resident_id) REFERENCES wp_g360_residents(resident_id)
);

-- Tabela de Ocorrências
CREATE TABLE wp_g360_incidents (
    incident_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    condominium_id BIGINT UNSIGNED NOT NULL,
    reported_by BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    type ENUM('Maintenance', 'Security', 'Noise', 'Other') NOT NULL,
    status ENUM('Open', 'InProgress', 'Resolved', 'Closed') DEFAULT 'Open',
    priority ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (condominium_id) REFERENCES wp_g360_condominiums(condominium_id),
    FOREIGN KEY (reported_by) REFERENCES wp_users(ID)
);

-- Tabela de Visitantes
CREATE TABLE wp_g360_visitors (
    visitor_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    unit_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    document_type ENUM('RG', 'CPF', 'CNH') NOT NULL,
    document_number VARCHAR(20) NOT NULL,
    phone VARCHAR(20),
    entry_date TIMESTAMP NOT NULL,
    exit_date TIMESTAMP,
    status ENUM('Expected', 'InPremises', 'Left') DEFAULT 'Expected',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (unit_id) REFERENCES wp_g360_units(unit_id)
);
