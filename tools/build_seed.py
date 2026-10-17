# -*- coding: utf-8 -*-
"""Builds database/schema.sql (DDL + demo data) and all SVG artwork.

Usage:  python3 tools/build_seed.py
Requires: php (for password_hash) — the output schema.sql is committed, so end users never run this.
"""
import hashlib
import math
import os
import random
import subprocess
import sys
from datetime import datetime, timedelta

sys.path.insert(0, os.path.dirname(__file__))
from seed_content import LANGS, CATEGORIES, TICKET_TYPES, VENUES, EDITIONS, ARTISTS  # noqa
from seed_events import EVENTS  # noqa
from seed_misc import (NEWS_CATEGORIES, NEWS, ALBUMS, CAPTIONS, SPONSORS, PARTNERS, TEAM, FAQ, USERS)  # noqa
import photos  # noqa  (photographs in assets/img; tools/build_images.py is no longer used)

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
R = random.Random(1961)
SEED_NOW = datetime(2026, 10, 5, 18, 0)


class Raw(str):
    pass


def q(v):
    if v is None:
        return "NULL"
    if isinstance(v, Raw):
        return str(v)
    if isinstance(v, bool):
        return "1" if v else "0"
    if isinstance(v, (int, float)):
        return str(v)
    if isinstance(v, datetime):
        return "'" + v.strftime("%Y-%m-%d %H:%M:%S") + "'"
    return "'" + str(v).replace("\\", "\\\\").replace("'", "''") + "'"


OUT = []


def insert(table, cols, rows, chunk=400):
    if not rows:
        return
    for i in range(0, len(rows), chunk):
        part = rows[i:i + chunk]
        vals = ",\n".join("(" + ",".join(q(v) for v in row) + ")" for row in part)
        OUT.append(f"INSERT INTO `{table}` ({','.join('`' + c + '`' for c in cols)}) VALUES\n{vals};\n")


def php_hash(pw):
    return subprocess.check_output(["php", "-r", f"echo password_hash({pw!r}, PASSWORD_DEFAULT);"]).decode().strip()


