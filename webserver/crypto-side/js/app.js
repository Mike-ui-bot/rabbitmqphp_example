document.addEventListener("DOMContentLoaded", function () {
    if (document.getElementById("crypto-list")) {
        fetchCryptoData();
    }
});

// Fetch cryptocurrency data from CoinCap API
function fetchCryptoData() {
    fetch("https://api.coincap.io/v2/assets")
        .then(response => response.json())
        .then(data => {
            let coinsTable = document.getElementById("crypto-list");
            coinsTable.innerHTML = ""; // Clear previous data
            let coins = data.data.slice(0, 10); // Get top 10 cryptos

            coins.forEach(coin => {
                let row = document.createElement("tr");
                row.innerHTML = `
                    <td>${coin.rank}</td>
                    <td>${coin.name} (${coin.symbol})</td>
                    <td>$${parseFloat(coin.priceUsd).toFixed(2)}</td>
                    <td>${parseFloat(coin.changePercent24Hr).toFixed(2)}%</td>
                `;
                coinsTable.appendChild(row);
            });
        })
        .catch(error => console.error("Error fetching data:", error));
}



