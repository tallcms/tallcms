# Introduction

REST API for managing TallCMS content including pages, posts, categories, media, and webhooks.

<aside>
    <strong>Base URL</strong>: <code>https://tallcms.test</code>
</aside>

    Welcome to the TallCMS API documentation. This API provides programmatic access to manage your CMS content.

    ## Authentication
    All API endpoints (except token creation) require authentication via Bearer token.

    To get a token, send a POST request to `/api/v1/tallcms/auth/token` with your credentials and desired abilities.

    ## Rate Limiting
    - Standard endpoints: 60 requests/minute
    - Authentication: 5 failed attempts triggers a 15-minute lockout

    ## Token Abilities
    Tokens must specify explicit abilities. Available abilities:
    - `pages:read`, `pages:write`, `pages:delete`
    - `posts:read`, `posts:write`, `posts:delete`
    - `categories:read`, `categories:write`, `categories:delete`
    - `media:read`, `media:write`, `media:delete`
    - `webhooks:manage`

    <aside>As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).</aside>

