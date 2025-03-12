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

    document.getElementById("search-bar").addEventListener("input", filterCoins);
    document.getElementById("filter-market-cap").addEventListener("change", filterCoins);
    document.getElementById("filter-positive-change").addEventListener("change", filterCoins);
});

let coinsData = [];

// Shows top 100 crypto data from CoinCap API
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
            coinsData = data.data;
            displayCoins(coinsData);
        })
        .catch(error => console.error("Error fetching data:", error));
}

function displayCoins(coins) {
    let coinsTable = document.getElementById("crypto-list");
    coinsTable.innerHTML = "";
    coins.forEach(coin => {
        let change24Hr = parseFloat(coin.changePercent24Hr).toFixed(2);
        let changeColor = change24Hr > 0 ? 'green' : (change24Hr < 0 ? 'red' : 'black');
        let priceColor = change24Hr > 0 ? 'green' : (change24Hr < 0 ? 'red' : 'black');
        let isPromising = parseFloat(coin.priceUsd) < 10 && change24Hr > 0;
        let row = document.createElement("tr");
        if (isPromising) {
            row.classList.add('highlighted-coin');
            storeRecommendedCoin(coin);
        }
        row.innerHTML = `
            <td>${coin.rank}</td>
            <td><a href="#" class="coin-link" data-id="${coin.id}">${coin.name} (${coin.symbol})</a></td>
            <td style="color: ${priceColor};">$${parseFloat(coin.priceUsd).toFixed(2)}</td>
            <td style="color: ${changeColor};">${change24Hr}%</td>
        `;
        coinsTable.appendChild(row);
    });
    document.querySelectorAll('.coin-link').forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const coinId = this.getAttribute('data-id');
            const coinName = this.innerText.split(' (')[0];
            fetchCoinHistory(coinId, coinName);
        });
    });
}

function storeRecommendedCoin(coin) {
    let recommendedCoins = JSON.parse(sessionStorage.getItem('recommended_coins')) || [];
    recommendedCoins.unshift(coin);
    if (recommendedCoins.length > 2) {
        recommendedCoins.pop();
    }
    sessionStorage.setItem('recommended_coins', JSON.stringify(recommendedCoins));
    updateWatchlist(recommendedCoins);
}

function updateWatchlist(coins) {
    const watchlistTable = document.getElementById('watchlist');
    watchlistTable.innerHTML = '';
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
function fetchCoinDetails(coinId, coinName) {
    fetch(`https://api.coincap.io/v2/assets/${coinId}`)
        .then(response => response.json())
        .then(data => {
            if (!data || !data.data) {
                throw new Error('No data found for the coin');
            }

            const coinData = data.data;
            const marketCap = parseFloat(coinData.marketCapUsd).toFixed(2);
            const tradingVolume = parseFloat(coinData.volumeUsd24Hr).toFixed(2);
            const circulatingSupply = parseFloat(coinData.supply).toFixed(2);
            const rank = coinData.rank;

            // Now, update the modal with the real-time data
            document.getElementById('market-cap').innerText = `$${marketCap}`;
            document.getElementById('trading-volume').innerText = `$${tradingVolume}`;
            document.getElementById('circulating-supply').innerText = circulatingSupply;
            document.getElementById('rank').innerText = rank;
        })
        .catch(error => {
            console.error("Error fetching coin details:", error);
            alert("Could not fetch coin details: " + error.message); // Optional: User feedback
        });
}
function fetchCoinHistory(coinId, coinName) {
    fetch(`https://api.coincap.io/v2/assets/${coinId}/history?interval=d1`)
        .then(response => response.json())
        .then(data => {
            if (!data || !data.data) {
                throw new Error('No data found for the coin');
            }

            const prices = data.data;
            if (!prices.length) {
                throw new Error('No price data available');
            }

            const dates = prices.map(item => item.date);
            const priceValues = prices.map(item => parseFloat(item.priceUsd));
            displayGraph(dates, priceValues, coinName);

            // After fetching the historical data, fetch the additional coin details
            fetchCoinDetails(coinId, coinName);
        })
        .catch(error => {
            console.error("Error fetching coin data:", error);
            alert("Could not fetch coin data: " + error.message); // Optional: User feedback
        });
}


function displayGraph(dates, priceValues, coinName) {
    if (currentChart) {
        currentChart.destroy();
    }
    const ctx = document.getElementById('coin-graph').getContext('2d');
    document.getElementById('coin-name').innerText = coinName;
    document.getElementById('graphModal').style.display = 'block';
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
                x: { title: { display: true, text: 'Date' } },
                y: { title: { display: true, text: 'Price (USD)' } }
            }
        }
    });
}

function filterCoins() {
    let searchQuery = document.getElementById("search-bar").value.toLowerCase();
    let filterMarketCap = document.getElementById("filter-market-cap").checked;
    let filterPositiveChange = document.getElementById("filter-positive-change").checked;

    let filteredCoins = coinsData.filter(coin => {
        let matchesSearch = coin.name.toLowerCase().startsWith(searchQuery);
        let matchesMarketCap = !filterMarketCap || parseFloat(coin.marketCapUsd) > 1000000000;
        let matchesPositiveChange = !filterPositiveChange || parseFloat(coin.changePercent24Hr) > 0;
        return matchesSearch && matchesMarketCap && matchesPositiveChange;
    });

    displayCoins(filteredCoins);
}

