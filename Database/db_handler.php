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
        
            // Registration handling
            if ($action === "register") {
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
                }

            // Get user portfolio 
            } elseif ($action === "get_portfolio") {
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

                    // Fetch portfolio using user_id
                    $stmt = $db->prepare("SELECT coin_name, coin_symbol, quantity, average_price, balance FROM portfolio WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $portfolio = [];

                    while ($row = $result->fetch_assoc()) {
                        $portfolio[] = $row;
                    }
                    $stmt->close();

                    echo "DEBUG: Retrieved portfolio: " . json_encode($portfolio) . "\n"; // Log portfolio data

                    $response = ["status" => "success", "portfolio" => $portfolio];
                }

            // Get user balance
            } elseif ($action === "get_balance") {
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

                    // Fetch balance from portfolio using user_id
                    $stmt = $db->prepare("SELECT balance FROM portfolio WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->bind_result($balance);
                    $stmt->fetch();
                    $stmt->close();

                    echo "DEBUG: Retrieved balance = $balance\n"; // Log balance

                    $response = ["status" => "success", "balance" => $balance];
                }
            } elseif ($action === "add_funds") {
                $username = $data['username'];
                $amount = $data['amount']; // Amount to add (e.g., 10,000)
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

                    $stmt = $db->prepare("UPDATE portfolio SET balance = balance + ? WHERE user_id = ?");
                    $stmt->bind_param("di", $amount, $user_id);
                    
                    if ($stmt->execute()) {
                        // Fetch the updated balance after adding funds
                        $stmt = $db->prepare("SELECT balance FROM portfolio WHERE user_id = ?");
                        $stmt->bind_param("i", $user_id);
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
                }
	        } elseif ($action === "getTransactions") {
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
                }
	   
            } elseif ($action === "buy") {
                $username = $data['username'];
                $coinSymbol = $data['coin_symbol'];
                $coinName = $data['coin_name'];
                $amount = $data['amount'];
                
                // Get user_id and balance
                $stmt = $db->prepare("SELECT id, balance FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->bind_result($user_id, $balance);
                $stmt->fetch();
                $stmt->close();
                
                // Get the current price of the coin
                $coinPrice = getCoinPrice($coinSymbol); 
                $totalPurchaseAmount = $amount * $coinPrice;
                
                // Check if user has enough balance
                if ($balance < $totalPurchaseAmount) {
                    $response = ["status" => "error", "message" => "Insufficient balance."];
                } else {
                    // Deduct balance
                    $stmt = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                    $stmt->bind_param("di", $totalPurchaseAmount, $user_id);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Check if user already owns the coin
                    $stmt = $db->prepare("SELECT quantity FROM portfolio WHERE user_id = ? AND coin_symbol = ?");
                    $stmt->bind_param("is", $user_id, $coinSymbol);
                    $stmt->execute();
                    $stmt->bind_result($currentQuantity);
                    $stmt->fetch();
                    $stmt->close();
                    
                    if ($currentQuantity > 0) {
                        $stmt = $db->prepare("UPDATE portfolio SET quantity = quantity + ? WHERE user_id = ? AND coin_symbol = ?");
                        $stmt->bind_param("dis", $amount, $user_id, $coinSymbol);
                    } else {
                        $stmt = $db->prepare("INSERT INTO portfolio (user_id, coin_name, coin_symbol, quantity) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("issd", $user_id, $coinName, $coinSymbol, $amount);
                    }
                    $stmt->execute();
                    $stmt->close();
                    
                    // Log transaction
                    $stmt = $db->prepare("INSERT INTO transactions (user_id, coin_symbol, coin_name, amount, price, action, timestamp) VALUES (?, ?, ?, ?, ?, 'buy', NOW())");
                    $stmt->bind_param("issdd", $user_id, $coinSymbol, $coinName, $amount, $coinPrice);
                    $stmt->execute();
                    $stmt->close();
                    
                    $response = ["status" => "success", "message" => "Purchase successful."];
                }
            } elseif ($action === "sell") {
                $username = $data['username'];
                $coinSymbol = $data['coin_symbol'];
                $amount = $data['amount'];
                
                // Get user_id and balance
                $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->bind_result($user_id);
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
                    // Get current price
                    $coinPrice = getCoinPrice($coinSymbol);
                    $totalSellAmount = $amount * $coinPrice;
                    
                    // Deduct coins
                    $stmt = $db->prepare("UPDATE portfolio SET quantity = quantity - ? WHERE user_id = ? AND coin_symbol = ?");
                    $stmt->bind_param("dis", $amount, $user_id, $coinSymbol);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Update balance
                    $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                    $stmt->bind_param("di", $totalSellAmount, $user_id);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Log transaction
                    $stmt = $db->prepare("INSERT INTO transactions (user_id, coin_symbol, coin_name, amount, price, action, timestamp) VALUES (?, ?, ?, ?, ?, 'sell', NOW())");
                    $stmt->bind_param("issdd", $user_id, $coinSymbol, $coinName, $amount, $coinPrice);
                    $stmt->execute();
                    $stmt->close();
                    
                    $response = ["status" => "success", "message" => "Sale successful."];
                }
            } elseif ($action === "login") {
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
                } 
            } elseif ($action === "getTop100Crypto") {
                $query = "SELECT * FROM crypto ORDER BY market_cap DESC LIMIT 100"; // Example: Sort by market cap
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
                        ];
                    }
                    $response = ["status" => "success", "data" => $cryptos];
                } else {
                    $response = ["status" => "error", "message" => "Failed to fetch top 100 cryptocurrencies from the database."];
                }
            
            } else {
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

