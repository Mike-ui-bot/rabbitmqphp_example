let page = 1;  // Track the page for loading more news
let allNews = [];  // Store all fetched news

async function fetchCryptoNews(page = 1) {
    try {
        const response = await fetch(`http://localhost:3000/news?page=${page}`);

        if (!response.ok) {
            throw new Error(`Failed to fetch news: ${response.statusText}`);
        }

        const data = await response.json();
        console.log("Fetched Crypto News:", data);

        if (data.stories) {
            allNews = [...allNews, ...data.stories];  // Add the new stories to the existing ones
            displayNews(data.stories);  // Display the new news only
        } else {
            console.error('No stories found');
        }
    } catch (error) {
        console.error('Error fetching news:', error);
    }
}

function displayNews(newsData) {
    const container = document.getElementById('news-container');
    
    // Loop through each story and create a card
    newsData.forEach(article => {
        const newsCard = document.createElement('div');
        newsCard.classList.add('news-card');

        // Ensure no repeating titles
        const title = article.story_title || "No title available";

        // Ensure "Read more" link is valid
        const readMoreLink = article.story_permalink ? article.story_permalink : '#'; // Default to # if no link is provided

        newsCard.innerHTML = `
            <h3>${title}</h3>
            <p>${article.story_content ? article.story_content.substring(0, 100) + '...' : 'No description available.'}</p>
            <a href="${readMoreLink}" target="_blank" class="read-more-link">Read more</a>
        `;

        container.appendChild(newsCard);
    });
}


// Function to handle loading more articles
function loadMoreNews() {
    page++;
    fetchCryptoNews(page);
}

document.addEventListener('DOMContentLoaded', () => {
    fetchCryptoNews();
});