DDL = r"""-- =====================================================================
--  Охридско лето / Ohrid Summer Festival — database schema + demo data
--  MySQL 5.7+/8.x or MariaDB 10.3+ (XAMPP).  Import via phpMyAdmin or:
--      mysql -u root < database/schema.sql
--  Historical festival facts are real; programme, people and sales are
--  FICTIONAL DEMO DATA for a university project.
-- =====================================================================
SET NAMES utf8mb4;
SET time_zone = '+02:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `ohrid_summer` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ohrid_summer`;

DROP TABLE IF EXISTS page_views, cookie_preferences, contact_messages, newsletter_subscribers,
  faq_translations, faqs, team_member_translations, team_members, partner_translations, partners,
  sponsor_translations, sponsors, gallery_image_translations, gallery_images,
  gallery_album_translations, gallery_albums, news_translations, news, news_category_translations,
  news_categories, favorites, reservations, tickets, order_items, orders, ticket_type_translations,
  ticket_types, event_seat_blocks, event_artists, event_translations, events, artist_translations,
  artists, venue_seats, venue_translations, venues, edition_translations, festival_editions,
  category_translations, categories, login_attempts, users, roles;

SET FOREIGN_KEY_CHECKS = 1;

-- schema version: tools/update-db.php re-imports this file when an older database is found
DROP TABLE IF EXISTS app_meta;
CREATE TABLE app_meta (k VARCHAR(40) NOT NULL PRIMARY KEY, v VARCHAR(100) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO app_meta (k, v) VALUES ('schema_version', '2026-10-14-photos');

-- ---------------------------------------------------------------- users
CREATE TABLE roles (
  id TINYINT UNSIGNED PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id TINYINT UNSIGNED NOT NULL DEFAULT 2,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(40) NULL,
  preferred_lang CHAR(2) NOT NULL DEFAULT 'mk',
  newsletter TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at DATETIME NULL,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  ip_hash CHAR(64) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_attempts (email, attempted_at),
  INDEX idx_attempts_ip (ip_hash, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- taxonomy
CREATE TABLE categories (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(40) NOT NULL UNIQUE,
  icon VARCHAR(30) NOT NULL DEFAULT 'spark',
  color CHAR(7) NOT NULL DEFAULT '#134a66',
  sort_order SMALLINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE category_translations (
  category_id TINYINT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,
  name VARCHAR(80) NOT NULL,
  PRIMARY KEY (category_id, lang),
  CONSTRAINT fk_cat_tr FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE festival_editions (
  id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  year SMALLINT UNSIGNED NOT NULL UNIQUE,
  edition_number SMALLINT UNSIGNED NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  visitors INT UNSIGNED NOT NULL DEFAULT 0,
  countries SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT chk_edition_dates CHECK (end_date >= start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE edition_translations (
  edition_id SMALLINT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,
  theme VARCHAR(120) NOT NULL,
  summary TEXT NULL,
  PRIMARY KEY (edition_id, lang),
  CONSTRAINT fk_ed_tr FOREIGN KEY (edition_id) REFERENCES festival_editions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- venues & seats
CREATE TABLE venues (
  id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL UNIQUE,
  image VARCHAR(255) NULL,
  layout_type ENUM('amphitheatre','nave','courtyard','terrace','hall','open') NOT NULL DEFAULT 'hall',
  seating_type ENUM('seated','general') NOT NULL DEFAULT 'seated',
  capacity INT UNSIGNED NOT NULL DEFAULT 0,
  latitude DECIMAL(9,6) NULL,
  longitude DECIMAL(9,6) NULL,
  map_x DECIMAL(5,2) NOT NULL DEFAULT 50,
  map_y DECIMAL(5,2) NOT NULL DEFAULT 50,
  address VARCHAR(255) NOT NULL DEFAULT '',
  address_en VARCHAR(255) NOT NULL DEFAULT '',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order SMALLINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE venue_translations (
  venue_id SMALLINT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,
  name VARCHAR(160) NOT NULL,
  short_description VARCHAR(400) NULL,
  description TEXT NULL,
  accessibility TEXT NULL,
  seating_info TEXT NULL,
  PRIMARY KEY (venue_id, lang),
  CONSTRAINT fk_venue_tr FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE venue_seats (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  venue_id SMALLINT UNSIGNED NOT NULL,
  section VARCHAR(10) NOT NULL,
  row_label VARCHAR(10) NOT NULL,
  seat_number SMALLINT UNSIGNED NOT NULL,
  seat_type ENUM('standard','vip','accessible') NOT NULL DEFAULT 'standard',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  pos_x SMALLINT NOT NULL,
  pos_y SMALLINT NOT NULL,
  UNIQUE KEY uq_seat (venue_id, section, row_label, seat_number),
  CONSTRAINT fk_seat_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- artists
CREATE TABLE artists (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE,
  image VARCHAR(255) NULL,
  category_id TINYINT UNSIGNED NOT NULL,
  country_code CHAR(2) NOT NULL DEFAULT 'MK',
  is_ensemble TINYINT(1) NOT NULL DEFAULT 0,
  website VARCHAR(255) NULL,
  instagram VARCHAR(80) NULL,
  facebook VARCHAR(80) NULL,
  youtube VARCHAR(80) NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_artist_cat FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE artist_translations (
  artist_id INT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,
  name VARCHAR(160) NOT NULL,
  role VARCHAR(120) NULL,
  biography TEXT NULL,
  PRIMARY KEY (artist_id, lang),
  CONSTRAINT fk_artist_tr FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- events
CREATE TABLE events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(140) NOT NULL UNIQUE,
  edition_id SMALLINT UNSIGNED NULL,
  category_id TINYINT UNSIGNED NOT NULL,
  venue_id SMALLINT UNSIGNED NOT NULL,
  starts_at DATETIME NOT NULL,
  duration_min SMALLINT UNSIGNED NOT NULL DEFAULT 90,
  image VARCHAR(255) NULL,
  price_standard DECIMAL(10,2) NOT NULL DEFAULT 0,
  price_vip DECIMAL(10,2) NULL,
  seating_mode ENUM('seated','general') NULL COMMENT 'NULL = use the venue default',
  capacity INT UNSIGNED NULL COMMENT 'general admission capacity',
  country VARCHAR(160) NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('published','draft','cancelled') NOT NULL DEFAULT 'published',
  sales_status ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_events_start (status, starts_at),
  INDEX idx_events_cat (category_id),
  INDEX idx_events_venue (venue_id),
  CONSTRAINT chk_event_price CHECK (price_standard >= 0),
  CONSTRAINT fk_event_edition FOREIGN KEY (edition_id) REFERENCES festival_editions(id) ON DELETE SET NULL,
  CONSTRAINT fk_event_cat FOREIGN KEY (category_id) REFERENCES categories(id),
  CONSTRAINT fk_event_venue FOREIGN KEY (venue_id) REFERENCES venues(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_translations (
  event_id INT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,
  title VARCHAR(200) NOT NULL,
  subtitle VARCHAR(255) NULL,
  description TEXT NULL,
  programme TEXT NULL,
  director VARCHAR(160) NULL,
  PRIMARY KEY (event_id, lang),
  FULLTEXT KEY ft_event (title, subtitle, description),
  CONSTRAINT fk_event_tr FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_artists (
  event_id INT UNSIGNED NOT NULL,
  artist_id INT UNSIGNED NOT NULL,
  role ENUM('performer','soloist','conductor','director','host') NOT NULL DEFAULT 'performer',
  sort_order SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (event_id, artist_id),
  CONSTRAINT fk_ea_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  CONSTRAINT fk_ea_artist FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_seat_blocks (
  event_id INT UNSIGNED NOT NULL,
  seat_id INT UNSIGNED NOT NULL,
  reason VARCHAR(120) NULL,
  PRIMARY KEY (event_id, seat_id),
  CONSTRAINT fk_block_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  CONSTRAINT fk_block_seat FOREIGN KEY (seat_id) REFERENCES venue_seats(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- ticketing
CREATE TABLE ticket_types (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  discount_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT chk_discount CHECK (discount_percent <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_type_translations (
  ticket_type_id TINYINT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,
  name VARCHAR(80) NOT NULL,
  description VARCHAR(255) NULL,
  PRIMARY KEY (ticket_type_id, lang),
  CONSTRAINT fk_tt_tr FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(20) NOT NULL UNIQUE,
  user_id INT UNSIGNED NULL,
  event_id INT UNSIGNED NOT NULL,
  customer_name VARCHAR(160) NOT NULL,
  customer_email VARCHAR(190) NOT NULL,
  status ENUM('pending','reserved','paid','cancelled','failed','refunded') NOT NULL DEFAULT 'pending',
  payment_method ENUM('visa','mastercard','maestro','bank_transfer','box_office') NOT NULL,
  payment_status ENUM('unpaid','paid','failed','refunded') NOT NULL DEFAULT 'unpaid',
  card_brand VARCHAR(20) NULL,
  card_last4 CHAR(4) NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  service_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  currency CHAR(3) NOT NULL DEFAULT 'MKD',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  paid_at DATETIME NULL,
  expires_at DATETIME NULL,
  INDEX idx_orders_user (user_id, created_at),
  INDEX idx_orders_status (status),
  CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_order_event FOREIGN KEY (event_id) REFERENCES events(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  ticket_type_id TINYINT UNSIGNED NOT NULL,
  seat_id INT UNSIGNED NULL,
  zone ENUM('standard','vip') NOT NULL DEFAULT 'standard',
  unit_price DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_oi_type FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id),
  CONSTRAINT fk_oi_seat FOREIGN KEY (seat_id) REFERENCES venue_seats(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tickets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  order_item_id INT UNSIGNED NOT NULL UNIQUE,
  event_id INT UNSIGNED NOT NULL,
  seat_id INT UNSIGNED NULL,
  ticket_code VARCHAR(24) NOT NULL UNIQUE,
  status ENUM('valid','reserved','used','cancelled') NOT NULL DEFAULT 'valid',
  -- a seat can be held by only one non-cancelled ticket per event (prevents double selling)
  active_seat_id INT UNSIGNED AS (IF(status = 'cancelled', NULL, seat_id)) STORED,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_event_active_seat (event_id, active_seat_id),
  INDEX idx_tickets_event (event_id, status),
  CONSTRAINT fk_ticket_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_ticket_item FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
  CONSTRAINT fk_ticket_event FOREIGN KEY (event_id) REFERENCES events(id),
  CONSTRAINT fk_ticket_seat FOREIGN KEY (seat_id) REFERENCES venue_seats(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- temporary seat holds during checkout (15 minutes)
CREATE TABLE reservations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  event_id INT UNSIGNED NOT NULL,
  seat_id INT UNSIGNED NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_hold (event_id, seat_id),
  INDEX idx_hold_exp (expires_at),
  CONSTRAINT fk_hold_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_hold_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  CONSTRAINT fk_hold_seat FOREIGN KEY (seat_id) REFERENCES venue_seats(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE favorites (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  entity_type ENUM('event','artist') NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_fav (user_id, entity_type, entity_id),
  CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- news
CREATE TABLE news_categories (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(40) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE news_category_translations (
  category_id TINYINT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,
  name VARCHAR(80) NOT NULL,
  PRIMARY KEY (category_id, lang),
  CONSTRAINT fk_ncat_tr FOREIGN KEY (category_id) REFERENCES news_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE news (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(140) NOT NULL UNIQUE,
  category_id TINYINT UNSIGNED NOT NULL,
  related_event_id INT UNSIGNED NULL,
  author_name VARCHAR(120) NOT NULL,
  image VARCHAR(255) NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('published','draft') NOT NULL DEFAULT 'published',
  published_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_news_pub (status, published_at),
  CONSTRAINT fk_news_cat FOREIGN KEY (category_id) REFERENCES news_categories(id),
  CONSTRAINT fk_news_event FOREIGN KEY (related_event_id) REFERENCES events(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE news_translations (
  news_id INT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,
  title VARCHAR(220) NOT NULL,
  excerpt VARCHAR(500) NULL,
  body MEDIUMTEXT NULL,
  PRIMARY KEY (news_id, lang),
  CONSTRAINT fk_news_tr FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- gallery
CREATE TABLE gallery_albums (
  id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE,
  album_type ENUM('event','venue','festival','backstage') NOT NULL DEFAULT 'festival',
  edition_id SMALLINT UNSIGNED NULL,
  event_id INT UNSIGNED NULL,
  venue_id SMALLINT UNSIGNED NULL,
  cover_image VARCHAR(255) NULL,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_album_ed FOREIGN KEY (edition_id) REFERENCES festival_editions(id) ON DELETE SET NULL,
  CONSTRAINT fk_album_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
  CONSTRAINT fk_album_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE gallery_album_translations (
  album_id SMALLINT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,
  title VARCHAR(160) NOT NULL,
  description VARCHAR(500) NULL,
  PRIMARY KEY (album_id, lang),
  CONSTRAINT fk_album_tr FOREIGN KEY (album_id) REFERENCES gallery_albums(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE gallery_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  album_id SMALLINT UNSIGNED NOT NULL,
  image VARCHAR(255) NOT NULL,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  CONSTRAINT fk_img_album FOREIGN KEY (album_id) REFERENCES gallery_albums(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE gallery_image_translations (
  image_id INT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,
  caption VARCHAR(255) NOT NULL,
  PRIMARY KEY (image_id, lang),
  CONSTRAINT fk_img_tr FOREIGN KEY (image_id) REFERENCES gallery_images(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- organisation
CREATE TABLE sponsors (
  id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  logo VARCHAR(255) NULL,
  website VARCHAR(255) NULL,
  level ENUM('general','gold','silver','media') NOT NULL DEFAULT 'silver',
  sort_order SMALLINT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sponsor_translations (
  sponsor_id SMALLINT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,
  description VARCHAR(500) NULL,
  PRIMARY KEY (sponsor_id, lang),
  CONSTRAINT fk_sp_tr FOREIGN KEY (sponsor_id) REFERENCES sponsors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE partners (
  id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  logo VARCHAR(255) NULL,
  website VARCHAR(255) NULL,
  partner_type ENUM('institutional','network','cultural','hospitality','media') NOT NULL DEFAULT 'cultural',
  sort_order SMALLINT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE partner_translations (
  partner_id SMALLINT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,
  description VARCHAR(500) NULL,
  PRIMARY KEY (partner_id, lang),
  CONSTRAINT fk_pt_tr FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE team_members (
  id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL UNIQUE,
  image VARCHAR(255) NULL,
  email VARCHAR(190) NULL,
  department ENUM('management','programme','production','communication','visitors','international') NOT NULL DEFAULT 'programme',
  sort_order SMALLINT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE team_member_translations (
  member_id SMALLINT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,
  name VARCHAR(120) NOT NULL,
  position VARCHAR(160) NOT NULL,
  bio VARCHAR(600) NULL,
  PRIMARY KEY (member_id, lang),
  CONSTRAINT fk_team_tr FOREIGN KEY (member_id) REFERENCES team_members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE faqs (
  id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  topic ENUM('tickets','reservations','payment','venues','refunds','accessibility','programme','festival') NOT NULL,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE faq_translations (
  faq_id SMALLINT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,
  question VARCHAR(255) NOT NULL,
  answer TEXT NOT NULL,
  PRIMARY KEY (faq_id, lang),
  CONSTRAINT fk_faq_tr FOREIGN KEY (faq_id) REFERENCES faqs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- engagement & privacy
CREATE TABLE newsletter_subscribers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  name VARCHAR(120) NULL,
  lang CHAR(2) NOT NULL DEFAULT 'mk',
  status ENUM('active','unsubscribed') NOT NULL DEFAULT 'active',
  consent_at DATETIME NOT NULL,
  token CHAR(32) NOT NULL,
  user_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_nl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NULL,
  topic ENUM('general','tickets','accessibility','press','volunteering','partnership') NOT NULL DEFAULT 'general',
  subject VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  lang CHAR(2) NOT NULL DEFAULT 'mk',
  status ENUM('new','read','answered') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_msg_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cookie_preferences (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  consent_id CHAR(32) NOT NULL UNIQUE,
  user_id INT UNSIGNED NULL,
  preferences TINYINT(1) NOT NULL DEFAULT 0,
  analytics TINYINT(1) NOT NULL DEFAULT 0,
  ip_hash CHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_cookie_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE page_views (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  path VARCHAR(255) NOT NULL,
  lang CHAR(2) NOT NULL,
  viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_views_time (viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
--  DEMO DATA
-- =====================================================================
"""


