const express = require('express');
const fetch = (...args) => import('node-fetch').then(({ default: fetch }) => fetch(...args));
const cors = require('cors');

const app = express();
const PORT = 3000;

app.use(cors()); // Allow frontend requests

// Example: You can have multiple Feed IDs
const feedIDs = ['1464826', '5579859', '5713180', '7005778', '7880441', '8082420'];  // Replace with your actual Feed IDs

// Fetch Crypto News from multiple NewsBlur feeds
app.get('/news', async (req, res) => {
    try {
        const feedPromises = feedIDs.map(feedID => {
            return fetch(`https://newsblur.com/reader/feed/${feedID}`, {
                headers: { 'User-Agent': 'Mozilla/5.0' }
            })
            .then(response => response.json())
            .then(data => data.stories) // Extract stories from each feed
            .catch(error => console.error('Error fetching feed:', error));
        });

        // Wait for all feed requests to complete
        const allFeeds = await Promise.all(feedPromises);

        // Flatten the array of news stories from all feeds
        const allStories = allFeeds.flat();

        res.json({ stories: allStories }); // Send all the stories in a single response
    } catch (error) {
        res.status(500).json({ error: 'Failed to fetch news from multiple sources' });
    }
});

app.listen(PORT, () => {
    console.log(`Server running on http://localhost:${PORT}`);
});

