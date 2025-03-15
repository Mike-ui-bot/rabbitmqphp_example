document.addEventListener("DOMContentLoaded", function () {
    const notificationForm = document.getElementById("notification-form");
    
    if (notificationForm) {
        notificationForm.addEventListener("submit", function (e) {
            e.preventDefault();

            let coin = document.getElementById("coin-alert").value;
            let price = document.getElementById("price-alert").value;

            if (!coin || !price || price <= 0) {
                alert("Please enter a valid price and select a coin.");
                return;
            }

            fetch("php/notifications.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ coin, price })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
            })
            .catch(error => console.error("Notification Error:", error));
        });
    }
});



