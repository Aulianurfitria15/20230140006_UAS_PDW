CREATE TABLE `pendaftaran_praktikum` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_mahasiswa` int(11) NOT NULL,
  `id_praktikum` int(11) NOT NULL,
  `tanggal_daftar` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `pendaftaran_unik` (`id_mahasiswa`,`id_praktikum`),
  KEY `id_praktikum` (`id_praktikum`),
  CONSTRAINT `pendaftaran_praktikum_ibfk_1` FOREIGN KEY (`id_mahasiswa`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pendaftaran_praktikum_ibfk_2` FOREIGN KEY (`id_praktikum`) REFERENCES `mata_praktikum` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;