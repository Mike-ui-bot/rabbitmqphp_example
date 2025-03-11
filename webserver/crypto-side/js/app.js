let currentChart = null;

document.addEventListener("DOMContentLoaded", function () {
    if (document.getElementById("crypto-list")) {
        fetchCryptoData();
    }

    // Ensure the close modal functionality is working
    const closeModalButton = document.getElementById('closeModal');
    if (closeModalButton) {
        closeModalButton.addEventListener('click', function() {
            document.getElementById('graphModal').style.display = 'none'; // Close the modal
        });
    }

    // Close the modal when clicking outside the modal content
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('graphModal');
        if (event.target === modal) {
            modal.style.display = 'none'; // Close modal when clicking outside
        }
    });
});

// Function to get top 100 crypto data from DB
function fetchCryptoData() {
    fetch("http://localhost/webserver/dbCryptoCall.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            action: "getTop100Crypto"
        })
    })
    .then(response => response.json())
    .then(data => {
        // Check if the response status is success and if 'data' exists
        if (data.status === "success" && Array.isArray(data.data)) {
            let coinsTable = document.getElementById("crypto-list");
            coinsTable.innerHTML = ""; // Clear previous data

            // Sort the coins by market cap in descending order
            let sortedCoins = data.data.sort((a, b) => parseFloat(b.marketCapUsd) - parseFloat(a.marketCapUsd));

            // Loop through the sorted coins to display the data
            sortedCoins.forEach((coin, index) => {
                let change24Hr = parseFloat(coin.changePercent24Hr).toFixed(2); // Get 24h change value
                let changeColor = change24Hr > 0 ? 'green' : (change24Hr < 0 ? 'red' : 'black'); // Green for positive, red for negative, black for no change

                // Apply color to price
                let priceColor = parseFloat(coin.changePercent24Hr) > 0 ? 'green' : (parseFloat(coin.changePercent24Hr) < 0 ? 'red' : 'black');

                // Define your "promising" coin logic:
                // Example: Coin is below $10 and has a positive change
                let isPromising = parseFloat(coin.priceUsd) < 10 && change24Hr > 0;

                // Create a row for the coin
                let row = document.createElement("tr");

                // Apply 'highlighted-coin' class if the coin is promising
                if (isPromising) {
                    row.classList.add('highlighted-coin');
                    storeRecommendedCoin(coin); // Save the coin to sessionStorage
                }

                // Use index + 1 for rank since array is 0-indexed
                row.innerHTML = `
                    <td>${index + 1}</td> <!-- Rank -->
                    <td><a href="#" class="coin-link" data-id="${coin.id}">${coin.name} (${coin.symbol})</a></td>
                    <td style="color: ${priceColor};">$${parseFloat(coin.priceUsd).toFixed(2)}</td>
                    <td style="color: ${changeColor};">${change24Hr}%</td>
                `;
                coinsTable.appendChild(row);
            });

            // Add click event to each coin link: displays coin history chart
            document.querySelectorAll('.coin-link').forEach(link => {
                link.addEventListener('click', function(event) {
                    event.preventDefault();
                    const coinId = this.getAttribute('data-id');
                    const coinName = this.innerText.split(' (')[0];  // Extract name from "Bitcoin (BTC)"
                    fetchCoinHistory(coinId, coinName);
                });
            });
        } else {
            console.error("Failed to fetch data:", data);
        }
    })
    .catch(error => console.error("Error fetching data:", error));
}

// Function to store recommended coin (keep only the last two)
function storeRecommendedCoin(coin) {
    // Retrieve the current list of recommended coins from sessionStorage
    let recommendedCoins = JSON.parse(sessionStorage.getItem('recommended_coins')) || [];

    // Add the new coin to the list
    recommendedCoins.unshift(coin);

    // Keep only the last two coins
    if (recommendedCoins.length > 2) {
        recommendedCoins.pop();
    }

    // Store the updated list in sessionStorage
    sessionStorage.setItem('recommended_coins', JSON.stringify(recommendedCoins));

    // Optionally, update the "Keep an Eye On" table
    updateWatchlist(recommendedCoins);
}

// Function to update the "Keep an Eye On" table
function updateWatchlist(coins) {
    const watchlistTable = document.getElementById('watchlist');
    watchlistTable.innerHTML = ''; // Clear previous list

    if (coins.length === 0) {
        watchlistTable.innerHTML = '<tr><td colspan="4">No recommended coins yet.</td></tr>';
    } else {
        coins.forEach(coin => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${coin.rank}</td>
                <td>${coin.name} (${coin.symbol})</td>
                <td>$${parseFloat(coin.priceUsd).toFixed(2)}</td>
                <td>${parseFloat(coin.changePercent24Hr).toFixed(2)}%</td>
            `;
            watchlistTable.appendChild(row);
        });
    }
}

// Function to fetch and display the coin's historical data
function fetchCoinHistory(coinId, coinName) {
    fetch("http://localhost/webserver/dmzCryptoCall.php", { // Daily data
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        // API URL is dynamic; depends on what coin you want to view
        body: JSON.stringify({
            action: "getCoinHistory",
            coinId: coinId,
            interval: "d1"
        })
    })
        .then(response => response.json())
        .then(data => {
            const prices = data.data;
            const dates = prices.map(item => item.date);
            const priceValues = prices.map(item => parseFloat(item.priceUsd));

            displayGraph(dates, priceValues, coinName);  // Pass the coin name here
        });
}


// Function to display the graph
function displayGraph(dates, priceValues, coinName) {
    // If there's an existing chart, destroy it to reset before creating a new one
    if (currentChart) {
        currentChart.destroy();
    }

    const ctx = document.getElementById('coin-graph').getContext('2d');
    const coinNameElement = document.getElementById('coin-name');
    
    // Update coin name at the top of the graph
    coinNameElement.innerText = coinName;

    // Close the modal if it exists
    const graphModal = document.getElementById('graphModal');
    graphModal.style.display = 'block';

    // Create the chart
    currentChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates.map(date => new Date(date).toLocaleDateString()),
            datasets: [{
                label: 'Price in USD',
                data: priceValues,
                fill: false,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Price (USD)'
                    }
                }
            }
        }
    });
}
