document.addEventListener("DOMContentLoaded", function () {
    const coinInput = document.getElementById("coin_symbol");  // Assuming you have an input field with id 'coin_symbol'
    const suggestionsBox = document.getElementById("suggestions");  // Assuming you have a div with id 'suggestions' for suggestions

    fetchCryptoData();

    coinInput.addEventListener("input", function () {
        const searchQuery = coinInput.value.toLowerCase();
        if (searchQuery) {
            filterCoins(searchQuery);
        } else {
            suggestionsBox.innerHTML = '';
        }
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
    suggestionsBox.innerHTML = ''; // Clear previous suggestions

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
    const coinInput = document.getElementById("coin_symbol");
    coinInput.value = `${coin.name} (${coin.symbol})`;
    document.getElementById("suggestions").innerHTML = '';  // Clear suggestions after selection
}