# ------------------------------------------------------------------ seat layouts
def seats_for(layout):
    """Returns list of (section, row, number, type, active, x, y) in a 1000-wide plan, stage on top."""
    S = []
    if layout == "amphitheatre":
        cx, cy = 500, 70
        sections = [("L", 200, 245), ("C", 251, 289), ("R", 295, 340)]
        # angles measured so that 270deg points straight down from the stage
        for row in range(1, 13):
            rad = 120 + row * 34
            for sec, a0, a1 in sections:
                span = math.radians(a1 - a0) * rad
                n = max(4, int(span / 24))
                for k in range(n):
                    a = math.radians(a0 + (a1 - a0) * (k + 0.5) / n)
                    x = cx + math.cos(a) * rad
                    y = cy - math.sin(a) * rad
                    num = k + 1
                    typ = "vip" if (sec == "C" and row <= 3) else "standard"
                    if row == 1 and ((sec == "L" and k < 2) or (sec == "R" and k >= n - 2)):
                        typ = "accessible"
                    S.append((sec, str(row), num, typ, 1, round(x), round(y)))
    elif layout == "nave":
        for row in range(1, 15):
            y = 120 + row * 38
            for side, sec in ((0, "A"), (1, "B")):
                for k in range(1, 9):
                    x = (130 + k * 40) if side == 0 else (530 + k * 40)
                    typ = "vip" if row <= 2 else "standard"
                    active = 1
                    # pillars of the nave block the outer seats in rows 5-6 and 10-11
                    if row in (5, 6, 10, 11) and ((side == 0 and k == 1) or (side == 1 and k == 8)):
                        active = 0
                    if row == 14 and ((side == 0 and k == 1) or (side == 1 and k == 8)):
                        typ = "accessible"
                    S.append((sec, str(row), k, typ, active, x, y))
    elif layout == "courtyard":
        for row in range(1, 11):
            for side, sec in ((0, "A"), (1, "B")):
                for k in range(1, 11):
                    x = (120 + k * 34) if side == 0 else (510 + k * 34)
                    dx = x - 500
                    y = 140 + row * 42 + (dx * dx) / 2600
                    typ = "accessible" if (row == 1 and ((side == 0 and k == 1) or (side == 1 and k == 10))) else "standard"
                    S.append((sec, str(row), k, typ, 1, round(x), round(y)))
    elif layout == "terrace":
        for row in range(1, 10):
            n = 22 + row
            for k in range(1, n + 1):
                x = 500 + (k - (n + 1) / 2) * 30
                dx = x - 500
                y = 140 + row * 46 + (dx * dx) / 3200
                typ = "vip" if row == 1 else "standard"
                if row == 9 and (k <= 1 or k >= n):
                    typ = "accessible"
                S.append(("T", str(row), k, typ, 1, round(x), round(y)))
    elif layout == "hall":
        for row in range(1, 17):
            y = 130 + row * 30
            for k in range(1, 23):
                block_gap = 0 if k <= 4 else (30 if k <= 18 else 60)
                x = 140 + k * 30 + block_gap
                typ = "standard"
                if 5 <= row <= 8 and 5 <= k <= 18:
                    typ = "vip"
                if row == 16 and k in (1, 2, 21, 22):
                    typ = "accessible"
                S.append(("P", str(row), k, typ, 1, x, y))
        for row in range(1, 5):
            y = 680 + row * 30
            for k in range(1, 25):
                x = 125 + k * 30 + (30 if k > 12 else 0)
                S.append(("B", str(row), k, "standard", 1, x, y))
    return S


