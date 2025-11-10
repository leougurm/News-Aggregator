## News Api

This is an api platform which serves articles from 3 different sources.

It fetches articles periodically from the sources and give results according to saved user preferences or search queries or filtering. 

On project startup every category from the sources are fetched and every 15 min news are retrieved from the sources with specific categories. They are saved into postgresql with vectors for better and faster search results.

## Tools 

- PHP
- Docker
- Laravel
- Horizon
- Redis (for cache and horizon)
- Posgtresql 

## Deploying the app

You only have to run 

``
docker compose up --build
``

If you do not want to wait for the jobs to run you can run. Jobs run every 15 minutes 

``
docker exec news-app php artisan startup-fetch:news
``

After running the project, you can check swagger.

http://localhost:8001/api/documentation

If there are articles fetched you can try a query like this

http://localhost:8001/api/v1/articles/search?q=tesla&category=business

## What does app do from technical perspective

- App owner can turn on or off fetching for a specific source from db
- For every source app gathers their own categories and its jobs runs everyday.
- For every  category app gathers articles from their sources and saves into db with their vectors so that searching would be easier
- Users can login and update their preferences
- If rate limits are reached for the sources fallback api keys are being used
- I put env file for test purposes.

## What could be done more

- Redis can be used for categories or latest articles
- Categories can be mapped for a global section hierarchy in our own app
- Api rate limits are pretty low for some sources. It must be taken into consideration.
- Every category can run in its own job it will be faster and more error prone but rate limits must be taken into consideration
- For semantic search embeddings can be introduced to postgresql. If the db gets bigger elastic search can be an option.



