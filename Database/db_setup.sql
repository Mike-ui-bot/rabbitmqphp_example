CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    balance decimal(18,2) DEFAULT 10000.00;
    PRIMARY KEY (id)
);

CREATE TABLE `crypto` (
  `asset_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `price` decimal(20,8) DEFAULT NULL,
  `market_cap` decimal(30,8) DEFAULT NULL,
  `supply` decimal(30,8) DEFAULT NULL,
  `max_supply` decimal(30,8) DEFAULT NULL,
  `volume` decimal(30,8) DEFAULT NULL,
  `change_percent` decimal(10,4) DEFAULT NULL,
  `data` json NOT NULL,
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE portfolio (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    coin_symbol VARCHAR(10) DEFAULT NULL,
    coin_name VARCHAR(50) NOT NULL DEFAULT 'Unknown Coin',
    quantity DECIMAL(18,8) DEFAULT NULL,
    average_price VARCHAR(50) NOT NULL DEFAULT 'Unknown price',
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE transactions (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    coin_symbol VARCHAR(10) NOT NULL,
    coin_name VARCHAR(50) NOT NULL,
    amount DECIMAL(18,8) NOT NULL,
    price DECIMAL(18,8) NOT NULL,
    type ENUM('buy','sell') NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE price_alerts (
    coin_symbol VARCHAR(10) NOT NULL UNIQUE,
    last_price DECIMAL(30,8) NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (coin_symbol)
);

/* Populate price_alerts with data from the crypto table
INSERT INTO price_alerts (coin_symbol, last_price, last_updated)
SELECT symbol, price, last_updated
FROM crypto
WHERE symbol NOT IN (SELECT coin_symbol FROM price_alerts); */
