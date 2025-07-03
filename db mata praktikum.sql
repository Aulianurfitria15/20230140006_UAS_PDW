CREATE TABLE mata_praktikum (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_praktikum VARCHAR(255) NOT NULL,
    deskripsi TEXT,
    id_asisten_pembuat INT,
    FOREIGN KEY (id_asisten_pembuat) REFERENCES users(id)
);