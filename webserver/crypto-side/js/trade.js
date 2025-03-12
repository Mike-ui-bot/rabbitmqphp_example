document.addEventListener("DOMContentLoaded", function () {
    const coinInput = document.getElementById("coin");
    const amountInput = document.getElementById("amount");
    const suggestionsBox = document.getElementById("suggestions");

    fetchCryptoData();

    coinInput.addEventListener("input", function () {
        const searchQuery = coinInput.value.toLowerCase();
        if (searchQuery) {
            filterCoins(searchQuery);
        } else {
            suggestionsBox.innerHTML = '';
        }
        updateTotalPrice();
    });

    amountInput.addEventListener("input", function () {
        updateTotalPrice();
    });

    const tradeForm = document.getElementById("trade-form");
    tradeForm.addEventListener("submit", function (e) {
        e.preventDefault();
        executeTrade();
    });
});

let coinsData = [];

function fetchCryptoData() {
    fetch("https://api.coincap.io/v2/assets")
        .then(response => response.json())
        .then(data => {
            coinsData = data.data;
        })
        .catch(error => console.error("Error fetching data:", error));
}

function filterCoins(query) {
    const filteredCoins = coinsData.filter(coin => 
        coin.name.toLowerCase().includes(query) || coin.symbol.toLowerCase().includes(query)
    );
    displaySuggestions(filteredCoins);
}

function displaySuggestions(coins) {
    const suggestionsBox = document.getElementById("suggestions");
    suggestionsBox.innerHTML = ''; 

    if (coins.length === 0) {
        suggestionsBox.innerHTML = '<div>No coins found</div>';
        return;
    }

    coins.forEach(coin => {
        const suggestionItem = document.createElement("div");
        suggestionItem.classList.add("suggestion-item");
        suggestionItem.textContent = `${coin.name} (${coin.symbol})`;
        suggestionItem.addEventListener("click", function () {
            selectCoin(coin);
        });
        suggestionsBox.appendChild(suggestionItem);
    });
}

function selectCoin(coin) {
    const coinInput = document.getElementById("coin");
    coinInput.value = `${coin.name} (${coin.symbol})`;
    document.getElementById("suggestions").innerHTML = '';
    updateTotalPrice();
}

function updateTotalPrice() {
    const coinInput = document.getElementById("coin");
    const amountInput = document.getElementById("amount");
    const tradeType = document.querySelector('input[name="trade-type"]:checked').value;

    const coinDetails = coinInput.value.split(' ');
    const coinSymbol = coinDetails[1]?.replace('(', '').replace(')', '');

    if (!coinSymbol) return; 

    const amount = parseFloat(amountInput.value);
    const pricePerUnit = getCoinPrice(coinSymbol);

    const totalPrice = amount * pricePerUnit;

    document.getElementById("total-price").textContent = `$${totalPrice.toFixed(2)}`;

    if (tradeType === 'buy') {
        checkBuyAvailability(totalPrice);
    } else if (tradeType === 'sell') {
        checkSellAvailability(amount, coinSymbol);
    }
}

function getCoinPrice(symbol) {
    const coin = coinsData.find(coin => coin.symbol === symbol);
    return coin ? parseFloat(coin.priceUsd) : 0;
}

function checkBuyAvailability(totalPrice) {
    fetchBalance(function (balance) {
        if (totalPrice > balance) {
            alert("You do not have enough funds to complete this purchase.");
        }
    });
}

function checkSellAvailability(amount, coinSymbol) {
    fetchPortfolio(function (portfolio) {
        const coin = portfolio.find(item => item.coin_symbol === coinSymbol);
        if (!coin || coin.quantity < amount) {
            alert("You do not have enough of this coin to sell.");
        }
    });
}

function fetchPortfolio(callback) {
    fetch("path/to/api/getPortfolio")
        .then(response => response.json())
        .then(data => callback(data))
        .catch(error => console.error("Error fetching portfolio:", error));
}

function fetchBalance(callback) {
    fetch("path/to/api/getBalance")
        .then(response => response.json())
        .then(data => callback(data.balance))
        .catch(error => console.error("Error fetching balance:", error));
}

function executeTrade() {
    const coinInput = document.getElementById("coin");
    const amountInput = document.getElementById("amount");
    const tradeType = document.querySelector('input[name="trade-type"]:checked').value;

    const coinDetails = coinInput.value.split(' ');
    const coinSymbol = coinDetails[1]?.replace('(', '').replace(')', '');
    const amount = parseFloat(amountInput.value);
    const totalPrice = amount * getCoinPrice(coinSymbol);

    if (tradeType === 'buy') {
        checkBuyAvailability(totalPrice);
    } else if (tradeType === 'sell') {
        checkSellAvailability(amount, coinSymbol);
    }

    alert(`Executing ${tradeType} trade for ${amount} ${coinSymbol} at $${totalPrice.toFixed(2)}`);
}

