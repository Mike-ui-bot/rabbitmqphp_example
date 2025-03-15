let userBalance = 0; // Global variable to store user balance
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
        updateTotalPrice();  // Keep this to update the total price without triggering the availability check
    });


    amountInput.addEventListener("input", function () {
        updateTotalPrice();  // Keep this to update the total price without triggering the availability check
    });


    const tradeForm = document.getElementById("trade-form");
    tradeForm.addEventListener("submit", function (e) {
        e.preventDefault();
        executeTrade();  // Balance and coin checks are only triggered here
    });


    fetchBalance();  // Fetch balance when the page loads
});


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


    // Removed the availability check here, it's not needed while editing
}


function getCoinPrice(symbol) {
    const coin = coinsData.find(coin => coin.symbol === symbol);
    return coin ? parseFloat(coin.priceUsd) : 0;
}


function fetchBalance() {
    fetch('get_balance.php') // Updated URL
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                userBalance = parseFloat(data.balance);
                console.log("User Balance:", userBalance);
            } else {
                console.error('Error fetching balance:', data.message);
            }
        })
        .catch(error => {
            console.error('Error fetching balance:', error);
        });
}




function checkBuyAvailability(totalPrice) {
    if (totalPrice > userBalance) {
        alert(`You do not have enough funds to complete this purchase. Your balance is $${userBalance.toFixed(2)}.`);
    } else {
        alert('Proceed with the purchase');


        const coinInput = document.getElementById("coin");
        const amountInput = document.getElementById("amount");


        const coinDetails = coinInput.value.split(' ');
        const coinSymbol = coinDetails[1]?.replace('(', '').replace(')', '');
        const coinName = coinDetails[0];
        const amount = parseFloat(amountInput.value);


        const coinPrice = getCoinPrice(coinSymbol);


        fetch('trade.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'buy',
                coin_symbol: coinSymbol,
                coin_name: coinName,
                amount: amount,
                balance: userBalance - totalPrice,
                price: coinPrice
            })
        });
    }
}


function checkSellAvailability(amount, coinSymbol, coinName, coinPrice) {
    fetch('get_portfolio.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const portfolio = data.portfolio;
                const coin = portfolio.find(item => item.coin_symbol === coinSymbol);
                if (!coin || coin.quantity < amount) {
                    alert("You do not have enough of this coin to sell.");
                } else {
                    executeSellTrade(amount, coinSymbol, coinName, coinPrice);
                }
            } else {
                console.error('Error fetching portfolio and balance:', data.message);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
}


function executeSellTrade(amount, coinSymbol, coinName, coinPrice) {
    const totalPrice = amount * coinPrice;
    fetch('trade.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'sell',
            coin_symbol: coinSymbol,
            coin_name: coinName,
            amount: amount,
            price: coinPrice,
            profit: totalPrice
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success'){
            alert("Sell order executed successfully!");
            location.reload();
        }else{
            alert("There was an error processing your sell order");
        }
    })
    .catch(error => {
        console.error("Error executing sell:", error);
    });
}


function executeTrade() {
    const coinInput = document.getElementById("coin");
    const amountInput = document.getElementById("amount");
    const tradeType = document.querySelector('input[name="trade-type"]:checked').value;


    const coinDetails = coinInput.value.split(' ');
    const coinSymbol = coinDetails[1]?.replace('(', '').replace(')', '');
    const coinName = coinDetails[0]; // Extract coinName here
    const amount = parseFloat(amountInput.value);
    const coinPrice = getCoinPrice(coinSymbol);


    if (tradeType === 'buy') {
        checkBuyAvailability(amount * coinPrice);
    } else if (tradeType === 'sell') {
        checkSellAvailability(amount, coinSymbol, coinName, coinPrice); // Pass coinName
    }
}

