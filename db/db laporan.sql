CREATE TABLE `laporan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_modul` int(11) NOT NULL,
  `id_mahasiswa` int(11) NOT NULL,
  `file_laporan` varchar(255) NOT NULL,
  `tanggal_kumpul` timestamp NOT NULL DEFAULT current_timestamp(),
  `nilai` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_modul` (`id_modul`),
  KEY `id_mahasiswa` (`id_mahasiswa`),
  CONSTRAINT `laporan_ibfk_1` FOREIGN KEY (`id_modul`) REFERENCES `modul` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `laporan_ibfk_2` FOREIGN KEY (`id_mahasiswa`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
