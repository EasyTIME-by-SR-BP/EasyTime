SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS mitarbeiter (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  personal_id      VARCHAR(30) NULL,
  vorname          VARCHAR(255) NULL,
  nachname         VARCHAR(255) NULL,
  strasse          VARCHAR(255) NULL,
  hausnummer       VARCHAR(255) NULL,
  plz              INT NULL,
  ort              VARCHAR(255) NULL,
  geb_datum        DATE NULL,
  sv_nummer        VARCHAR(45) NULL,
  geschlecht       VARCHAR(45) NULL,
  email            VARCHAR(100) NULL,
  firmen_telefon   VARCHAR(45) NULL,
  privat_telefon   VARCHAR(45) NULL,
  iban             VARCHAR(45) NULL,
  bic              VARCHAR(45) NULL,
  position         VARCHAR(45) NULL,
  weitere_position VARCHAR(45) NULL,
  bemerkung        TEXT NULL,
  status           TINYINT NULL,
  password         VARCHAR(200) NULL,
  berechtigung     VARCHAR(45) NULL,
  urlaubsanspruch  INT NULL,
  akt_wochen_std   INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS standorte (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  ort          VARCHAR(150) NULL,
  kostenstelle INT NULL,
  strasse      VARCHAR(45) NULL,
  hausnummer   VARCHAR(45) NULL,
  plz          INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS klassen (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  klasse         VARCHAR(45) NULL,
  mitarbeiter_id INT NOT NULL,
  FOREIGN KEY (mitarbeiter_id) REFERENCES mitarbeiter(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dokumente (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  url          TEXT NULL,
  upload_datum DATE NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mitarbeiter_dokumente (
  mitarbeiter_id INT NOT NULL,
  dokument_id    INT NOT NULL,
  PRIMARY KEY (mitarbeiter_id, dokument_id),
  FOREIGN KEY (mitarbeiter_id) REFERENCES mitarbeiter(id),
  FOREIGN KEY (dokument_id) REFERENCES dokumente(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mitarbeiter_standorte (
  mitarbeiter_id INT NOT NULL,
  standort_id    INT NOT NULL,
  basis          TINYINT NOT NULL,
  PRIMARY KEY (mitarbeiter_id, standort_id),
  FOREIGN KEY (mitarbeiter_id) REFERENCES mitarbeiter(id),
  FOREIGN KEY (standort_id) REFERENCES standorte(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS standort_vertretung (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  standort_id  INT NOT NULL,
  vertreter_id INT NOT NULL,
  prioritaet   INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eintritt (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  mitarbeiter_id INT NOT NULL,
  eintrittsdatum DATE NULL,
  std_woche      INT NULL,
  berufsjahr     INT NULL,
  einstufung     VARCHAR(255) NULL,
  offener_urlaub INT NULL,
  FOREIGN KEY (mitarbeiter_id) REFERENCES mitarbeiter(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS abmeldung (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  mitarbeiter_id  INT NOT NULL,
  datum_abmeldung DATE NULL,
  datum_anmeldung DATE NULL,
  resturlaub      INT NULL,
  FOREIGN KEY (mitarbeiter_id) REFERENCES mitarbeiter(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS aenderungsmeldung (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  mitarbeiter_id  INT NOT NULL,
  bearbeitet_von  INT NOT NULL,
  datum           DATE NULL,
  std_woche       INT NULL,
  urlaubsanspruch INT NULL,
  FOREIGN KEY (mitarbeiter_id) REFERENCES mitarbeiter(id),
  FOREIGN KEY (bearbeitet_von) REFERENCES mitarbeiter(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS taetigkeitsart (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  bezeichnung VARCHAR(45) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS taetigkeit (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  datum             DATE NULL,
  mitarbeiter_id    INT NOT NULL,
  taetigkeitsart_id INT NOT NULL,
  stunden           DECIMAL(10,3) NULL,
  FOREIGN KEY (mitarbeiter_id) REFERENCES mitarbeiter(id) ON DELETE CASCADE,
  FOREIGN KEY (taetigkeitsart_id) REFERENCES taetigkeitsart(id) ON DELETE CASCADE,
  UNIQUE KEY uq_taetigkeit (datum, mitarbeiter_id, taetigkeitsart_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  standort_id       INT NOT NULL,
  `start`           DATE NULL,
  ende              DATE NULL,
  titel             VARCHAR(255) NULL,
  eventtyp          VARCHAR(45) NOT NULL,
  klassen           TEXT NULL,
  urlaub_akzeptabel TINYINT NULL,
  in_urlaub         TINYINT NOT NULL,
  status            TINYINT NOT NULL,
  bemerkung         TEXT NULL,
  FOREIGN KEY (standort_id) REFERENCES standorte(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS urlaub (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  mitarbeiter_id      INT NOT NULL,
  beginn              DATE NULL,
  ende                DATE NULL,
  tage_im_urlaub      INT NULL,
  beginn_in_worten    VARCHAR(255) NULL,
  ende_in_worten      VARCHAR(255) NULL,
  genehmigt           TINYINT NULL,
  vertretung_id       INT NULL,
  buero               INT NULL,
  buero_vertretung_id INT NULL,
  FOREIGN KEY (mitarbeiter_id) REFERENCES mitarbeiter(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS urlaubssperre (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  von         DATE NULL,
  bis         DATE NULL,
  ganzjaehrig TINYINT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS urlaub_kommentar (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  urlaub_id      INT NOT NULL,
  mitarbeiter_id INT NOT NULL,
  kommentar      TEXT NOT NULL,
  erstellt_am    DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (urlaub_id) REFERENCES urlaub(id) ON DELETE CASCADE,
  FOREIGN KEY (mitarbeiter_id) REFERENCES mitarbeiter(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS urlaub_event (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  event_id  INT NOT NULL,
  urlaub_id INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS uebertrag (
  mitarbeiter_id         INT NOT NULL,
  datum                  DATE NOT NULL,
  uebertrag_urlaub       DECIMAL(10,3) NULL,
  uebertrag_ueberstunden DECIMAL(10,3) NULL,
  ang_wochen_std         DECIMAL(10,3) NULL,
  monats_soll            DECIMAL(10,3) NULL,
  PRIMARY KEY (mitarbeiter_id, datum),
  FOREIGN KEY (mitarbeiter_id) REFERENCES mitarbeiter(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vorlagen (
  id  INT AUTO_INCREMENT PRIMARY KEY,
  typ VARCHAR(45) NULL,
  url TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS zuschlag (
  mitarbeiter_id INT NOT NULL,
  datum          DATE NOT NULL,
  gr10_pro_tag   DECIMAL(10,3) NULL,
  wochenende     DECIMAL(10,3) NULL,
  nacht          DECIMAL(10,3) NULL,
  A              DECIMAL(10,3) NULL,
  C              DECIMAL(10,3) NULL,
  E              DECIMAL(10,3) NULL,
  F              DECIMAL(10,3) NULL,
  D              DECIMAL(10,3) NULL,
  theorie        DECIMAL(10,3) NULL,
  PRIMARY KEY (mitarbeiter_id, datum),
  FOREIGN KEY (mitarbeiter_id) REFERENCES mitarbeiter(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS monatsbericht_view (
  mitarbeiter_id INT NULL,
  datum          DATE NULL,
  A              DECIMAL(10,3) NULL,
  C              DECIMAL(10,3) NULL,
  E              DECIMAL(10,3) NULL,
  F              DECIMAL(10,3) NULL,
  D              DECIMAL(10,3) NULL,
  theorie        DECIMAL(10,3) NULL,
  nacht          DECIMAL(10,3) NULL,
  wochenende     DECIMAL(10,3) NULL,
  gr10_pro_tag   DECIMAL(10,3) NULL,
  lektion        DECIMAL(10,3) NULL,
  regie          DECIMAL(10,3) NULL,
  pruefung       DECIMAL(10,3) NULL,
  krank          DECIMAL(10,3) NULL,
  feiertag       DECIMAL(10,3) NULL,
  urlaub_col     DECIMAL(10,3) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_settings (
  `key`   VARCHAR(64) PRIMARY KEY,
  `value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  user_id             INT NOT NULL,
  title               VARCHAR(255) NOT NULL,
  message             TEXT NOT NULL,
  category            VARCHAR(32) DEFAULT 'info',
  type                VARCHAR(16) DEFAULT 'info',
  resolution_mode     VARCHAR(16) DEFAULT 'individual',
  thread_id           VARCHAR(64) DEFAULT NULL,
  action_url          TEXT DEFAULT NULL,
  related_user_id     INT DEFAULT NULL,
  is_read             TINYINT DEFAULT 0,
  is_resolved         TINYINT DEFAULT 0,
  resolved_at         DATETIME DEFAULT NULL,
  resolved_by_user_id INT DEFAULT NULL,
  created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES mitarbeiter(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
