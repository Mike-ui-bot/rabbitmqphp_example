<?php
require_once __DIR__ . '/../RabbitMQ/RabbitMQLib.inc';
require_once 'databaseConnect.php';

use RabbitMQ\RabbitMQServer;
use PhpAmqpLib\Message\AMQPMessage;

try {
    global $db;
    echo "Trying to connect to RabbitMQ...\n";


    // Initialize RabbitMQServer (using "Database" from RabbitMQ.ini)
    $rbMQs = new RabbitMQServer(__DIR__ . '/../RabbitMQ/RabbitMQ.ini', 'Database');


    $rbMQs->consume(function ($message) use ($db) {
        echo "Message: $message\n";
   
        // Decode JSON message
        $data = json_decode($message, true);
        $response = ["status" => "error", "message" => "Unknown error"];
       
        if (!isset($data['action'])) {
            echo "Error: Action not specified.\n";
            $response["message"] = "Action not specified.";
        } else {
            $action = $data['action'];
       
            switch ($action) {
                // Registration handling
                case "register":
                    $email = $data['email'];
                    $username = $data['username'];
                    $password = $data['password'];
           
                    // Check if email or username already exists
                    $stmt = $db->prepare("SELECT email, username FROM users WHERE email = ? OR username = ?");
                    $stmt->bind_param("ss", $email, $username);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $existingUser = $result->fetch_assoc();
                    $stmt->close();


                    if ($existingUser) {
                        if ($existingUser['email'] === $email) {
                            echo "Error: Email '$email' is already in use.\n";
                            $response = ["status" => "email_error", "message" => "Email $email is already in use. Please use a different email."];
                        } elseif ($existingUser['username'] === $username) {
                            echo "Error: Username '$username' is already taken.\n";
                            $response = ["status" => "username_error", "message" => "Username $username is already taken. Please choose another."];
                        }
                    } else {
                        // Insert data into database
                        $stmt = $db->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)");
                        $stmt->bind_param("sss", $email, $username, $password);


                        if ($stmt->execute()) {
                            echo "User '$username' successfully registered and added to database.\n";
                            $response = ["status" => "success", "message" => "Registration successful! Please log in."];
                        } else {
                            echo "Error: " . $stmt->error . "\n";
                            $response = ["status" => "error", "message" => "Sorry, we were unable to register you at this time."];
                        }
                        $stmt->close();
                    } break;


                // Get user portfolio
                case "get_portfolio":
                    $username = $data['username'];


                    // Get user_id from users table
                    $stmt = $db->prepare("SELECT id, balance FROM users WHERE username = ?");
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $stmt->bind_result($user_id, $balance);
                    $stmt->fetch();
                    $stmt->close();


                    if (!$user_id) {
                        echo "Error: No user found for username '$username'\n";
                        $response = ["status" => "error", "message" => "User not found"];
                    } else {
                        echo "DEBUG: Retrieved user_id = $user_id for username = $username\n"; // Log user_id


                        // Fetch portfolio using user_id
                        $stmt = $db->prepare("SELECT coin_name, coin_symbol, quantity, average_price FROM portfolio WHERE user_id = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $portfolio = [];


                        while ($row = $result->fetch_assoc()) {
                            $portfolio[] = $row;
                        }
                        $stmt->close();


                        echo "DEBUG: Retrieved portfolio: " . json_encode($portfolio) . "\n"; // Log portfolio data


                        $response = ["status" => "success", "portfolio" => $portfolio, "balance" => (float)$balance];
                    } break;


                // Get user balance
                case "get_balance":
                    $username = $data['username'];


                    // Get user_id from users table
                    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $stmt->bind_result($id);
                    $stmt->fetch();
                    $stmt->close();


                    if (!$id) {
                        echo "Error: No user found for username '$username'\n";
                        $response = ["status" => "error", "message" => "User not found"];
                    } else {
                        echo "DEBUG: Retrieved user_id = $user_id for username = $username\n"; // Log user_id


                        // Fetch balance from portfolio using user_id
                        $stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        $stmt->bind_result($balance);
                        $stmt->fetch();
                        $stmt->close();


                        echo "DEBUG: Retrieved balance = $balance\n"; // Log balance


                        $response = ["status" => "success", "balance" => $balance];
                    } break;

                // Add funds
                case "add_funds":
                    $username = $data['username'];
                    $amount = $data['amount']; // Amount to add (e.g., 10,000)
                    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $stmt->bind_result($id);
                    $stmt->fetch();
                    $stmt->close();


                    if (!$id) {
                        echo "Error: No user found for username '$username'\n";
                        $response = ["status" => "error", "message" => "User not found"];
                    } else {
                        echo "DEBUG: Retrieved user_id = $user_id for username = $username\n"; // Log user_id


                        $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                        $stmt->bind_param("di", $amount, $id);
                       
                        if ($stmt->execute()) {
                            // Fetch the updated balance after adding funds
                            $stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
                            $stmt->bind_param("i", $id);
                            $stmt->execute();
                            $stmt->bind_result($new_balance);
                            $stmt->fetch();
                            $stmt->close();


                            $response = [
                                "status" => "success",
                                "message" => "Funds added successfully!",
                                "new_balance" => $new_balance
                            ];
                        } else {
                            $response = ["status" => "error", "message" => "Failed to add funds."];
                        }
                    } break;


                // Get transactions
                case "getTransactions":
                    $username = $data['username'];


                    // Get user_id from users table
                    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $stmt->bind_result($user_id);
                    $stmt->fetch();
                    $stmt->close();


                    if (!$user_id) {
                        echo "Error: No user found for username '$username'\n";
                        $response = ["status" => "error", "message" => "User not found"];
                    } else {
                        echo "DEBUG: Retrieved user_id = $user_id for username = $username\n"; // Log user_id


                        // Fetch transactions for the user from the transactions table
                        $stmt = $db->prepare("SELECT coin_symbol, coin_name, amount, price, action, timestamp FROM transactions WHERE user_id = ? ORDER BY timestamp DESC");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $transactions = [];


                        while ($row = $result->fetch_assoc()) {
                            $transactions[] = $row;
                        }
                        $stmt->close();


                        if (empty($transactions)) {
                            // If no transactions
                            echo "DEBUG: No transactions found for user_id = $user_id\n";
                            $response = ["status" => "success", "transactions" => []];
                        } else {
                            //  retrieved transactions
                            echo "DEBUG: Retrieved transactions: " . json_encode($transactions) . "\n";
                            $response = ["status" => "success", "transactions" => $transactions];
                        }
                    } break;


                // Buy
               
                case "buy":
                    $username = $data['username'];
                    $coinSymbol = $data['coin_symbol'];
                    $coinName = $data['coin_name'];
                    $amount = $data['amount'];
                    $coinPrice = $data['price'];
           
                    $stmt = $db->prepare("SELECT id, balance FROM users WHERE username = ?");
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $stmt->bind_result($user_id, $balance);
                    $stmt->fetch();
                    $stmt->close();
           
                    $totalPurchaseAmount = $amount * $coinPrice;
           
                    if ($balance < $totalPurchaseAmount) {
                        $response = ["status" => "error", "message" => "Insufficient balance."];
                    } else {
                        // Deduct balance from users table
                        $newBalance = $balance - $totalPurchaseAmount;
                        $stmt = $db->prepare("UPDATE users SET balance = ? WHERE id = ?");
                        $stmt->bind_param("di", $newBalance, $user_id);
                        $stmt->execute();
                        $stmt->close();
           
                        // Check if the user already has this coin in their portfolio
                        $stmt = $db->prepare("SELECT quantity FROM portfolio WHERE user_id = ? AND coin_symbol = ?");
                        $stmt->bind_param("is", $user_id, $coinSymbol);
                        $stmt->execute();
                        $stmt->bind_result($currentQuantity);
                        $stmt->fetch();
                        $stmt->close();
           
                        if ($currentQuantity > 0) {
                            // Update existing portfolio entry
                            $newQuantity = $currentQuantity + $amount;
                            $stmt = $db->prepare("UPDATE portfolio SET quantity = ?, average_price = ? WHERE user_id = ? AND coin_symbol = ?");
                            $stmt->bind_param("ddis", $newQuantity, $coinPrice, $user_id, $coinSymbol);
                        } else {
                            // Insert new portfolio entry
                            $stmt = $db->prepare("INSERT INTO portfolio (user_id, coin_symbol, coin_name, quantity, average_price) VALUES (?, ?, ?, ?, ?)");
                            $stmt->bind_param("issdd", $user_id, $coinSymbol, $coinName, $amount, $coinPrice);
                        }
                        $stmt->execute();
                        $stmt->close();
           
                        // Log transaction
                        $stmt = $db->prepare("INSERT INTO transactions (user_id, coin_symbol, coin_name, amount, price, action) VALUES (?, ?, ?, ?, ?, 'buy')");
                        $stmt->bind_param("issdd", $user_id, $coinSymbol, $coinName, $amount, $coinPrice);
                        $stmt->execute();
                        $stmt->close();
           
                        $response = ["status" => "success", "message" => "Purchase successful."];
                    }
                    break;


                // Sell
                case "sell":
                    $username = $data['username'];
                    $coinSymbol = $data['coin_symbol'];
                    $coinName = $data['coin_name'];
                    $amount = $data['amount'];
                    $coinPrice = $data['price'];
                    $totalSellAmount = $data['profit'];
           
                    // Get user_id and balance
                    $stmt = $db->prepare("SELECT id, balance FROM users WHERE username = ?");
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $stmt->bind_result($user_id, $currentBalance);
                    $stmt->fetch();
                    $stmt->close();
           
                    // Get user's coin holdings
                    $stmt = $db->prepare("SELECT quantity FROM portfolio WHERE user_id = ? AND coin_symbol = ?");
                    $stmt->bind_param("is", $user_id, $coinSymbol);
                    $stmt->execute();
                    $stmt->bind_result($currentQuantity);
                    $stmt->fetch();
                    $stmt->close();
           
                    if ($currentQuantity < $amount) {
                        $response = ["status" => "error", "message" => "Insufficient coin quantity."];
                    } else {
                        // Deduct coins and potentially remove the coin from portfolio
                        $newQuantity = $currentQuantity - $amount;
                        if ($newQuantity <= 0) {
                            $stmt = $db->prepare("DELETE FROM portfolio WHERE user_id = ? AND coin_symbol = ?");
                        } else {
                            $stmt = $db->prepare("UPDATE portfolio SET quantity = ? WHERE user_id = ? AND coin_symbol = ?");
                        }
           
                        if ($newQuantity <= 0){
                            $stmt->bind_param("is", $user_id, $coinSymbol);
                        } else {
                            $stmt->bind_param("dis", $newQuantity, $user_id, $coinSymbol);
                        }
           
                        $stmt->execute();
                        $stmt->close();
           
                        // Update balance
                        $newBalance = $currentBalance + $totalSellAmount;
                        $stmt = $db->prepare("UPDATE users SET balance = ? WHERE id = ?");
                        $stmt->bind_param("di", $newBalance, $user_id);
                        $stmt->execute();
                        $stmt->close();
           
                        // Log transaction
                        $stmt = $db->prepare("INSERT INTO transactions (user_id, coin_symbol, coin_name, amount, price, action, timestamp) VALUES (?, ?, ?, ?, ?, 'sell', NOW())");
                        $stmt->bind_param("issdd", $user_id, $coinSymbol, $coinName, $amount, $coinPrice);
                        $stmt->execute();
                        $stmt->close();
           
                        $response = ["status" => "success", "message" => "Sale successful."];
                    }
                    break;


                // Login handling
                case "login":
                    $email = $data['email'];
                    $password = $data['password'];
           
                    // Verify user credentials
                    $stmt = $db->prepare("SELECT username, password FROM users WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $stmt->bind_result($dbUsername, $dbPassword);
                    $stmt->fetch();
                    $stmt->close();
                   
                    if ($password === $dbPassword) {
                        echo "Login successful for user '$email'.\n";
                        $response = ["status" => "success", "message" => "Login successful!", "username" => $dbUsername];
                    } else {
                        echo "Error: Incorrect password for user '$email'.\n";
                        $response = ["status" => "error", "message" => "Invalid email or password."];
                    } break;


                // Get top 100 Crypto
                case "getTop100Crypto":
                    $query = "SELECT asset_id, name, symbol, price, market_cap, supply, max_supply, volume, change_percent, crypto_rank FROM (
                            SELECT asset_id, name, symbol, price, market_cap, supply, max_supply, volume, change_percent,
                            RANK() OVER (ORDER BY market_cap DESC) AS crypto_rank
                            FROM crypto
                            ) ranked_crypto
                            LIMIT 100";            
       
                    $result = $db->query($query);


                    if ($result && $result->num_rows > 0) {
                        $cryptos = [];
                        while ($row = $result->fetch_assoc()) {
                            $cryptos[] = [
                                'id' => $row['asset_id'],
                                'name' => $row['name'],
                                'symbol' => $row['symbol'],
                                'priceUsd' => $row['price'],
                                'marketCapUsd' => $row['market_cap'],
                                'supply' => $row['supply'],
                                'maxSupply' => $row['max_supply'],
                                'volumeUsd24Hr' => $row['volume'],
                                'changePercent24Hr' => $row['change_percent'],
                                'rank' => $row['crypto_rank'],
                            ];
                        }
                        $response = ["status" => "success", "data" => $cryptos];
                    } else {
                        $response = ["status" => "error", "message" => "Failed to fetch top 100 cryptocurrencies from the database."];
                    } break;

                // Email notifications everytime coin price changes (dependent on cron, so every 5 minutes in this case)
                case "get_coin_price":
                    $symbol = $data['symbol'];
                
                    // Fetch current price from the crypto table
                    $stmt = $db->prepare("SELECT price, market_cap, supply, max_supply, volume, change_percent, last_updated FROM crypto WHERE symbol = ?");
                    $stmt->bind_param("s", $symbol);
                    $stmt->execute();
                    $stmt->bind_result($price, $market_cap, $supply, $max_supply, $volume, $change_percent, $last_updated);
                    $stmt->fetch();
                    $stmt->close();
                
                    if (!$price) {
                        $response = ["status" => "error", "message" => "Coin symbol '$symbol' not found"];
                        break;
                    }

                    // Fetch last recorded price
                    $stmt = $db->prepare("SELECT last_price FROM price_alerts WHERE coin_symbol = ?");
                    $stmt->bind_param("s", $symbol);
                    $stmt->execute();
                    $stmt->bind_result($old_price);
                    $stmt->fetch();
                    $stmt->close();

                    $price_changed = false;

                    // Add the coin to price_alerts if it is not there
                    if ($old_price === null) {
                        $stmt = $db->prepare("INSERT INTO price_alerts (coin_symbol, last_price) VALUES (?, ?)");
                        $stmt-> bind_param("sd", $symbol, $price);
                        $stmt-> execute();
                        $stmt->close();
                        $old_price = $price;
                    }
                
                    // Check if price has changed
                    if ($old_price != $price) {
                        $price_changed = true;

                        // Update price_alerts with new price
                        $stmt = $db->prepare("INSERT INTO price_alerts (coin_symbol, last_price) 
                                              VALUES (?, ?) ON DUPLICATE KEY UPDATE last_price = ?");
                        $stmt->bind_param("sdd", $symbol, $price, $price);
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        echo "No price change detected for $symbol.\n";
                    }
                
                    // Return response to RabbitMQ
                    $response = [
                        "status" => "success",
                        "old_price" => $old_price,
                        "price" => $price,
                        "market_cap" => $market_cap,
                        "supply" => $supply,
                        "max_supply" => $max_supply,
                        "volume" => $volume,
                        "change_percent" => $change_percent,
                        "last_updated" => $last_updated,
                        "price_changed" => $price_changed
                    ]; break;
               
                default:
                    echo "Error: Unknown action '$action'.\n";
                }        
            }
        return $response;
    });    
    // Close RabbitMQ connection when done
    $rbMQs->close();
} catch (Exception $error) {
    echo "Error: " . $error->getMessage() . "\n\n";
}
?>