def main():
    # ---------------- artwork

    OUT.append(DDL)
    insert("roles", ["id", "code", "name"], [(1, "admin", "Administrator"), (2, "user", "Registered user")])

    # users
    hashes = {}
    urows = []
    for i, (role, fn, ln, email, pw, lang, nl) in enumerate(USERS, start=1):
        if pw not in hashes:
            hashes[pw] = php_hash(pw)
        active = 0 if email.startswith("inactive") else 1
        urows.append((i, 1 if role == "admin" else 2, fn, ln, email, hashes[pw], None, lang, nl, active,
                      datetime(2026, 3, 1) + timedelta(days=i * 17), None))
    insert("users", ["id", "role_id", "first_name", "last_name", "email", "password_hash", "phone", "preferred_lang",
                     "newsletter", "is_active", "created_at", "last_login_at"], urows)
    user_id = {u[3]: i for i, u in enumerate(USERS, start=1)}

    # categories
    cat_id = {}
    crow, ctr = [], []
    for i, (slug, icon, color, names) in enumerate(CATEGORIES, start=1):
        cat_id[slug] = i
        crow.append((i, slug, icon, color, i))
        for lg, nm in names.items():
            ctr.append((i, lg, nm))
    insert("categories", ["id", "slug", "icon", "color", "sort_order"], crow)
    insert("category_translations", ["category_id", "lang", "name"], ctr)

    # editions
    ed_id = {}
    erow, etr = [], []
    for i, (year, num, s, e, vis, cnt, tr) in enumerate(EDITIONS, start=1):
        ed_id[year] = i
        erow.append((i, year, num, s, e, vis, cnt))
        for lg, (theme, summ) in tr.items():
            etr.append((i, lg, theme, summ))
    insert("festival_editions", ["id", "year", "edition_number", "start_date", "end_date", "visitors", "countries"], erow)
    insert("edition_translations", ["edition_id", "lang", "theme", "summary"], etr)

    # venues + seats
    venue_id, venue_layout, venue_seats_map, venue_meta = {}, {}, {}, {}
    vrow, vtr, srow = [], [], []
    seat_pk = 0
    for i, v in enumerate(VENUES, start=1):
        venue_id[v["slug"]] = i
        venue_layout[v["slug"]] = v["layout"]
        img = f"assets/img/venues/{v['slug']}.jpg"
        seats = seats_for(v["layout"]) if v["seating"] == "seated" else []
        cap = sum(1 for s in seats if s[4]) if seats else v.get("capacity", 500)
        venue_meta[v["slug"]] = {"capacity": cap}
        vrow.append((i, v["slug"], img, v["layout"], v["seating"], cap, v["lat"], v["lng"], v["map"][0], v["map"][1],
                     v["address"], v["address_en"], 1, i))
        for lg in ("mk", "en"):
            t = v[lg]
            vtr.append((i, lg, t["name"], t["short"], t["desc"], t["access"], t["seating"]))
        ids = []
        for (sec, row, num, typ, act, x, y) in seats:
            seat_pk += 1
            srow.append((seat_pk, i, sec, row, num, typ, act, x, y))
            ids.append((seat_pk, sec, row, num, typ, act))
        venue_seats_map[v["slug"]] = ids
    insert("venues", ["id", "slug", "image", "layout_type", "seating_type", "capacity", "latitude", "longitude", "map_x", "map_y",
                      "address", "address_en", "is_active", "sort_order"], vrow)
    insert("venue_translations", ["venue_id", "lang", "name", "short_description", "description", "accessibility", "seating_info"], vtr)
    insert("venue_seats", ["id", "venue_id", "section", "row_label", "seat_number", "seat_type", "is_active", "pos_x", "pos_y"], srow)

    # artists
    artist_id = {}
    arow, atr = [], []
    for i, a in enumerate(ARTISTS, start=1):
        artist_id[a["slug"]] = i
        arow.append((i, a["slug"], f"assets/img/artists/{a['slug']}.jpg", cat_id[a["cat"]], a["country"], a.get("ensemble", 0),
                     a.get("web"), a.get("ig"), None, None, a.get("featured", 0), 1, datetime(2026, 2, 1) + timedelta(days=i)))
        for lg in ("mk", "en"):
            nm, role, bio = a[lg]
            atr.append((i, lg, nm, role, bio))
    insert("artists", ["id", "slug", "image", "category_id", "country_code", "is_ensemble", "website", "instagram", "facebook",
                       "youtube", "is_featured", "is_published", "created_at"], arow)
    insert("artist_translations", ["artist_id", "lang", "name", "role", "biography"], atr)

    # ticket types
    tt_id = {}
    trow, ttr = [], []
    for i, (code, disc, names) in enumerate(TICKET_TYPES, start=1):
        tt_id[code] = (i, disc)
        trow.append((i, code, disc, i, 1))
        for lg, (nm, ds) in names.items():
            ttr.append((i, lg, nm, ds))
    insert("ticket_types", ["id", "code", "discount_percent", "sort_order", "is_active"], trow)
    insert("ticket_type_translations", ["ticket_type_id", "lang", "name", "description"], ttr)

    # events
    event_id, event_info = {}, {}
    evrow, evtr, earow = [], [], []
    for i, e in enumerate(EVENTS, start=1):
        event_id[e["slug"]] = i
        start = datetime.strptime(e["start"], "%Y-%m-%d %H:%M")
        vslug = e["venue"]
        vseat = next(v["seating"] for v in VENUES if v["slug"] == vslug)
        mode = e["seating"] or vseat
        cap = None
        if mode == "general":
            cap = venue_meta[vslug]["capacity"] if vseat == "general" else (300 if e["cat"] == "film" else 120)
        event_info[e["slug"]] = dict(id=i, start=start, mode=mode, cap=cap, venue=vslug, e=e)
        evrow.append((i, e["slug"], ed_id.get(e["year"]), cat_id[e["cat"]], venue_id[vslug], start, e["dur"],
                      photos.event_photo(e['slug'], e['cat']), e["price"], e["vip"], e["seating"], cap, e["country"],
                      e["featured"], e["status"], e["sales"], datetime(2026, 9, 1)))
        for lg in ("mk", "en"):
            title, sub, desc, prog, director = e[lg]
            evtr.append((i, lg, title, sub, desc, prog, director or None))
        for k, (aslug, role) in enumerate(e["artists"]):
            earow.append((i, artist_id[aslug], role, k))
    insert("events", ["id", "slug", "edition_id", "category_id", "venue_id", "starts_at", "duration_min", "image",
                      "price_standard", "price_vip", "seating_mode", "capacity", "country", "is_featured", "status",
                      "sales_status", "created_at"], evrow)
    insert("event_translations", ["event_id", "lang", "title", "subtitle", "description", "programme", "director"], evtr)
    insert("event_artists", ["event_id", "artist_id", "role", "sort_order"], earow)

    # camera platform for the opening gala (event-specific unavailable seats)
    og = event_info["opening-gala-beethoven-ninth-2027"]
    blocks = [s for s in venue_seats_map[og["venue"]] if s[1] == "C" and s[2] == "7" and 5 <= s[3] <= 8]
    insert("event_seat_blocks", ["event_id", "seat_id", "reason"], [(og["id"], s[0], "Camera platform / TV") for s in blocks])
    blocked = {(og["id"], s[0]) for s in blocks}

    # ---------------- sales (box office + demo users)
    orow, oirow, tkrow = [], [], []
    oid = oiid = tid = 0
    code_seen = set()

    def ticket_code(evid, n):
        h = hashlib.sha1(f"{evid}-{n}-ohrid".encode()).hexdigest()[:10].upper()
        while h in code_seen:
            h = hashlib.sha1((h + "x").encode()).hexdigest()[:10].upper()
        code_seen.add(h)
        return f"OL-{h[:5]}-{h[5:]}"

    def pick_type():
        x = R.random()
        return "adult" if x < 0.62 else "student" if x < 0.80 else "senior" if x < 0.93 else "child"

    def make_order(info, seat_rows, qty_ga, user=None, method=None, status="paid", created=None, types=None, fee=None):
        nonlocal oid, oiid, tid
        e = info["e"]
        oid += 1
        if method is None:
            method = R.choice(["visa", "visa", "mastercard", "maestro", "box_office"])
        past = info["start"] < SEED_NOW
        if created is None:
            lo = info["start"] - timedelta(days=60) if past else datetime(2026, 9, 15, 9)
            hi = info["start"] - timedelta(hours=3) if past else SEED_NOW - timedelta(hours=2)
            created = lo + (hi - lo) * R.random()
        uname, uemail = ("Box office", "boxoffice@ohridsummer.test")
        if user:
            u = USERS[user - 1]
            uname, uemail = f"{u[1]} {u[2]}", u[3]
        items = []
        n = len(seat_rows) if seat_rows else qty_ga
        for k in range(n):
            tcode = types[k] if types else pick_type()
            ttid, disc = tt_id[tcode]
            seat = seat_rows[k] if seat_rows else None
            zone = "vip" if (seat and seat[4] == "vip" and e["vip"]) else "standard"
            base = e["vip"] if zone == "vip" else e["price"]
            price = round(base * (100 - disc) / 100)
            items.append((ttid, seat[0] if seat else None, zone, price))
        sub = sum(x[3] for x in items)
        if fee is None:
            fee = 0 if method == "box_office" else 50
        pay_status = {"paid": "paid", "reserved": "unpaid", "pending": "unpaid", "cancelled": "unpaid"}[status]
        tstatus = {"paid": "used" if past else "valid", "reserved": "reserved", "pending": "reserved", "cancelled": "cancelled"}[status]
        brand = method.capitalize() if method in ("visa", "mastercard", "maestro") else None
        last4 = f"{R.randint(0, 9999):04d}" if brand else None
        expires = Raw("DATE_ADD(NOW(), INTERVAL 72 HOUR)") if status == "reserved" else (Raw("DATE_ADD(NOW(), INTERVAL 5 DAY)") if status == "pending" else None)
        yy = info["start"].strftime("%y")
        orow.append((oid, f"OL{yy}-{oid:06d}", user, info["id"], uname, uemail, status, method, pay_status, brand, last4,
                     sub, fee, sub + fee, "MKD", created, created if status == "paid" else None, expires))
        for (ttid, sid, zone, price) in items:
            oiid += 1
            tid += 1
            oirow.append((oiid, oid, ttid, sid, zone, price))
            tkrow.append((tid, oid, oiid, info["id"], sid, ticket_code(info["id"], tid), tstatus, created))
        return oid

    demo_user = user_id["user@ohridsummer.test"]
    reserved_for_demo = {
        "opening-gala-beethoven-ninth-2027": ("C", "4", [10, 11], ["adult", "student"], "visa", "paid"),
        "medea-ilievski-2027": ("C", "9", [6, 7], ["adult", "adult"], "box_office", "reserved"),
        "byzantion-night-chant-2027": ("A", "6", [4, 5], ["adult", "senior"], "bank_transfer", "pending"),
        "ljubov-opening-gala-2026": ("C", "6", [3, 4], ["adult", "adult"], "mastercard", "paid"),
    }
    demo_taken = {}
    for slug, (sec, row, nums, types, method, st) in reserved_for_demo.items():
        info = event_info[slug]
        rows = [s for s in venue_seats_map[info["venue"]] if s[1] == sec and s[2] == row and s[3] in nums]
        demo_taken[slug] = rows
        created = info["start"] - timedelta(days=20) if info["start"] < SEED_NOW else SEED_NOW - timedelta(days=2)
        make_order(info, rows, 0, user=demo_user, method=method, status=st, created=created, types=types)
    # GA event for the demo user
    make_order(event_info["balkan-brass-plaoshnik-2027"], None, 3, user=demo_user, method="maestro", status="paid",
               created=SEED_NOW - timedelta(days=1), types=["adult", "adult", "child"])

    other_users = [user_id["john@ohridsummer.test"], user_id["sara@ohridsummer.test"]]
    for slug, info in event_info.items():
        e = info["e"]
        if info["mode"] == "seated":
            taken = {s[0] for s in demo_taken.get(slug, [])}
            avail = [s for s in venue_seats_map[info["venue"]] if s[5] == 1 and s[0] not in taken and (info["id"], s[0]) not in blocked]
            target = int(len(avail) * e["occ"]) if e["occ"] < 1 else len(avail)
            # prefer front rows / centre
            avail.sort(key=lambda s: int(s[2]) * 0.6 + R.random() * 8)
            chosen = sorted(avail[:target], key=lambda s: (s[1], int(s[2]), s[3]))
            k = 0
            while k < len(chosen):
                n = min(R.choice([1, 2, 2, 2, 3, 4]), len(chosen) - k)
                usr = R.choice(other_users) if R.random() < 0.06 else None
                make_order(info, chosen[k:k + n], 0, user=usr)
                k += n
        else:
            target = int(info["cap"] * e["occ"])
            sold = 3 if slug == "balkan-brass-plaoshnik-2027" else 0
            while sold < target:
                n = min(R.choice([1, 2, 2, 3, 4, 5]), target - sold)
                make_order(info, None, n)
                sold += n

    insert("orders", ["id", "order_number", "user_id", "event_id", "customer_name", "customer_email", "status", "payment_method",
                      "payment_status", "card_brand", "card_last4", "subtotal", "service_fee", "total", "currency",
                      "created_at", "paid_at", "expires_at"], orow, chunk=300)
    insert("order_items", ["id", "order_id", "ticket_type_id", "seat_id", "zone", "unit_price"], oirow, chunk=600)
    insert("tickets", ["id", "order_id", "order_item_id", "event_id", "seat_id", "ticket_code", "status", "created_at"], tkrow, chunk=600)

    # favorites for demo user
    insert("favorites", ["user_id", "entity_type", "entity_id", "created_at"], [
        (demo_user, "event", event_id["la-traviata-2027"], SEED_NOW - timedelta(days=3)),
        (demo_user, "event", event_id["lake-of-light-ballet-2027"], SEED_NOW - timedelta(days=2)),
        (demo_user, "event", event_id["okafor-trio-sunset-2027"], SEED_NOW - timedelta(days=1)),
        (demo_user, "artist", artist_id["elena-markovska"], SEED_NOW - timedelta(days=4)),
        (demo_user, "artist", artist_id["marie-claire-dubois"], SEED_NOW - timedelta(days=4)),
    ])

    # news
    ncat = {}
    insert("news_categories", ["id", "slug"], [(i, s) for i, (s, _) in enumerate(NEWS_CATEGORIES, start=1)])
    nct = []
    for i, (s, names) in enumerate(NEWS_CATEGORIES, start=1):
        ncat[s] = i
        nct += [(i, lg, nm) for lg, nm in names.items()]
    insert("news_category_translations", ["category_id", "lang", "name"], nct)
    nrow, ntr = [], []
    news_pal = {"announcements": ("gold", "music"), "programme": ("night", "music"), "tickets": ("lake", "film"),
                "behind-the-scenes": ("fresco", "theatre"), "heritage": ("stone", "literature")}
    for i, n in enumerate(NEWS, start=1):
        pal, motif_cat = news_pal[n["cat"]]
        if n["event"]:
            motif_cat = next(e["cat"] for e in EVENTS if e["slug"] == n["event"])
        nrow.append((i, n["slug"], ncat[n["cat"]], event_id.get(n["event"]) if n["event"] else None, n["author"],
                     photos.news_photo(n['slug']), n["featured"], "published", n["date"]))
        for lg in ("mk", "en"):
            t, ex, body = n[lg]
            ntr.append((i, lg, t, ex, body))
    insert("news", ["id", "slug", "category_id", "related_event_id", "author_name", "image", "is_featured", "status", "published_at"], nrow)
    insert("news_translations", ["news_id", "lang", "title", "excerpt", "body"], ntr)

    # gallery
    alrow, altr, girow, gitr = [], [], [], []
    gid = 0
    for i, (slug, typ, ref, count, mk, en, pal) in enumerate(ALBUMS, start=1):
        ev_ref = event_id.get(ref) if typ == "event" else None
        ve_ref = venue_id.get(ref) if typ == "venue" else None
        ed_ref = ed_id.get(int(ref)) if typ == "festival" else (ed_id.get(event_info[ref]["e"]["year"]) if typ == "event" else None)
        atype = "backstage" if slug.startswith("backstage") else typ
        imgs = []
        for k in range(count):
            gid += 1
            fn = f"gallery/{slug}-{k + 1}.jpg"
            imgs.append(gid)
            girow.append((gid, i, "assets/img/" + fn, k))
            cap = photos.GALLERY[slug][k][1:] if slug in photos.GALLERY else CAPTIONS[(i * 3 + k) % len(CAPTIONS)]
            gitr.append((gid, "mk", cap[0]))
            gitr.append((gid, "en", cap[1]))
        alrow.append((i, slug, atype, ed_ref, ev_ref, ve_ref, f"assets/img/gallery/{slug}-1.jpg", i, 1))
        altr.append((i, "mk", mk[0], mk[1]))
        altr.append((i, "en", en[0], en[1]))
    insert("gallery_albums", ["id", "slug", "album_type", "edition_id", "event_id", "venue_id", "cover_image", "sort_order", "is_published"], alrow)
    insert("gallery_album_translations", ["album_id", "lang", "title", "description"], altr)
    insert("gallery_images", ["id", "album_id", "image", "sort_order"], girow)
    insert("gallery_image_translations", ["image_id", "lang", "caption"], gitr)

    # sponsors & partners
    sprow, sptr = [], []
    for i, (name, level, web, mk, en) in enumerate(SPONSORS, start=1):
        fn = f"logos/sponsor-{i}.png"
        sprow.append((i, name, "assets/img/" + fn, web, level, i, 1))
        sptr += [(i, "mk", mk), (i, "en", en)]
    insert("sponsors", ["id", "name", "logo", "website", "level", "sort_order", "is_active"], sprow)
    insert("sponsor_translations", ["sponsor_id", "lang", "description"], sptr)
    prow, ptr = [], []
    for i, (name, typ, web, mk, en) in enumerate(PARTNERS, start=1):
        fn = f"logos/partner-{i}.png"
        prow.append((i, name, "assets/img/" + fn, web, typ, i, 1))
        ptr += [(i, "mk", mk), (i, "en", en)]
    insert("partners", ["id", "name", "logo", "website", "partner_type", "sort_order", "is_active"], prow)
    insert("partner_translations", ["partner_id", "lang", "description"], ptr)

    # team
    tmrow, tmtr = [], []
    for i, (dept, slug, mk, en) in enumerate(TEAM, start=1):
        tmrow.append((i, slug, f"assets/img/team/{slug}.jpg", f"{slug}@ohridsummer.test", dept, i, 1))
        tmtr += [(i, "mk", mk[0], mk[1], mk[2]), (i, "en", en[0], en[1], en[2])]
    insert("team_members", ["id", "slug", "image", "email", "department", "sort_order", "is_active"], tmrow)
    insert("team_member_translations", ["member_id", "lang", "name", "position", "bio"], tmtr)

    # faq
    frow, ftr = [], []
    for i, (topic, tr) in enumerate(FAQ, start=1):
        frow.append((i, topic, i, 1))
        for lg, (qq, aa) in tr.items():
            ftr.append((i, lg, qq, aa))
    insert("faqs", ["id", "topic", "sort_order", "is_active"], frow)
    insert("faq_translations", ["faq_id", "lang", "question", "answer"], ftr)

    # newsletter, messages, analytics
    nl = []
    names = ["ana", "bojana", "kiril", "elena", "marko", "lea", "tom", "giulia", "jonas", "amelie", "arben", "vesna"]
    langs = ["mk", "mk", "mk", "en", "mk", "sq", "en", "it", "de", "fr", "sq", "mk"]
    for k, nm in enumerate(names, start=1):
        nl.append((f"{nm}{k}@example.com", None, langs[k - 1], "active" if k != 7 else "unsubscribed",
                   SEED_NOW - timedelta(days=40 - k * 3), hashlib.md5(nm.encode()).hexdigest(), None))
    nl.append(("user@ohridsummer.test", "Марија Демо", "mk", "active", SEED_NOW - timedelta(days=30),
               hashlib.md5(b"demo").hexdigest(), demo_user))
    nl.append(("sara@ohridsummer.test", "Sara Krasniqi", "sq", "active", SEED_NOW - timedelta(days=12),
               hashlib.md5(b"sara").hexdigest(), user_id["sara@ohridsummer.test"]))
    insert("newsletter_subscribers", ["email", "name", "lang", "status", "consent_at", "token", "user_id"], nl)
    insert("contact_messages", ["name", "email", "phone", "topic", "subject", "message", "lang", "status", "created_at"], [
        ("Елена Т.", "elena.t@example.com", None, "accessibility", "Места за инвалидска количка",
         "Добар ден, би сакала да резервирам место за инвалидска количка за „Медеја“. Дали е можно?", "mk", "new", SEED_NOW - timedelta(hours=20)),
        ("Thomas Becker", "t.becker@example.com", "+49 30 000000", "tickets", "Group booking",
         "Hello, we are a choir of 24 people visiting Ohrid in July. Do you offer group discounts for the opening gala?", "en", "read", SEED_NOW - timedelta(days=2)),
        ("Giulia Rossi", "giulia.r@example.com", None, "volunteering", "Volontariato 2027",
         "Buongiorno, vorrei partecipare al programma di volontariato. Parlo italiano, inglese e un po' di macedone.", "it", "answered", SEED_NOW - timedelta(days=5)),
    ])
    pv = []
    paths = ["/", "/programme.php", "/event.php", "/artists.php", "/news.php", "/calendar.php", "/venues.php", "/gallery.php"]
    for d in range(14):
        for _ in range(R.randint(20, 60)):
            pv.append((R.choice(paths), R.choice(["mk", "mk", "mk", "en", "en", "sq", "de", "it", "fr"]),
                       SEED_NOW - timedelta(days=d, minutes=R.randint(0, 1400))))
    insert("page_views", ["path", "lang", "viewed_at"], pv, chunk=800)

    OUT.append("\n-- end of schema.sql\n")
    with open(os.path.join(ROOT, "database", "schema.sql"), "w", encoding="utf-8") as f:
        f.write("".join(OUT))
    print("seats", seat_pk, "orders", oid, "tickets", tid, "images ok")


if __name__ == "__main__":
    main()
