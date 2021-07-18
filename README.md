# The price comparison website for Nintendo Switch games
## using Laravel 8
#### artisan command： 
1. game:crawl us
2. summary:sync us
3. summary:group
4. summary:price
### router
| Method    | URI                      |  Action                                              | Middleware |
|-----------|--------------------------|-----------------------------------------------------|------------|
| GET\|HEAD  | /                        | Closure                                             | web        |
| POST      | api/v1/login             | App\Http\Controllers\v1\PassportController@login    | api        |
| POST      | api/v1/logout            | App\Http\Controllers\v1\PassportController@logout   | api    auth:api    |
| POST      | api/v1/register          | App\Http\Controllers\v1\PassportController@register | api        |
| GET\|HEAD  | api/v1/summary           | App\Http\Controllers\v1\SummaryController@index     | api        |
| GET\|HEAD  | api/v1/summary/{groupID} | App\Http\Controllers\v1\SummaryController@show      | api        |
| PUT\|PATCH | api/v1/summary/{groupID} | App\Http\Controllers\v1\SummaryController@update    | api   auth:api      |
| GET\|HEAD  | api/v1/wikigame          | App\Http\Controllers\v1\WikiGameController@index    | api        |
