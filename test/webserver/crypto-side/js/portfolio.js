document.addEventListener("DOMContentLoaded", function () {
    if (document.getElementById("portfolio-list")) {
        fetchPortfolio();
    }
});

// Fetches user portfolio
function fetchPortfolio() {
    fetch("php/portfolio.php")
        .then(response => response.json())
        .then(data => {
            let portfolioTable = document.getElementById("portfolio-list");
            portfolioTable.innerHTML = "";

            if (data.length === 0) {
                portfolioTable.innerHTML = "<tr><td colspan='4'>No holdings yet.</td></tr>";
                return;
            }

            data.forEach(coin => {
                let row = document.createElement("tr");
                row.innerHTML = `
                    <td>${coin.coin_name} (${coin.coin_symbol})</td>
                    <td>${parseFloat(coin.quantity).toFixed(4)}</td>
                    <td>$${parseFloat(coin.average_price).toFixed(2)}</td>
                    <td>$${parseFloat(coin.total_value).toFixed(2)}</td>
                `;
                portfolioTable.appendChild(row);
            });
        })
        .catch(error => console.error("Error fetching portfolio:", error));
}



