document.addEventListener("DOMContentLoaded", function () {
    const tradeForm = document.getElementById("trade-form");
    
    if (tradeForm) {
        tradeForm.addEventListener("submit", function (e) {
            e.preventDefault();
            
            let coin = document.getElementById("coin").value;
            let amount = document.getElementById("amount").value;
            let type = document.querySelector('input[name="trade-type"]:checked').value;
            
            if (!coin || !amount || amount <= 0) {
                alert("Please enter a valid amount and select a coin.");
                return;
            }
            
            fetch("php/trade.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ coin, amount, type })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                window.location.reload();
            })
            .catch(error => console.error("Trade Error:", error));
        });
    }
});



