<?php
// recommendations.php

session_start(); // Start the session to access $_SESSION['username']

function getCoinCapId($symbol) {
    $coinIdCache = []; // Initialize cache within the function scope
    if (isset($coinIdCache[$symbol])) {
        return $coinIdCache[$symbol];
    }

    $url = "https://api.coincap.io/v2/assets?search=" . $symbol;
    $response = @file_get_contents($url);
    $data = $response ? json_decode($response, true) : null;

    if ($data && isset($data['data']) && count($data['data']) > 0) {
        foreach($data['data'] as $asset){
            if(strtoupper($asset['symbol']) === strtoupper($symbol)){
                $coinIdCache[$symbol] = $asset['id'];
                return $asset['id'];
            }
        }
    }
    return null; // CoinCap ID not found
}

function calculateVolatility($coinId) {
    $now = time();
    $oneWeekAgo = $now - (7 * 24 * 60 * 60); // 7 days ago

    $url = "https://api.coincap.io/v2/assets/" . $coinId . "/history?interval=d1&start=" . ($oneWeekAgo * 1000) . "&end=" . ($now * 1000);
    $response = @file_get_contents($url);
    $data = $response ? json_decode($response, true) : null;

    if ($data && isset($data['data']) && count($data['data']) > 0) {
        $prices = array_column($data['data'], 'priceUsd');
        $averagePrice = array_sum($prices) / count($prices);
        $squaredDifferences = array_map(function($price) use ($averagePrice) {
            return pow($price - $averagePrice, 2);
        }, $prices);
        $variance = array_sum($squaredDifferences) / count($squaredDifferences);
        $volatility = sqrt($variance);
        return $volatility;
    }
    return 0;
}

// Recommendation Logic
$allCoinsUrl = "https://api.coincap.io/v2/assets?limit=2000";
$allCoinsResponse = @file_get_contents($allCoinsUrl);
$allCoinsData = $allCoinsResponse ? json_decode($allCoinsResponse, true) : null;

$recommendedCoins = [];
if ($allCoinsData && isset($allCoinsData['data'])) {
    require_once(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQLib.inc');
    use RabbitMQ\RabbitMQClient;
    $client = new RabbitMQClient(__DIR__ . '/../../rabbitmqphp_example/RabbitMQ/RabbitMQ.ini', 'Database');

    $portfolio_request = json_encode([
        'action' => 'get_portfolio',
        'username' => $_SESSION['username']
    ]);
    $portfolio_response = json_decode($client->sendRequest($portfolio_request), true);
    $portfolio = $portfolio_response['status'] === 'success' ? $portfolio_response['portfolio'] : [];

    foreach ($allCoinsData['data'] as $asset) {
        $symbol = $asset['symbol'];
        $coinId = $asset['id'];
        $inPortfolio = false;
        foreach ($portfolio as $coin) {
            if (strtoupper($coin['coin_symbol']) === strtoupper($symbol)) {
                $inPortfolio = true;
                break;
            }
        }
        if (!$inPortfolio) {
            $volatility = calculateVolatility($coinId);
            if ($volatility > 50) { // Adjust volatility threshold as needed
                $recommendedCoins[] = [
                    'symbol' => $symbol,
                    'name' => $asset['name'],
                    'volatility' => $volatility
                ];
            }
        }
    }
}

// Generate HTML table
$table = '<table><thead><tr><th>Coin</th><th>Volatility</th></tr></thead><tbody>';
foreach ($recommendedCoins as $coin) {
    $table .= '<tr><td>' . htmlspecialchars($coin['name']) . ' (' . htmlspecialchars($coin['symbol']) . ')</td><td>' . number_format($coin['volatility'], 2) . '</td></tr>';
}
$table .= '</tbody></table>';

// Send table data as JSON
header('Content-Type: application/json');
echo json_encode(['table' => $table]);
?>


